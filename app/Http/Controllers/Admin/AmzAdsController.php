<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Models\AmzKeywordUpdate as ModelsAmzKeywordUpdate;
use App\Http\Controllers\Controller;
use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordSb;
use App\Models\AmzAdsProducts;
use App\Models\AmzAdsProductsSb;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzCampaignUpdates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AmzAdsCampaignsUnderSchedule;
use App\Models\AmzCampaignsSd;
use App\Models\AmzTargetingClauses;
use App\Models\AmzTargetsSd;
use App\Models\ProductAsins;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AmzAdsController extends Controller
{
    public function campaigns(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaigns);
        try {
            $campaignType = 'SP';
            // Default: Enabled (unless user explicitly selects something)
            // Allowed values: enabled | paused | all
            $statusFilter = strtolower((string) $request->query('statusFilter', 'enabled'));

            $state = match ($statusFilter) {
                'all'    => ['ENABLED', 'PAUSED'], // keep your existing "All" meaning
                'paused' => ['PAUSED'],
                default  => ['ENABLED'],           // default
            };
            // Base query with all filters
            $query = AmzCampaigns::query();
            $query = $this->campaignSearch($request, $query, $campaignType)
                ->whereIn('campaign_state', $state);
            // Paginate after filters applied
            $campaigns = $query
                ->withExists([
                    'keywords as keywords_exists' => function ($q) {
                        $q->where('state', 'ENABLED');
                    },
                ])
                ->paginate((int) $request->query('per_page', 25));

            // Use IDs only from the current page
            $campaignIds = $campaigns->getCollection()->pluck('campaign_id')->filter()->values();
            // Fetch latest pending updates for displayed campaigns
            $pendingUpdates = AmzCampaignUpdates::query()
                ->where('campaign_type', $campaignType)
                ->where('status', 'pending')
                ->whereIn('campaign_id', $campaignIds)
                ->get()
                ->keyBy('campaign_id');
            $scheduleUpdates = AmzAdsCampaignsUnderSchedule::query()
                ->where('campaign_status', 'Enabled')
                ->whereIn('campaign_id', $campaignIds)
                ->get()
                ->keyBy('campaign_id');
            $lastUpdated = AmzCampaigns::max('updated_at');

            return view('pages.admin.amzAds.data.campaign', compact(
                'campaigns',
                'campaignType',
                'pendingUpdates',
                'scheduleUpdates',
                'lastUpdated'
            ));
        } catch (\Throwable $e) {
            Log::error('Error in campaigns(): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Something went wrong while fetching SP campaigns.');
        }
    }

    public function campaignKeywords(Request $request, $id, $type)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaignRelatedKeywords);
        $search   = $request->input('search');
        $spTarget = false;

        if ($type === 'SP') {
            $campaign = AmzCampaigns::where('campaign_id', $id)->first();

            if ($campaign && $campaign->targeting_type === "AUTO") {
                $query    = AmzTargetingClauses::where('campaign_id', $id);
                $spTarget = true;
            } else {
                $query = AmzAdsKeywords::where('campaign_id', $id);
            }

            $this->applySearch($query, $search, 'keyword_id');
        } elseif ($type === 'SB') {
            $query = AmzAdsKeywordSb::where('campaign_id', $id);
            $this->applySearch($query, $search, 'keyword_id');
        } elseif ($type === 'SD') {
            $query = AmzTargetsSd::where('campaign_id', $id);
            $this->applySearch($query, $search, 'target_id');
        } else {
            abort(404, 'Invalid campaign type');
        }

        $keywords = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->appends(['search' => $search]);

        return view('pages.admin.amzAds.data.campaignKeywords', compact(
            'keywords',
            'id',
            'type',
            'spTarget'
        ));
    }

    /**
     * Reusable search filter.
     */
    private function applySearch($query, $search, $column)
    {
        if (!empty($search)) {
            $query->where($column, 'LIKE', "%{$search}%");
        }
    }


    public function campaignsSb(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaigns);
        try {
            $campaignType = 'SB';
            // Default: enabled (unless user selects otherwise)
            // allowed: enabled | paused | all
            $statusFilter = strtolower((string) $request->query('statusFilter', 'enabled'));

            $states = match ($statusFilter) {
                'all'    => ['ENABLED', 'PAUSED'],
                'paused' => ['PAUSED'],
                default  => ['ENABLED'],
            };
            // Base query + filters
            $query = AmzCampaignsSb::query();
            $query = $this->campaignSearch($request, $query, $campaignType)
                ->whereIn('campaign_state', $states);

            // Paginate after filtering
            $campaigns = $query->withExists([
                'keywordsSb as keywords_exists' => function ($q) {
                    $q->where('state', 'ENABLED');
                },
            ])->paginate((int) $request->query('per_page', 25));

            $campaignIds = $campaigns->getCollection()->pluck('campaign_id')->filter()->values();
            // Fetch latest pending updates for displayed campaigns
            $pendingUpdates = AmzCampaignUpdates::query()
                ->where('campaign_type', $campaignType)
                ->where('status', 'pending')
                ->whereIn('campaign_id', $campaignIds)
                ->get()
                ->keyBy('campaign_id');

            $scheduleUpdates = AmzAdsCampaignsUnderSchedule::query()
                ->where('campaign_status', 'Enabled')
                ->whereIn('campaign_id', $campaignIds)
                ->get()
                ->keyBy('campaign_id');
            $lastUpdated = AmzCampaignsSb::max('updated_at');
            return view('pages.admin.amzAds.data.campaign', compact(
                'campaigns',
                'campaignType',
                'pendingUpdates',
                'scheduleUpdates',
                'lastUpdated'
            ));
        } catch (\Throwable $e) {
            Log::error('Error in campaignsSb(): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Something went wrong while fetching SB campaigns.');
        }
    }

    public function campaignsSd(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaigns);
        try {
            $campaignType = 'SD';

            // Default: enabled (unless user selects otherwise)
            // allowed: enabled | paused | all
            $statusFilter = strtolower((string) $request->query('statusFilter', 'enabled'));

            $states = match ($statusFilter) {
                'all'    => ['ENABLED', 'PAUSED'],
                'paused' => ['PAUSED'],
                default  => ['ENABLED'],
            };
            // Base query + filters
            $query = AmzCampaignsSd::query();
            $query = $this->campaignSearch($request, $query, $campaignType)
                ->whereIn('campaign_state', $states);
            // Paginate after filtering
            $campaigns = $query
                ->withExists('targetSb')
                ->paginate((int) $request->query('per_page', 25));

            // IDs only from current page
            $campaignIds = $campaigns->getCollection()->pluck('campaign_id')->filter()->values();

            // Fetch latest pending updates for displayed campaigns
            $pendingUpdates = AmzCampaignUpdates::query()
                ->where('campaign_type', $campaignType)
                ->where('status', 'pending')
                ->whereIn('campaign_id', $campaignIds)
                ->get()
                ->keyBy('campaign_id');

            $scheduleUpdates = AmzAdsCampaignsUnderSchedule::query()
                ->where('campaign_status', 'Enabled')
                ->whereIn('campaign_id', $campaignIds)
                ->get()
                ->keyBy('campaign_id');
            $lastUpdated = AmzCampaignsSd::max('updated_at');

            return view('pages.admin.amzAds.data.campaign', compact(
                'campaigns',
                'campaignType',
                'pendingUpdates',
                'scheduleUpdates',
                'lastUpdated'
            ));
        } catch (\Throwable $e) {
            Log::error('Error in campaignsSd(): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Something went wrong while fetching SD campaigns.');
        }
    }

    private function campaignSearch(Request $request, $query, string $campaignType)
    {
        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->whereAny([
                    'campaign_name',
                    'campaign_id',
                    'daily_budget',
                ], 'like', $search);
            });
        }
        // Country filter
        if ($request->filled('country') && $request->country !== 'all') {
            $query->where('country', strtoupper($request->country));
        }
        // Update filter
        if ($request->get('updateFilter') === 'pending') {
            $pendingIds = AmzCampaignUpdates::where('campaign_type', $campaignType)
                ->where('status', 'pending')
                ->distinct()
                ->pluck('campaign_id');

            $query->whereIn('campaign_id', $pendingIds);
        }
        return $query;
    }
    // Update the campaign to a tem table to track the update
    public function campaignUpdate(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaignUpdate);
        $request->validate([
            'campaign_id'   => 'required|string',
            'campaign_type' => 'required|string|in:SB,SP,SD',
            'new_budget'    => 'nullable|numeric',
            'new_status'    => 'nullable|string',
        ]);

        try {
            $campaign = null;
            $type = strtolower($request->campaign_type);

            if ($type === 'sp') {
                $campaign = AmzCampaigns::where('campaign_id', $request->campaign_id)->first();
            } elseif ($type === 'sb') {
                $campaign = AmzCampaignsSb::where('campaign_id', $request->campaign_id)->first();
            } elseif ($type === 'sd') {
                $campaign = AmzCampaignsSd::where('campaign_id', $request->campaign_id)->first();
            } else {
                throw new \Exception("Unhandled campaign type: {$request->campaign_type}");
            }

            if (!$campaign) {
                return back()->with('error', "Campaign not found for ID {$request->campaign_id}");
            }

            AmzCampaignUpdates::create([
                'campaign_id'   => $request->campaign_id,
                'campaign_type' => strtoupper($request->campaign_type),
                'old_budget'    => $campaign->daily_budget,
                'new_budget'    => $request->new_budget,
                'old_status'    => $campaign->campaign_state,
                'new_status'    => $request->new_status,
                'country'       => $campaign->country,
                'updated_by'    => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Campaign update logged successfully');
        } catch (\Throwable $e) {
            Log::error("Error in Campaign Update: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', "Something went wrong while updating the campaign.");
        }
    }

    public function campaignCreate(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaignCreate);
        return view('pages.admin.amzAds.data.campaign.form', [
            'campaignType' => strtoupper($request->get('type', 'SP')),
        ]);
    }


    public function keywordsSp(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywords);

        try {
            $keywordType = 'SP';

            // Default: enabled | allowed: enabled, paused, all
            $statusFilter = strtolower((string) $request->query('statusFilter', 'enabled'));

            $states = match ($statusFilter) {
                'all'    => ['ENABLED', 'PAUSED'],
                'paused' => ['PAUSED'],
                default  => ['ENABLED'],
            };

            $query = AmzAdsKeywords::query();
            $query = $this->keywordSearch($request, $query, $keywordType)
                ->whereIn('state', $states);

            $keywords = $query->paginate((int) $request->query('per_page', 50));

            $keywordIds = $keywords->getCollection()
                ->pluck('keyword_id')
                ->filter()
                ->values();

            $pendingUpdates = ModelsAmzKeywordUpdate::query()
                ->where('keyword_type', $keywordType)
                ->where('status', 'pending')
                ->whereIn('keyword_id', $keywordIds)
                ->get()
                ->keyBy('keyword_id');

            $lastUpdated = AmzAdsKeywords::max('updated_at');

            return view('pages.admin.amzAds.data.keyword', [
                'keywords'       => $keywords,
                'keywordType'    => $keywordType,
                'pendingUpdates' => $pendingUpdates,
                'lastUpdated'    => $lastUpdated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in keywordsSp(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Something went wrong while fetching SP keywords.');
        }
    }

    public function keywordsSb(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywords);

        try {
            $keywordType = 'SB';

            // Default: enabled | allowed: enabled, paused, all
            $statusFilter = strtolower((string) $request->query('statusFilter', 'enabled'));

            $states = match ($statusFilter) {
                'all'    => ['ENABLED', 'PAUSED'],
                'paused' => ['PAUSED'],
                default  => ['ENABLED'],
            };

            $query = AmzAdsKeywordSb::query();
            $query = $this->keywordSearch($request, $query, $keywordType)
                ->whereIn('state', $states);

            $keywords = $query->paginate((int) $request->query('per_page', 50));

            $keywordIds = $keywords->getCollection()
                ->pluck('keyword_id')
                ->filter()
                ->values();

            $pendingUpdates = ModelsAmzKeywordUpdate::query()
                ->where('keyword_type', $keywordType)
                ->where('status', 'pending')
                ->whereIn('keyword_id', $keywordIds)
                ->get()
                ->keyBy('keyword_id');

            $lastUpdated = AmzAdsKeywordSb::max('updated_at');

            return view('pages.admin.amzAds.data.keyword', [
                'keywords'       => $keywords,
                'keywordType'    => $keywordType,
                'pendingUpdates' => $pendingUpdates,
                'lastUpdated'    => $lastUpdated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in keywordsSb(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Something went wrong while fetching SB keywords.');
        }
    }


    private function keywordSearch(Request $request, $query, $keywordType)
    {
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->whereAny([
                    'keyword_text',
                    'keyword_id',
                    'campaign_id',
                    'ad_group_id',
                    'bid',
                    'match_type',
                ], 'like',  $search);
            });
        }

        if ($request->filled('country') && $request->country !== 'all') {
            $query->where('country', strtoupper($request->country));
        }
        // Update filter
        if ($request->get('updateFilter') === 'pending') {
            $pendingIds = ModelsAmzKeywordUpdate::where('keyword_type', $keywordType)
                ->where('status', 'pending')
                ->distinct()
                ->pluck('keyword_id');

            $query->whereIn('keyword_id', $pendingIds);
        }
        return $query;
    }

    public function keywordsUpdate(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordUpdate);
        $request->validate([
            'keyword_id'   => 'required|string',
            'ad_group_id'  => 'required|string',
            'campaign_id'  => 'required|string',
            'keyword_type' => 'required|string|in:SB,SP',
            'new_bid'      => 'nullable|numeric',
            'new_state'    => 'nullable|string|in:ENABLED,PAUSED',
        ]);
        try {
            // pick the right model based on type
            if (strtolower($request->keyword_type) === 'sp') {
                $keyword = AmzAdsKeywords::where('keyword_id', $request->keyword_id)->first();
            } else {
                // return back()->with('error', 'Unable to update the SB keywords right now. Please try again later.');
                $keyword = AmzAdsKeywordSb::where('keyword_id', $request->keyword_id)->first();
            }

            ModelsAmzKeywordUpdate::create([
                'keyword_id'   => $request->keyword_id,
                'ad_group_id'  => $request->ad_group_id,
                'campaign_id'  => $request->campaign_id,
                'keyword_type' => strtoupper($request->keyword_type),
                'old_bid'      => $keyword->bid,
                'new_bid'      => $request->new_bid,
                'old_state'    => $keyword->state,
                'new_state'    => $request->new_state,
                'country'      => $keyword->country,
                'updated_by'   => auth()->id(),
            ]);

            return back()->with('success', 'Keyword update logged successfully 🚀');
        } catch (\Throwable $e) {
            Log::error("Error in Keyword Update: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', "Something went wrong while updating the keyword.");
        }
    }

    public function campaignAsinsSp(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaigns);
        $query = AmzAdsProducts::select('asin', 'country', 'state')
            ->groupBy('asin', 'country', 'state');
        if ($request->filled('search')) {
            $query->where('asin', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('country') && $request->country !== 'all') {
            $query->where('country', $request->country);
        }
        $asins = $query->paginate($request->get('per_page', 10));

        $asins->getCollection()->transform(function ($asinRow) {
            $campaigns = AmzCampaigns::whereIn('campaign_id', function ($q) use ($asinRow) {
                $q->select('campaign_id')
                    ->from('amz_ads_products')
                    ->where('asin', $asinRow->asin)
                    ->where('country', $asinRow->country);
            })
                ->where('campaign_state', 'ENABLED')
                ->get(['campaign_id', 'campaign_name', 'campaign_state', 'daily_budget', 'targeting_type']);

            $asinRow->campaigns = $campaigns;
            return $asinRow;
        });
        return view('pages.admin.amzAds.data.campaignByAsinSp', compact('asins'));
    }
    public function allKeywordsAsin(Request $request, $asin)
    {
        $perPage = (int) $request->get('per_page', 10);
        $match   = $request->get('match', 'all');        // all | match | not_match | reco_not_existing
        $table   = true;
        // 1) ASIN manual enabled campaigns
        $campaignIdsSub = DB::table('amz_campaigns as c')
            ->join('amz_ads_products as p', function ($join) use ($asin) {
                $join->on('p.campaign_id', '=', 'c.campaign_id')
                    ->where('p.asin', '=', $asin);
            })
            ->where('c.targeting_type', 'MANUAL')
            ->where('c.campaign_state', 'ENABLED')
            ->select('c.campaign_id')
            ->distinct();

        /**
         * ✅ reco_not_existing:
         * Return ONLY recommended keywords (campaign_keyword_recommendations)
         * for this ASIN campaigns that do NOT exist in amz_ads_keywords.
         * Output only: keyword, bid
         */
        if ($match === 'reco_not_existing') {

            // existing keywords (normalized) for this ASIN campaigns
            $existingKeywordsSub = DB::table('amz_ads_keywords as k2')
                ->joinSub($campaignIdsSub, 'cp2', function ($join) {
                    $join->on('cp2.campaign_id', '=', 'k2.campaign_id');
                })
                ->selectRaw('LOWER(TRIM(k2.keyword_text))')
                ->distinct();

            // Build ranked recommendations: 1 row per keyword (highest bid wins)
            $rankedRecoSub = DB::table('campaign_keyword_recommendations as r')
                ->joinSub($campaignIdsSub, 'cp', function ($join) {
                    $join->on('cp.campaign_id', '=', 'r.campaign_id');
                })
                ->whereNotIn(DB::raw('LOWER(TRIM(r.keyword))'), $existingKeywordsSub);

            // search inside recommendations (apply BEFORE ranking)
            if ($request->filled('search')) {
                $rankedRecoSub->where('r.keyword', 'like', '%' . trim($request->search) . '%');
            }

            $rankedRecoSub->selectRaw("
                TRIM(r.keyword) as keyword,
                r.bid as bid,
                r.match_type as match_type,
                r.campaign_id as campaign_id,
                r.ad_group_id as ad_group_id,
                ROW_NUMBER() OVER (
                    PARTITION BY LOWER(TRIM(r.keyword))
                    ORDER BY r.bid DESC
                ) as rn
            ");

            // Outer query: keep only rn = 1 (distinct keyword)
            $recoQuery = DB::query()
                ->fromSub($rankedRecoSub, 'x')
                ->where('x.rn', 1)
                ->select([
                    'x.keyword',
                    'x.bid',
                    'x.match_type',
                    'x.campaign_id',
                    'x.ad_group_id',
                ]);

            $keywords = $recoQuery
                ->orderBy('keyword')
                ->paginate($perPage)
                ->appends($request->query());
            $table   = false;

            return view('pages.admin.amzAds.data.allkeywordsAsin', compact('keywords', 'asin', 'match', 'table'));
        }



        /**
         * Default: existing keywords table (amz_ads_keywords)
         */
        $keywordsQuery = DB::table('amz_ads_keywords as k')
            ->joinSub($campaignIdsSub, 'cp', function ($join) {
                $join->on('cp.campaign_id', '=', 'k.campaign_id');
            })
            ->select([
                'k.id',
                'k.keyword_id',
                'k.campaign_id',
                'k.keyword_text',
                'k.match_type',
                'k.bid',
                'k.state as keyword_state',
                DB::raw("
                EXISTS (
                    SELECT 1
                    FROM campaign_keyword_recommendations r
                    WHERE r.campaign_id = k.campaign_id
                      AND LOWER(TRIM(r.keyword)) = LOWER(TRIM(k.keyword_text))
                ) AS has_reco
            "),
            ]);

        if ($match === 'match') {
            $keywordsQuery->whereRaw("
            EXISTS (
                SELECT 1
                FROM campaign_keyword_recommendations r
                WHERE r.campaign_id = k.campaign_id
                  AND LOWER(TRIM(r.keyword)) = LOWER(TRIM(k.keyword_text))
            )
        ");
        } elseif ($match === 'not_match') {
            $keywordsQuery->whereRaw("
            NOT EXISTS (
                SELECT 1
                FROM campaign_keyword_recommendations r
                WHERE r.campaign_id = k.campaign_id
                  AND LOWER(TRIM(r.keyword)) = LOWER(TRIM(k.keyword_text))
            )
        ");
        }

        if ($request->filled('search')) {
            $keywordsQuery->where('k.keyword_text', 'like', '%' . trim($request->search) . '%');
        }

        $keywords = $keywordsQuery
            ->orderByDesc('k.id')
            ->paginate($perPage)
            ->appends($request->query());

        return view('pages.admin.amzAds.data.allkeywordsAsin', compact('keywords', 'asin', 'match', 'table'));
    }




    public function campaignAsinsSb(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsDataCampaigns);
        $query = AmzAdsProductsSb::select('asin', 'country', 'state')
            ->groupBy('asin', 'country', 'state');
        if ($request->filled('search')) {
            $query->where('asin', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('country') && $request->country !== 'all') {
            $query->where('country', $request->country);
        }
        $asins = $query->paginate($request->get('per_page', 10));

        $asins->getCollection()->transform(function ($asinRow) {
            $campaigns = AmzCampaignsSb::whereIn('campaign_id', function ($q) use ($asinRow) {
                $q->select('campaign_id')
                    ->from('amz_ads_products_sb')
                    ->where('asin', $asinRow->asin)
                    ->where('country', $asinRow->country);
            })
                ->where('campaign_state', 'ENABLED')
                ->get(['campaign_id', 'campaign_name', 'campaign_state', 'daily_budget']);

            $asinRow->campaigns = $campaigns;
            return $asinRow;
        });

        return view('pages.admin.amzAds.data.campaignByAsinSb', compact('asins'));
    }

    public function targetsSd(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsTargets);
        try {
            $query      = AmzTargetsSd::query();
            $targetType = "SD";

            // Apply search & filter logic
            $query = $this->targetSearch($request, $query);

            // Only enabled targets
            $targets = $query->where('state', 'ENABLED')->paginate($request->get('per_page', 50));

            return view('pages.admin.amzAds.data.target', [
                'targets'    => $targets,
                'targetType' => $targetType,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error in targetsSd(): " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', "Something went wrong while fetching SD targets.");
        }
    }

    private function targetSearch(Request $request, $query)
    {
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('target_id', 'like', "%{$request->search}%")
                    ->orWhere('campaign_id', 'like', "%{$request->search}%")
                    ->orWhere('ad_group_id', 'like', "%{$request->search}%")
                    ->orWhere('bid', 'like', "%{$request->search}%")
                    ->orWhere('expression', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('country') && $request->country !== 'all') {
            $query->where('region', strtoupper($request->country));
        }

        return $query;
    }
}
