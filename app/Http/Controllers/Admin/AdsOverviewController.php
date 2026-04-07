<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Ads\AdsOverviewService;
use App\Traits\HasFilteredAdsPerformance;

class AdsOverviewController extends Controller
{
    use HasFilteredAdsPerformance;
    public function __construct(protected AdsOverviewService $adsOverviewService) {}

    public function index(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignOverviewDashboard);
        $marketTz       = config('timezone.market');
        $asin           = $request->input('asin', null);
        $productName    = $request->input('product', null);
        $allowedPeriods = ['1d', '7d', '14d', '30d'];
        $period         = $request->get('period', '1d');

        if (!in_array($period, $allowedPeriods, true)) {
            $period = '1d';
        }
        // Map period -> window days (this is what you will pass)
        $windowDays = match ($period) {
            '7d'  => 7,
            '14d' => 14,
            '30d' => 30,
            default => 1, // 1d
        };

        // Start = today (market timezone)
        $startCarbon = request()->filled('date')
            ? Carbon::parse(request('date'), $marketTz)->startOfDay()
            : Carbon::now($marketTz)->subDay()->startOfDay();

        $start = $startCarbon->toDateString();

        // End = start - (windowDays - 1)
        $end = (clone $startCarbon)
            ->subDays($windowDays - 1)
            ->toDateString();

        // build cache key
        // keep it readable + stable + safe characters
        $asinPart  = $asin ? "asin:{$asin}" : "asin:all";
        $datePart  = "start:{$start}";
        $periodPart = "period:{$period}";
        $productPart = $productName ? "product:{$productName}" : "product:all";

        $cacheKey = "ads_overview|{$periodPart}|{$datePart}|{$asinPart}|{$productPart}";

        $overview = Cache::tags(['ads_overview'])->remember($cacheKey, 3600, function () use ($start, $windowDays, $asin, $productName) {
            $data = $this->adsOverviewService->getRangeSummaryFromReports($start, $windowDays, $asin, $productName);
            $data['total'] = $this->adsOverviewService->buildTotalsFromOverview($data);
            return $data;
        });
        // Direct call (no cache)
        // $overview          = $this->adsOverviewService->getRangeSummaryFromReports($start, $windowDays, $asin);
        // $overview['total'] = $this->adsOverviewService->buildTotalsFromOverview($overview);

        $this->checkAndFlashMissingTypes($overview);

