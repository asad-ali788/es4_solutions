<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\CampaignPerformanceExport;
use App\Exports\KeywordRecommendationsExport;
use App\Exports\TargetRecommendationsExport;
use App\Jobs\Ads\AmzCampaignPerformanceUpdateJob;
use App\Jobs\Ads\AmzKeywordPerformanceUpdatesJob;
use App\Jobs\Ads\AmzPerformanceRevertJob;
use App\Models\AsinRecommendation;
use App\Models\CampaignRecommendations;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AmzKeywordRecommendation;
use App\Models\AmzPerformanceChangeLog;
use App\Models\AmzTargetRecommendation;
use App\Models\CampaignBudgetRecommendationRule;
use App\Models\ProductAsins;
use App\Models\KeywordBidRecommendationRule;
use App\Traits\HasFilteredAdsPerformance;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmzAdsPerformanceController extends Controller
{

    use HasFilteredAdsPerformance;

    public function asinPerformance(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsAsinPerformance);

        $weeks = AsinRecommendation::distinct()
            ->orderByDesc('report_week')
            ->pluck('report_week');

        $selectedWeek = $request->input('week', $weeks->first());

        $query = AsinRecommendation::query()
            ->leftJoin('product_categorisations as pc', function ($join) {
                $join->on('pc.child_asin', '=', 'asin_recommendations.asin')
                    ->whereNull('pc.deleted_at');
            })
            ->select('asin_recommendations.*')
            ->addSelect(DB::raw('pc.child_short_name as product_name'));

        if ($request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('asin_recommendations.asin', 'like', $search)
                    ->orWhere('asin_recommendations.acos', 'like', $search)
                    ->orWhere('asin_recommendations.report_week', 'like', $search)
                    ->orWhere('pc.child_short_name', 'like', $search); // ✅ searchable by product name
            });
        }

        if ($request->filled('country') && $request->country !== 'all') {
            $query->where('asin_recommendations.country', $request->country);
        }

        if ($request->filled('campaign') && $request->campaign !== 'all') {
            $query->where('asin_recommendations.campaign_types', $request->campaign);
        }

        if (!empty($selectedWeek)) {
            $query->where('asin_recommendations.report_week', $selectedWeek);
        }

        $asins = $query->orderBy('asin_recommendations.country')
            ->paginate($request->input('per_page', 25))
            ->appends($request->all());

        return view('pages.admin.amzAds.performance.asins', compact('asins', 'weeks', 'selectedWeek'));
    }


    // Campaign performance table 
    public function capaignPerformance(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignPerformance);
        try {
            $query = $this->getFilteredCampaignsQuery($request);
            $campaigns = $query->orderBy('enabled_campaigns_count', 'desc')
                ->paginate($request->input('per_page', 25));

            // Merge asins for all campaigns (flattened, unique)
            $merged = $this->mergeCampaignAsins($campaigns->getCollection());
            $allAsins = $merged->pluck('related_asins')
                ->flatten()
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Use global cache for asin-to-name mapping (like keywords)
            $asinToName = cache()->remember('product_categorisations_asin_to_name', 60 * 24 * 30, function () {
                return DB::table('product_categorisations')
                    ->whereNull('deleted_at')
                    ->pluck('child_short_name', 'child_asin')
                    ->toArray();
            });

            $merged->transform(function ($row) use ($asinToName) {
                $names = collect($row->related_asins ?? [])
                    ->map(fn($a) => $asinToName[$a] ?? null)
                    ->filter()
                    ->unique()
                    ->values();
                $row->product_name = $row->sp_product_name
                    ?? $row->sd_product_name
                    ?? ($names->first() ?? null);
                $row->product_names = $names->all();
                return $row;
            });
            $campaigns->setCollection($merged);

            $ruleFilter = CampaignBudgetRecommendationRule::select('id', 'action_label')->get();
            $selectedWeek = $request->date ?? Carbon::now(config('timezone.market'))->startOfDay()->subDay()->toDateString();
            $type = "campaign";

            // toast when filters applied (other than search)
            $ignore = ['page', 'per_page', 'search'];
            $hasExtraFilters = collect($request->query())
                ->reject(fn($v) => $v === null || $v === '' || $v === [] || $v === 'all')
                ->keys()
                ->diff($ignore)
                ->isNotEmpty();
            if ($hasExtraFilters) {
                session()->flash('info', 'Filters applied');
            }
            return view('pages.admin.amzAds.performance.campaigns', compact('campaigns', 'selectedWeek', 'ruleFilter', 'type'));
        } catch (Exception $e) {
            Log::error('Campaign Performance', ['e' => $e->getMessage()]);
            return redirect()->back()->with('error', "Something went wrong! Please try again.");
        }
    }


    // make ready the campaign to update the campaign budget
    public function runUpdate(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignPerformanceRunUpdate);
        try {
            $runUpdate = (bool) $request->input('run_update', true);

            if ($request->boolean('bulk')) {
                $req    = $request->except(['run_update', 'run_status']);
                $fake   = new Request($req);
                $shared = $this->getFilteredCampaignsQuery($fake);
                $count = 0;
                $idQuery = (clone $shared)
                    ->toBase()
                    ->reorder() // remove orderBys from shared index query
                    ->selectRaw('campaign_recommendations.id as cr_id')
                    ->distinct()
                    ->orderBy('campaign_recommendations.id');

                $idQuery->chunkById(500, function ($rows) use ($runUpdate, &$count) {
                    $ids = collect($rows)->pluck('cr_id')->all();
                    $count += count($ids);
                    DB::table('campaign_recommendations')
                        ->whereIn('id', $ids)
                        ->update([
                            'run_update' => $runUpdate,
                            'run_status' => 'pending',
                            'updated_at' => now(),
                        ]);
                }, 'campaign_recommendations.id', 'cr_id');

                return back()->with('success', "Bulk run update applied to {$count} campaigns.");
            }
            // Single update
            $campaignId = $request->input('campaign_id');
            if (!$campaignId) {
                return response()->json(['success' => false, 'message' => 'No campaign ID provided.']);
            }

            CampaignRecommendations::where('id', $campaignId)
                ->update([
                    'run_update' => $runUpdate,
                    'run_status' => 'pending'
                ]);

            return response()->json([
                'success' => true,
                'message' => "Campaign run status updated successfully."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkBudgetUpdate(Request $request)
    {
        try {
            if (! $request->boolean('bulkBudget')) {
                return back()->with('error', 'Bulk budget flag missing.');
            }
            // Allow "none" OR numeric percentage
            $data = $request->validate([
                'update_type' => ['required', 'in:increase,decrease'],
                'percentage'  => ['required'], // handle "none" manually below
            ]);
            $percentageRaw = (string) $request->input('percentage');
            $isReset       = $percentageRaw === 'none';
            $multiplier    = 1.0;
            $pct           = 0.0;
            if (! $isReset) {
                // Validate numeric range when not reset
                $pct = (float) $percentageRaw;

                if (! is_numeric($percentageRaw) || $pct < 0 || $pct > 100) {
                    return back()->with('error', 'Percentage must be a number between 0 and 100, or "none".');
                }

                $multiplier = $data['update_type'] === 'increase'
                    ? (1 + $pct / 100)
                    : (1 - $pct / 100);
            }

            // Exclude form-only fields from filters
            $reqForFilter = $request->except([
                '_token',
                'bulkBudget',
                'update_type',
                'percentage',
                'run_update',
                'run_status'
            ]);

            $fake   = new Request($reqForFilter);
            $shared = $this->getFilteredCampaignsQuery($fake);
            $count  = 0;

            $idQuery = (clone $shared)
                ->toBase()
                ->reorder()
                ->selectRaw('campaign_recommendations.id as cr_id')
                ->distinct()
                ->orderBy('campaign_recommendations.id');

            $idQuery->chunkById(500, function ($rows) use (&$count, $isReset, $multiplier) {

                $ids = collect($rows)->pluck('cr_id')->all();
                if (empty($ids)) {
                    return;
                }

                $count += count($ids);

                $update = [
                    'run_status' => 'pending',
                    'updated_at' => now(),
                ];
                if ($isReset) {
                    $update['manual_budget'] = null; // reset override
                } else {
                    $update['manual_budget'] = DB::raw(
                        "GREATEST(0, COALESCE(total_daily_budget,0) * {$multiplier})"
                    );
                }

                DB::table('campaign_recommendations')
                    ->whereIn('id', $ids)
                    ->update($update);
            }, 'campaign_recommendations.id', 'cr_id');

            $msg = $isReset
                ? "Manual budget reset for {$count} campaigns."
                : "Manual budget updated for {$count} campaigns ({$data['update_type']} {$pct}%).";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('bulkBudgetUpdate failed', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->except(['_token']),
            ]);

            return back()->with('error', 'Bulk budget update failed: ' . $e->getMessage());
        }
    }

    public function campaignMakeLive(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignPerformanceMakeLive);
        $userId = Auth::id() ?? null;
        $marketTz = config('timezone.market');
        $selectedWeek = $request->date
            ?? Carbon::now($marketTz)->subDay()->toDateString();

        $selectedDate = Carbon::parse($selectedWeek, $marketTz)->startOfDay();
        $today = Carbon::now($marketTz)->startOfDay();
        $threeDaysAgo = Carbon::now($marketTz)->subDays(2)->startOfDay(); // day before yesterday

        //  Allow today, yesterday, or day before yesterday
        if ($selectedDate->between($threeDaysAgo, $today)) {
            try {
                // Update pending campaigns
                $updated = CampaignRecommendations::where('run_update', true)->where('run_status', 'pending')
                    ->whereDate('report_week', $selectedWeek)
                    ->update(['run_status' => 'dispatched']);

                if ($updated === 0) {
                    return redirect()->back()->with('error', "No campaigns were eligible for dispatch.");
                }

                // Dispatch job
                AmzCampaignPerformanceUpdateJob::dispatch($selectedWeek, $userId);
                return redirect()->back()->with('success', "The campaigns are moved to queue and will be updated soon.");
            } catch (\Exception $e) {
                Log::error("Failed to dispatch campaign update: " . $e->getMessage(), [
                    'date' => $selectedWeek,
                ]);
                return redirect()->back()->with('error', "Something went wrong while queuing campaigns. Please try again.");
            }
        }
        return redirect()->back()->with('error', "Selected date is not today or yesterday, no action taken.");
    }

    public function campaignPerformanceExport(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignPerformanceExcelExport);
        $marketTz     = config('timezone.market');
        $selectedWeek = $request->date ?? Carbon::now($marketTz)->startOfDay()->subDay()->toDateString();
        $fileName     = "campaign_performance_{$selectedWeek}.xlsx";
        return Excel::download(new CampaignPerformanceExport($request->all()), $fileName);
    }

    public function keywordPerformance(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordPerformance);
        try {
            $query   = $this->getFilteredKeywordsQuery($request);
            $perPage = (int) $request->input('per_page', 25);
            $keywords = $query
                ->orderByDesc('amz_keyword_recommendations.total_sales')
                ->paginate($perPage)
                ->withQueryString();

            // The output data is the same: use sp_product_name or asin_product_name for product_name
            // Cache product_categorisations globally for 1 month
            $asinToName = cache()->remember('product_categorisations_asin_to_name', 60 * 24 * 30, function () {
                return DB::table('product_categorisations')
                    ->whereNull('deleted_at')
                    ->pluck('child_short_name', 'child_asin')
                    ->toArray();
            });

            $keywords->getCollection()->transform(function ($row) use ($asinToName) {
                // Gather all possible ASINs: single and related
                $asins = [];
                if (!empty($row->asin)) {
                    $asins[] = $row->asin;
                }
                // related_asin may be a JSON array or comma-separated string
                if (!empty($row->related_asin)) {
                    if (is_string($row->related_asin)) {
                        $decoded = json_decode($row->related_asin, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $asins = array_merge($asins, $decoded);
                        } else {
                            // fallback: comma-separated
                            $asins = array_merge($asins, array_map('trim', explode(',', $row->related_asin)));
                        }
                    } elseif (is_array($row->related_asin)) {
                        $asins = array_merge($asins, $row->related_asin);
                    }
                }
                $asins = array_filter(array_unique($asins));

                // Map all ASINs to names
                $names = collect($asins)
                    ->map(fn($a) => $asinToName[$a] ?? null)
                    ->filter()
                    ->unique()
                    ->values();

                // Compose product_name for display (all names, comma separated)
                $row->product_name = $row->sp_product_name
                    ?? $row->asin_product_name
                    ?? ($names->isNotEmpty() ? $names->implode(', ') : null);
                $row->product_names = $names->all();
                return $row;
            });

            $ruleFilter   = KeywordBidRecommendationRule::select('id', 'action_label')->get();
            $selectedDate = $request->date ?? Carbon::now(config('timezone.market'))->subDay()->toDateString();
            $type         = 'keyword';
            return view('pages.admin.amzAds.performance.keywords',
                compact('keywords', 'selectedDate', 'ruleFilter', 'type')
            );
        } catch (Exception $e) {
            Log::error('Keyword Performance', ['e' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', "Something went wrong! Please try again.");
        }
    }

    public function runKeywordUpdate(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordPerformanceRunUpdate);

        try {
            $runUpdate = (bool) $request->input('run_update', true);

            if ($request->boolean('bulk')) {

                $req  = $request->except(['run_update', 'run_status']);
                $fake = new Request($req);


                // Only fetch IDs for update, avoid heavy joins/columns

                // Only select the id column for chunking
                // Get only the IDs in batches, avoid chunkById on joined query
                $ids = $this->getFilteredKeywordsQuery($fake)
                    ->get(['amz_keyword_recommendations.id'])
                    ->pluck('id')
                    ->all();

                $count = 0;
                foreach (array_chunk($ids, 5000) as $batch) {
                    $count += count($batch);
                    DB::table('amz_keyword_recommendations')
                        ->whereIn('id', $batch)
                        ->update([
                            'run_update' => $runUpdate,
                            'run_status' => 'pending',
                            'updated_at' => now(),
                        ]);
                }

                return redirect()->back()
                    ->with('success', "Bulk run update applied to {$count} keywords across all pages.");
            }

            $keywordId = $request->input('keyword_id');

            if (!$keywordId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No keyword ID provided.'
                ]);
            }

            AmzKeywordRecommendation::where('id', $keywordId)
                ->update([
                    'run_update' => $runUpdate,
                    'run_status' => 'pending',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "Keyword run status updated successfully."
            ]);
        } catch (\Exception $e) {
            if ($request->boolean('bulk')) {
                Log::error("Bulk keyword run update failed: " . $e->getMessage(), [
                    'request_data' => $request->all(),
                ]);
                return redirect()->back()
                    ->with('error', "Something went wrong");
            }

            log::error("Keyword run update failed: " . $e->getMessage(), [
                'keyword_id' => $request->input('keyword_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Something went wrong: " . $e->getMessage()
            ], 500);
        }
    }


    public function keywordMakeLive(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordPerformanceMakeLive);
        $userId = Auth::id() ?? null;
        $marketTz = config('timezone.market');

        $selectedWeek = $request->date
            ?? Carbon::now($marketTz)->subDay()->toDateString();

        $selectedDate  = Carbon::parse($selectedWeek, $marketTz)->startOfDay();
        $today         = Carbon::now($marketTz)->startOfDay();
        $threeDaysAgo  = Carbon::now($marketTz)->subDays(2)->startOfDay();

        // Allow today, yesterday, or day before yesterday
        if ($selectedDate->between($threeDaysAgo, $today)) {
            try {
                // Update pending keywords
                $updated = AmzKeywordRecommendation::where('run_update', true)
                    ->where('run_status', 'pending')
                    ->whereDate('date', $selectedWeek)
                    ->update(['run_status' => 'dispatched']);

                if ($updated === 0) {
                    return redirect()->back()
                        ->with('error', "No keywords were eligible for dispatch.");
                }

                // Dispatch job (date-based, same as campaigns)
                AmzKeywordPerformanceUpdatesJob::dispatch($selectedWeek, $userId);

                return redirect()->back()
                    ->with('success', "The keywords are moved to queue and will be updated soon.");
            } catch (\Exception $e) {

                Log::error("Failed to dispatch keyword update: " . $e->getMessage(), [
                    'date' => $selectedWeek,
                ]);

                return redirect()->back()
                    ->with('error', "Something went wrong while queuing keywords. Please try again.");
            }
        }

        return redirect()->back()
            ->with('error', "Selected date is not today or yesterday, no action taken.");
    }


    public function bulkBidUpdate(Request $request)
    {
        try {
            if (! $request->boolean('bulkBid')) {
                return back()->with('error', 'Bulk bid flag missing.');
            }

            // Allow "none" OR numeric percentage
            $data = $request->validate([
                'update_type' => ['required', 'in:increase,decrease'],
                'percentage'  => ['required'], // handle "none" manually below
            ]);

            $percentageRaw = (string) $request->input('percentage');
            $isReset       = $percentageRaw === 'none';
            $multiplier    = 1.0;
            $pct           = 0.0;

            if (! $isReset) {
                $pct = (float) $percentageRaw;

                if (! is_numeric($percentageRaw) || $pct < 0 || $pct > 100) {
                    return back()->with('error', 'Percentage must be a number between 0 and 100, or "none".');
                }

                $multiplier = $data['update_type'] === 'increase'
                    ? (1 + $pct / 100)
                    : (1 - $pct / 100);
            }

            // Exclude form-only fields from filters
            $reqForFilter = $request->except([
                '_token',
                'bulkBid',
                'update_type',
                'percentage',
                'run_update',
                'run_status'
            ]);

            $fake   = new Request($reqForFilter);
            $shared = $this->getFilteredKeywordsQuery($fake);
            $count  = 0;

            $idQuery = (clone $shared)
                ->toBase()
                ->reorder()
                ->selectRaw('amz_keyword_recommendations.id as kr_id')
                ->distinct()
                ->orderBy('amz_keyword_recommendations.id');

            $idQuery->chunkById(5000, function ($rows) use (&$count, $isReset, $multiplier) {

                $ids = collect($rows)->pluck('kr_id')->all();
                if (empty($ids)) {
                    return;
                }

                $count += count($ids);

                $update = [
                    'run_status' => 'pending',
                    'updated_at' => now(),
                ];

                if ($isReset) {
                    // Reset manual bid override
                    $update['manual_bid'] = null;
                } else {
                    $update['manual_bid'] = DB::raw(
                        "GREATEST(0, COALESCE(bid,0) * {$multiplier})"
                    );
                }

                DB::table('amz_keyword_recommendations')
                    ->whereIn('id', $ids)
                    ->update($update);
            }, 'amz_keyword_recommendations.id', 'kr_id');

            $msg = $isReset
                ? "Manual bid reset for {$count} keywords."
                : "Manual bid updated for {$count} keywords ({$data['update_type']} {$pct}%).";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {

            Log::error('bulkBidUpdate failed', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->except(['_token']),
            ]);

            return back()->with('error', 'Bulk bid update failed: ' . $e->getMessage());
        }
    }


    public function keywordPerformanceExport(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordPerformanceExcelExport);

        $marketTz   = config('timezone.market');
        $date       = $request->input('date', Carbon::now($marketTz)->startOfDay()->subDay()->toDateString());

        return Excel::download(new KeywordRecommendationsExport($request), "keyword_recommendations_{$date}.xlsx");
    }

    public function mergeKeywordAsins($keywords)
    {
        return $keywords->groupBy('keyword_id')->map(function ($group) {
            $first = $group->first();

            $allAsins = $group->pluck('asin')->filter()->unique()->values()->all();

            $related = $group->pluck('related_asin')->filter()->map(function ($item) {
                if (is_array($item)) {
                    return array_map(function ($v) {
                        return json_decode($v, true) ?: $v;
                    }, $item);
                } elseif (is_string($item)) {
                    return json_decode($item, true) ?: [$item];
                }
                return [];
            })->flatten()->unique()->values()->all();

            $first->related_asin = array_values(array_unique(array_merge($allAsins, $related)));

            return $first;
        })->values();
    }

    public function targetPerformance(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsTargetPerformance);
        $marketTz     = config('timezone.market');
        $selectedDate = $request->date ?? Carbon::now($marketTz)->subDay()->toDateString();
        $query = AmzTargetRecommendation::query()
            ->leftJoin('amz_ads_targets_performance_report_sd as sd', function ($join) use ($selectedDate) {
                $join->on('amz_target_recommendations.targeting_id', '=', 'sd.targeting_id')
                    ->where('amz_target_recommendations.campaign_types', 'SD')
                    ->whereDate('sd.c_date', $selectedDate);
            })
            ->leftJoin('amz_ads_targets_performance_report_sb as sb', function ($join) use ($selectedDate) {
                $join->on('amz_target_recommendations.targeting_id', '=', 'sb.targeting_id')
                    ->where('amz_target_recommendations.campaign_types', 'SB')
                    ->whereDate('sb.c_date', $selectedDate);
            })
            ->leftJoin('amz_ads_keyword_performance_report as sp', function ($join) use ($selectedDate) {
                $join->on('amz_target_recommendations.targeting_id', '=', 'sp.keyword_id')
                    ->where('amz_target_recommendations.campaign_types', 'SP')
                    ->whereDate('sp.c_date', $selectedDate);
            })
            ->select(
                'amz_target_recommendations.*',
                DB::raw('COALESCE(sd.cost, sb.cost, sp.cost) as spend'),
                DB::raw('COALESCE(sd.impressions, sb.impressions, sp.impressions) as impressions'),
                DB::raw('COALESCE(sd.clicks, sb.clicks, sp.clicks) as clicks')
            );

        // 🔎 Search filter
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('amz_target_recommendations.campaign_id', 'like', $search)
                    ->orWhere('amz_target_recommendations.targeting_id', 'like', $search)
                    ->orWhere('amz_target_recommendations.targeting_text', 'like', $search)
                    ->orWhere('amz_target_recommendations.clicks', 'like', $search)
                    ->orWhere('amz_target_recommendations.acos', 'like', $search)
                    ->orWhere('sd.targeting_text', 'like', $search)
                    ->orWhere('sb.targeting_text', 'like', $search);
            });
        }

        // Country filter
        if ($request->country && $request->country !== 'all') {
            $query->where('amz_target_recommendations.country', $request->country);
        }

        // Campaign type filter
        if ($request->campaign && $request->campaign !== 'all') {
            $query->where('amz_target_recommendations.campaign_types', $request->campaign);
        }

        // Date filter (default: yesterday in market timezone)
        $marketTz     = config('timezone.market');
        $selectedDate = $request->date ?? Carbon::now($marketTz)->startOfDay()->subDay()->toDateString();

        $query->whereDate('amz_target_recommendations.date', $selectedDate)
            ->orderByDesc('amz_target_recommendations.total_sales');

        // Paginate with query params preserved
        $targets = $query->paginate($request->input('per_page', 25));

        return view('pages.admin.amzAds.performance.targets', compact('targets', 'selectedDate'));
    }

    public function targetPerformanceExport(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsTargetPerformanceExport);
        $marketTz = config('timezone.market');
        $date     = $request->input('date', Carbon::now($marketTz)->startOfDay()->subDay()->toDateString());
        return Excel::download(
            new TargetRecommendationsExport($date),
            "target_recommendations_{$date}.xlsx"
        );
    }

    public function productAsins(Request $request)
    {
        $query   = $request->input('q', '');
        $page    = max((int) $request->input('page', 1), 1);
        $perPage = 20;
        $asinsQuery = ProductAsins::query()
            ->select('asin1 as text')
            ->distinct();

        if ($query) {
            $asinsQuery->where('asin1', 'like', "%{$query}%");
        }
        $results = $asinsQuery
            ->orderBy('text')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        $more = $results->count() > $perPage;
        $results = $results->take($perPage)->map(fn($asin) => [
            'id'   => $asin->text,
            'text' => $asin->text
        ]);
        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $more]
        ]);
    }

    public function showLogs(Request $request, $type = null, $date = null)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsLogs);

        try {
            $perPage = 20;

            $search = $request->input('search');

            $filterType = $request->filled('type')
                ? $request->input('type')
                : ($type ?: 'all');


            $campaignTypeInput = $request->input('campaign');

            if (blank($campaignTypeInput)) {
                $typeOptions = 'SP';
            } elseif ($campaignTypeInput === 'all') {
                $typeOptions = null;
            } else {
                $typeOptions = $campaignTypeInput;
            }


            $fallbackDate = now(config('timezone.market'))->subDay()->toDateString();

            $filterDate = $request->filled('date')
                ? $request->input('date')
                : ($date ?: $fallbackDate);

            $logsQuery = AmzPerformanceChangeLog::query()
                ->leftJoin('users as executed_users', 'executed_users.id', '=', 'amz_performance_change_logs.user_id')
                ->leftJoin('users as reverted_users', 'reverted_users.id', '=', 'amz_performance_change_logs.reverted_by')
                ->when($filterType !== 'all', function ($q) use ($filterType) {
                    $q->where('amz_performance_change_logs.change_type', $filterType);
                })
                ->when($filterDate, function ($q) use ($filterDate) {
                    $q->whereDate('amz_performance_change_logs.date', $filterDate);
                })
                ->when($typeOptions, function ($q) use ($typeOptions) {
                    $q->where('amz_performance_change_logs.type', $typeOptions);
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('amz_performance_change_logs.campaign_id', 'like', "%{$search}%")
                            ->orWhere('amz_performance_change_logs.keyword_id', 'like', "%{$search}%")
                            ->orWhere('amz_performance_change_logs.target_id', 'like', "%{$search}%")
                            ->orWhere('amz_performance_change_logs.change_type', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('amz_performance_change_logs.executed_at')
                ->select([
                    'amz_performance_change_logs.id',
                    'amz_performance_change_logs.campaign_id',
                    'amz_performance_change_logs.keyword_id',
                    'amz_performance_change_logs.target_id',
                    'amz_performance_change_logs.change_type',
                    'amz_performance_change_logs.country',
                    'amz_performance_change_logs.date',
                    'amz_performance_change_logs.old_value',
                    'amz_performance_change_logs.new_value',
                    'amz_performance_change_logs.type',
                    'amz_performance_change_logs.user_id',
                    DB::raw("DATE_FORMAT(amz_performance_change_logs.executed_at, '%d %b %Y, %h:%i %p') as executed_at_formatted"),
                    DB::raw("DATE_FORMAT(amz_performance_change_logs.revert_executed_at, '%d %b %Y, %h:%i %p') as revert_executed_at_formatted"),
                    'amz_performance_change_logs.run_update',
                    'amz_performance_change_logs.run_status',
                    'amz_performance_change_logs.reverted_by',
                    'executed_users.name as executed_by_name',
                    'reverted_users.name as reverted_by_name',
                ]);

            $logs = $logsQuery
                ->paginate($perPage)
                ->appends($request->query());

            return view('pages.admin.amzAds.performance.logs', [
                'logs'         => $logs,
                'type'         => $filterType,
                'date'         => $filterDate,
                'search'       => $search,
                'campaignType' => $typeOptions,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching performance logs', [
                'type'    => $type,
                'date'    => $date,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Something went wrong while loading logs.');
        }
    }


    public function runPerformanceLogUpdate(Request $request)
    {

        // $this->authorize(AmzAdsEnum::AmazonAdsLogsRunUpdate);

        try {
            $runUpdate = (bool) $request->input('run_update', true);

            $search     = $request->input('search');
            $filterType = $request->input('type', 'all');   // 'all' | 'campaign' | 'keyword'
            $filterDate = $request->input('date');          // 'YYYY-MM-DD'


            if ($request->boolean('bulk')) {

                $query = AmzPerformanceChangeLog::query()
                    ->when($filterType !== 'all', fn($q) => $q->where('change_type', $filterType))
                    ->when($filterDate, fn($q) => $q->whereDate('date', $filterDate))
                    ->when($search, function ($q) use ($search) {
                        $q->where(function ($sub) use ($search) {
                            $sub->where('campaign_id', 'like', "%{$search}%")
                                ->orWhere('keyword_id', 'like', "%{$search}%")
                                ->orWhere('target_id', 'like', "%{$search}%")
                                ->orWhere('change_type', 'like', "%{$search}%");
                        });
                    })
                    // don't touch dispatched rows (your UI disables them)
                    ->where(function ($q) {
                        $q->whereNull('run_status')->orWhere('run_status', '!=', 'dispatched');
                    })
                    ->whereNull('deleted_at');

                $count = 0;

                $query->select('id')
                    ->orderBy('id')
                    ->chunkById(5000, function ($rows) use ($runUpdate, &$count) {

                        $ids = $rows->pluck('id')->all();
                        if (empty($ids)) {
                            return;
                        }

                        $count += count($ids);

                        AmzPerformanceChangeLog::whereIn('id', $ids)
                            ->update([
                                'run_update' => $runUpdate,
                                'run_status' => 'pending',
                                'updated_at' => now(),
                            ]);
                    });

                return redirect()->back()
                    ->with('success', "Bulk run update applied to {$count} logs across all pages.");
            }


            $logId = $request->input('log_id');

            if (!$logId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No log ID provided.',
                ], 422);
            }

            $log = AmzPerformanceChangeLog::query()
                ->select('id', 'run_status')
                ->whereNull('deleted_at')
                ->find($logId);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log not found.',
                ], 404);
            }

            if ($log->run_status === 'dispatched') {
                return response()->json([
                    'success' => false,
                    'message' => 'This log is already dispatched and cannot be modified.',
                ], 409);
            }

            $updated = AmzPerformanceChangeLog::where('id', $logId)
                ->update([
                    'run_update' => $runUpdate,
                    'run_status' => 'pending',
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes were applied.',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Log run update updated successfully.',
            ]);
        } catch (\Throwable $e) {

            Log::error('Performance log run update failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
            ]);

            if ($request->boolean('bulk')) {
                return redirect()->back()->with('error', 'Something went wrong.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function performanceLogsMakeRevertLive(Request $request)
    {
        // Optional: add a permission
        // $this->authorize(AmzAdsEnum::AmazonAdsLogsRevertMakeLive);

        $marketTz = config('timezone.market');

        $selectedDate = $request->date ?? Carbon::now($marketTz)->subDay()->toDateString();

        $selectedDay  = Carbon::parse($selectedDate, $marketTz)->startOfDay();
        $today        = Carbon::now($marketTz)->startOfDay();
        $threeDaysAgo = Carbon::now($marketTz)->subDays(2)->startOfDay();

        // Allow today, yesterday, day before yesterday (same as your MakeLive)
        if (!$selectedDay->between($threeDaysAgo, $today)) {
            return redirect()->back()->with('error', 'Selected date is not allowed.');
        }

        // filter type from UI (optional): all | campaign | keyword
        $filterType = $request->input('type', 'all');

        try {
            $query = AmzPerformanceChangeLog::query()
                ->where('run_update', true)
                ->where('run_status', 'pending')
                ->whereDate('date', $selectedDate);

            if ($filterType !== 'all') {
                $query->where('change_type', $filterType);
            }

            // Only revert logs that have an old_value
            $query->whereNotNull('old_value');

            $updated = $query->update([
                'run_status' => 'dispatched',
                'updated_at' => now(),
            ]);

            if ($updated === 0) {
                return redirect()->back()->with('error', 'No logs were eligible for revert dispatch.');
            }

            // pass user id into job (Auth::id() is not reliable inside queued jobs)
            $userId = $request->user()?->id;

            AmzPerformanceRevertJob::dispatch($selectedDate, $filterType, $userId);

            return redirect()->back()->with('success', "Revert queued for {$updated} logs. It will process shortly.");
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch performance revert: {$e->getMessage()}", [
                'date' => $selectedDate,
                'type' => $filterType,
            ]);

            return redirect()->back()->with('error', 'Something went wrong while queuing revert.');
        }
    }
}
