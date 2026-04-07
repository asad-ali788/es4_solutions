<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\SellingEnum;
use App\Http\Controllers\Controller;
use App\Services\Ads\AdsOverviewService;
use App\Services\Seller\SellingAdsItemService;
use App\Traits\HasFilteredAdsPerformance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SellingAdsItemController extends Controller
{
    protected SellingAdsItemService $service;
    use HasFilteredAdsPerformance;
    public function __construct(SellingAdsItemService $service, protected AdsOverviewService $adsOverviewService)
    {
        $this->service = $service;
    }
    public function index(Request $request)
    {
        $this->authorize(SellingEnum::SellingAdsItem);
        try {
            $data = $this->service->getAsinsForIndex($request);

            return view('pages.admin.sellingAdsItem.index', $data);
        } catch (\Throwable $e) {
            Log::error('Error fetching Ads Items ASIN selling items: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Something went wrong while fetching Ads Items.');
        }
    }

    public function details(Request $request, string $asin)
    {
        $this->authorize(SellingEnum::SellingAdsItemDashboard);

        try {
            $marketTz     = config('timezone.market');
            $selectedDate = $request->input('date')
                ?? Carbon::now($marketTz)->subDay()->toDateString();

            $campaign          = $request->input('campaign', 'SP');
            $spTargetingType   = $request->input('sp_targeting_type', 'MANUAL');
            $perPage           = (int) $request->input('per_page', 25);

            $request->merge([
                'period'            => '1d',
                'asins'             => [$asin],
                'campaign'          => $campaign,
                'sp_targeting_type' => $spTargetingType,
            ]);
            $cacheKey = sprintf(
                'asin_campaign_details:%s:%s:%s:%s:%d',
                $asin,
                $selectedDate,
                $campaign,
                $spTargetingType,
                $perPage
            );
            $result = Cache::remember($cacheKey, now()->addHour(), function () use (
                $request,
                $selectedDate,
                $asin,
                $perPage
            ) {
                $query = $this->getFilteredCampaignsQuery($request);

                return $this->service->buildAsinCampaignDetails(
                    query: $query,
                    selectedDate: $selectedDate,
                    asin: $asin,
                    perPage: $perPage
                );
            });

            $campaigns = $result['campaigns'];

            return view('pages.admin.sellingAdsItem.show', compact('campaigns', 'selectedDate', 'asin'));
        } catch (\Throwable $e) {
            Log::error('Error in Selling Ads Item', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->with('error', 'An error occurred while loading the Ads Item Dashboard.');
        }
    }

    public function keywordsDetail(Request $request, string $asin)
    {
        $this->authorize(SellingEnum::SellingAdsItemDashboard);

        try {
            $marketTz     = config('timezone.market');
            $selectedDate = $request->input('date')
                ?? Carbon::now($marketTz)->subDay()->toDateString();
            $campaign        = $request->input('campaign', 'SP');
            $spTargetingType = $request->input('sp_targeting_type', 'MANUAL');
            $perPage         = (int) $request->input('per_page', 25);

            $request->merge([
                'period'            => '1d',
                'asins'             => [$asin],
                'campaign'          => $campaign,
                'sp_targeting_type' => $spTargetingType,
            ]);
            // Cache key includes asin + date + filters + perPage to avoid incorrect reuse
            $cacheKey = sprintf(
                'asin_keyword_details:%s:%s:%s:%s:%d',
                $asin,
                $selectedDate,
                $campaign,
                $spTargetingType,
                $perPage
            );

            $result = Cache::remember($cacheKey, now()->addHour(), function () use (
                $request,
                $selectedDate,
                $asin,
                $perPage
            ) {
                $query = $this->getFilteredKeywordsQuery($request);

                return $this->service->buildAsinKeywordDetails(
                    query: $query,
                    selectedDate: $selectedDate,
                    asin: $asin,
                    perPage: $perPage
                );
            });

            $keywords = $result['keywords'];

            return view('pages.admin.sellingAdsItem.keywordshow', compact('keywords', 'selectedDate', 'asin'));
        } catch (\Throwable $e) {
            Log::error('Error in Selling Ads Item Keywords Detail', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'An error occurred while loading the Ads Item Dashboard.');
        }
    }

    public function createKeywords(Request $request)
    {
        $data = $request->validate([
            'campaign_id'   => ['required', 'integer'],
            'country'       => ['required', 'string'],
            'campaign_type' => ['required', 'string'],
        ]);

        if (($data['campaign_type'] ?? null) !== 'SP') {
            return back()->with('warning', 'Currently, only SP keyword creation is supported.');
        }

        $result = $this->service->createSpKeywordsFromLatestRecommendations(
            campaignId: (int) $data['campaign_id'],
            country: strtoupper($data['country'])
        );

        if ($result['ok']) {
            return back()->with('success', $result['message'] ?? 'Keywords created successfully.');
        }

        return back()->with('error', $result['message'] ?? 'An error occurred while creating keywords.');
    }

    public function storeGenerated(Request $request, SellingAdsItemService $sellingAdsItemService)
    {
        $data = $request->validate([
            'asin'               => ['required', 'string'],
            'campaign_type'      => ['required', 'string'],
            'sku'                => ['required', 'string'],
            'country'            => ['required', 'string'],
            'total_budget'       => ['required', 'numeric'],
            'targeting_type'     => ['required', 'string'],
            'match_type'         => ['required', 'string'],
            'pst_date'           => ['required', 'string'],
            'campaigns'          => ['required', 'array', 'min:1'],
            'campaigns.*.name'   => ['required', 'string'],
            'campaigns.*.budget' => ['required', 'numeric'],
        ]);

        //choose profileId based on user/country
        $profileId = $sellingAdsItemService->resolveProfileId($data['country']);
        $results = [
            'success' => [],
            'failed'  => [],
        ];
        // Set marketplace min budget (configure per marketplace if needed)
        $minDailyBudget = 1.00;

        foreach ($data['campaigns'] as $index => $c) {
            $rowNo = $index + 1;

            $campaignId = null;
            $adGroupId  = null;

            try {
                $budget = (float) $c['budget'];

                Log::info('SP AUTO create flow started', [
                    'asin'   => $data['asin'],
                    'row'    => $rowNo,
                    'name'   => $c['name'],
                    'budget' => $budget,
                    'sku'    => $data['sku'],
                ]);

                // 1) Campaign (+ retry on duplicate CMP)
                $camp = $sellingAdsItemService->spCreateAutoCampaignWithRetry(
                    $profileId,
                    $c['name'],
                    $budget,
                    [
                        'maxAttempts'   => 4,
                        'minDailyBudget' => $minDailyBudget,
                    ]
                );

                if (!$camp['ok']) {
                    throw new \RuntimeException('Campaign: ' . implode(' | ', $camp['messages']));
                }

                $campaignId = $camp['id'];
                $finalName  = $camp['name_used'] ?? $c['name'];

                // 2) AdGroup
                $adGroupName = $data['asin'] . '_AdGroup_' . $rowNo;

                $ag = $sellingAdsItemService->spCreateAdGroup(
                    $profileId,
                    $campaignId,
                    $adGroupName,
                    0.10
                );

                if (!$ag['ok']) {
                    throw new \RuntimeException('AdGroup: ' . implode(' | ', $ag['messages']));
                }

                $adGroupId = $ag['id'];

                // 3) Product Ad
                $pa = $sellingAdsItemService->spCreateProductAd(
                    $profileId,
                    $campaignId,
                    $adGroupId,
                    $data['asin'],
                    $data['sku']
                );

                if (!$pa['ok']) {
                    throw new \RuntimeException('ProductAd: ' . implode(' | ', $pa['messages']));
                }

                $adId = $pa['id'];

                $results['success'][] = [
                    'row'        => $rowNo,
                    'campaignId' => $campaignId,
                    'adGroupId'  => $adGroupId,
                    'adId'       => $adId,
                    'name'       => $finalName,
                    'budget'     => $budget,
                ];
                $sellingAdsItemService->upsertCreatedProductAdsAndCampaigns($data, $results['success']);

                Log::info('SP AUTO create flow completed', [
                    'row'        => $rowNo,
                    'campaignId' => $campaignId,
                    'adGroupId'  => $adGroupId,
                    'adId'       => $adId,
                ]);
            } catch (\Throwable $e) {

                // ✅ COMPENSATION (rollback best-effort)
                // If adgroup created but productAd failed -> archive adgroup & campaign
                // If campaign created but adgroup failed -> archive campaign
                try {
                    if ($adGroupId) {
                        $sellingAdsItemService->archiveAdGroup($profileId, $adGroupId);
                    }
                } catch (\Throwable $x) {
                    Log::warning('Rollback: archiveAdGroup failed', [
                        'row' => $rowNo,
                        'adGroupId' => $adGroupId,
                        'error' => $x->getMessage(),
                    ]);
                }

                try {
                    if ($campaignId) {
                        $sellingAdsItemService->archiveCampaign($profileId, $campaignId);
                    }
                } catch (\Throwable $x) {
                    Log::warning('Rollback: archiveCampaign failed', [
                        'row' => $rowNo,
                        'campaignId' => $campaignId,
                        'error' => $x->getMessage(),
                    ]);
                }

                Log::error('SP AUTO create flow failed', [
                    'asin'  => $data['asin'],
                    'row'   => $rowNo,
                    'name'  => $c['name'],
                    'error' => $e->getMessage(),
                ]);

                $results['failed'][] = [
                    'row'   => $rowNo,
                    'name'  => $c['name'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Build toaster arrays (message only, no status words)
        $toastSuccess = array_map(function ($s) {
            return "Row {$s['row']}: Campaign {$s['campaignId']} · AdGroup {$s['adGroupId']} · Ad {$s['adId']} created";
        }, $results['success']);

        $toastErrors = array_map(function ($f) {
            return "Row {$f['row']} ({$f['name']}): {$f['error']}";
        }, $results['failed']);

        // Summary (optional – can be shown separately)
        $summary = 'Created: ' . count($results['success']) . ' | Failed: ' . count($results['failed']);

        return back()->with([
            'success'       => $summary,
            'toast_success' => $toastSuccess,
            'toast_errors'  => $toastErrors,
        ]);
    }
}