        return view('pages.admin.amzAds.overview.overview', compact(
            'overview',
            'period',
            'start',
            'end',
            'asin',
        ));
    }

    public function campaignOverview(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignOverview);

        $query = $this->getFilteredCampaignsQuery($request);

        $campaigns = $query->orderBy('enabled_campaigns_count', 'desc')
            ->paginate($request->get('per_page', 25));

        $campaigns->getCollection()->transform(function ($campaign) {
            return $campaign;
        });

        $merged = $this->mergeCampaignAsins($campaigns->getCollection());
        $campaigns->setCollection($merged);

        return view('pages.admin.amzAds.overview.campaigns', compact(
            'campaigns',
        ));
    }

    public function cacheClear()
    {
        try {
            Cache::tags(['ads_overview'])->flush();
            return back()->with('success', 'Overview cache refreshed!');
        } catch (\Throwable $e) {
            Log::warning('Overview cache clear failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to refresh Overview.');
        }
    }

    /**
     * Detect missing ad types (SP, SB, SD)
     * and automatically set a toast message for the *current* request only.
     */
    private function checkAndFlashMissingTypes(array $overview): void
    {
        $missing = [];

        $spTotal = ($overview['counts']['SP']['AUTO'] ?? 0)
            + ($overview['counts']['SP']['MANUAL'] ?? 0);

        $sbTotal = $overview['counts']['SB'] ?? 0;
        $sdTotal = $overview['counts']['SD'] ?? 0;

        if ($spTotal === 0) $missing[] = 'SP';
        if ($sbTotal === 0) $missing[] = 'SB';
        if ($sdTotal === 0) $missing[] = 'SD';

        if (! empty($missing)) {
            $list = implode(', ', $missing);
            session()->now('no_data', "No data found for: {$list}");
        } else {
            session()->forget('no_data');
        }
    }

    public function keywordDashboard(Request $request)
    {
        try {
            $this->authorize(AmzAdsEnum::AmazonAdsKeywordOverviewDashboard);
            
            $marketTz = config('timezone.market');
            $asin     = $request->input('asin');
            $productName = $request->input('product', null);

            $allowedPeriods = ['1d', '7d', '14d', '30d'];
            $period = $request->get('period', '1d');

            if (!in_array($period, $allowedPeriods, true)) {
                $period = '1d';
            }

            $windowDays = match ($period) {
                '7d'  => 7,
                '14d' => 14,
                '30d' => 30,
                default => 1,
            };

            $maxDate       = Carbon::now($marketTz)->subDay()->toDateString();
            $requestedDate = $request->get('date', $maxDate);

            if ($requestedDate > $maxDate) {
                $requestedDate = $maxDate;
            }

            $snapshotCarbon = Carbon::parse($requestedDate, $marketTz)->startOfDay();
            $snapshotDate   = $snapshotCarbon->toDateString();

            $start = (clone $snapshotCarbon)
                ->subDays($windowDays - 1)
                ->toDateString();

            $end = $snapshotDate;

            $asinPart   = $asin ? "asin:{$asin}" : "asin:all";
            $datePart   = "snapshot:{$snapshotDate}";
            $periodPart = "period:{$period}";
            $productNamePart = $productName ? "product:{$productName}" : "product:all";

            $cacheKey = "ads_keyword_overview|{$periodPart}|{$datePart}|{$asinPart}|{$productNamePart}";

            $overview = Cache::tags(['ads_keyword_overview', 'ads_overview'])
                ->remember($cacheKey, 3600, function () use (
                    $snapshotDate,
                    $windowDays,
                    $asin,
                    $productName
                ) {
                    $data = $this->adsOverviewService->getKeywordRangeSummaryFromReports($snapshotDate, $windowDays, $asin, $productName);

                    $data['total'] = $this->adsOverviewService->buildKeywordTotalsFromOverview($data);

                    return $data;
                });

            $this->checkAndFlashMissingTypesKeyword($overview);

            return view('pages.admin.amzAds.overview.keyword.overview', compact('overview', 'period', 'start', 'end', 'asin'));
        } catch (\Throwable $e) {

            Log::error('Keyword Dashboard Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'request' => $request->all(),
            ]);

            return back()->with(
                'error',
                'Unable to load keyword dashboard. Please try again later.'
            );
        }
    }

    public function keywordOverview(Request $request)
    {
        try {
            $this->authorize(AmzAdsEnum::AmazonAdsKeywordOverview);

            $query = $this->getFilteredKeywordsQuery($request);

            $keywords = $query->orderByDesc('total_spend')->paginate($request->get('per_page', 25));

            $merged = $this->mergeKeywordAsins($keywords->getCollection());
            $keywords->setCollection($merged);

            return view('pages.admin.amzAds.overview.keyword.keywords', compact('keywords'));
        } catch (\Throwable $e) {
            Log::error('Error loading keyword overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Something went wrong while loading keyword overview.');
        }
    }

    public function keywordCacheClear()
    {
        try {
            Cache::tags(['ads_keyword_overview'])->flush();

            return back()->with('success', 'Keyword overview cache refreshed!');
        } catch (\Throwable $e) {
            Log::warning('Keyword overview cache clear failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to refresh keyword overview.');
        }
    }

    private function mergeKeywordAsins($keywords)
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

    private function checkAndFlashMissingTypesKeyword(array $overview): void
    {
        $missing = [];

        $spTotal = ($overview['counts']['SP'] ?? 0);
        $sbTotal = $overview['counts']['SB'] ?? 0;

        if ($spTotal === 0) $missing[] = 'SP';
        if ($sbTotal === 0) $missing[] = 'SB';

        if (! empty($missing)) {
            $list = implode(', ', $missing);
            session()->now('no_data', "No data found for: {$list}");
        } else {
            session()->forget('no_data');
        }
    }
}
