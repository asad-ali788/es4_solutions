<?php

namespace App\Console\Commands\Ads;

use App\Models\CampaignKeywordRecommendation as ModelsCampaignKeywordRecommendation;
use App\Models\CampaignRecommendations;
use App\Services\Api\AmazonAdsService;
use App\Traits\HasFilteredAdsPerformance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignKeywordRecommendation extends Command
{
    use HasFilteredAdsPerformance;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-keyword-recommendation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Generate Campaign Keyword and Bid Recommendations';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $marketTz = config('timezone.market');
        $amazonAdsService = app(AmazonAdsService::class);

        Log::channel('ads')->info('✅ Campaign Keyword Recommendation Started');

        $selectedWeek = Carbon::now($marketTz)->subDay()->toDateString();

        $query = CampaignRecommendations::where('report_week', $selectedWeek)->where('acos_7d',0)->where('total_spend_7d', '>', 0)->where('campaign_types','SP')
            ->leftJoin('amz_campaigns as sp', 'campaign_recommendations.campaign_id', '=', 'sp.campaign_id')
            ->leftJoin('amz_ads_campaign_performance_report as performace', 'campaign_recommendations.campaign_id', '=', 'performace.campaign_id')
            ->select(
                'campaign_recommendations.campaign_id',
                'campaign_recommendations.campaign_types',
                'campaign_recommendations.country',
                'sp.targeting_type as sp_targeting_type',
                'performace.ad_group_id as ad_group_id',
            )
            ->whereNotNull('performace.ad_group_id')
            ->where('sp.targeting_type','MANUAL')
            ->groupBy(
                'campaign_recommendations.campaign_id',
                'performace.ad_group_id',
                'campaign_recommendations.campaign_types',
                'campaign_recommendations.country',
                'sp.targeting_type'
            );

        foreach ($query->cursor() as $data) {

            $profileId = match (strtoupper($data->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => null,
            };

            if (!$profileId) {
                Log::channel('ads')->warning("⚠️ No profile ID for {$data->country}, skipping campaign {$data->campaign_id}");
                continue;
            }

            $payload = [
                "adGroupId"          => (int) $data->ad_group_id,
                "campaignId"         => (int) $data->campaign_id,
                "recommendationType" => "KEYWORDS_FOR_ADGROUP",
                "locale"             => strtoupper($data->country) === 'CA' ? "en_CA" : "en_US",
                "maxRecommendations" => 10,
                "sortDimension"      => "DEFAULT",
            ];

            $apiResponse = $amazonAdsService->getRankedKeywordRecommendation($payload, $profileId);

            if (empty($apiResponse['success'])) {
                Log::channel('ads')->warning("⚠️ API failed for campaign {$data->campaign_id}, adgroup {$data->ad_group_id}");
                usleep(250000);
                continue;
            }

            $response = !empty($apiResponse['response'])
                ? json_decode($apiResponse['response'], true)
                : null;

            $keywordList = $response['keywordTargetList'] ?? [];

            if (empty($keywordList)) {
                usleep(250000);
                continue;
            }

            $now = now();
            $rows = [];

            foreach ($keywordList as $item) {
                $keyword = $item['keyword'] ?? null;
                if (!$keyword) {
                    continue;
                }

                $bidInfoList = $item['bidInfo'] ?? [];
                if (empty($bidInfoList)) {
                    // If you still want to store keyword without bidInfo, you can add a single row here.
                    continue;
                }

                foreach ($bidInfoList as $reco) {
                    if (($reco['matchType'] ?? null) !== 'BROAD') {
                        continue; // skip EXACT/PHRASE
                    }
                    $suggested = $reco['suggestedBid'] ?? [];

                    $rows[] = [
                        'campaign_id'     => (int) $data->campaign_id,
                        'ad_group_id'     => (int) $data->ad_group_id,
                        'keyword'         => $keyword,
                        'match_type'      => $reco['matchType'] ?? null,
                        'bid'             => isset($reco['bid']) ? ((float)$reco['bid'] / 100) : null,
                        'bid_start'       => isset($suggested['rangeStart'])  ? ((float)$suggested['rangeStart'] / 100)  : null,
                        'bid_suggestion'  => isset($suggested['rangeMedian']) ? ((float)$suggested['rangeMedian'] / 100) : null,
                        'bid_end'         => isset($suggested['rangeEnd'])    ? ((float)$suggested['rangeEnd'] / 100)    : null,
                        'country'         => $data->country,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            if (!empty($rows)) {
                ModelsCampaignKeywordRecommendation::upsert(
                    $rows,
                    ['country', 'campaign_id', 'ad_group_id', 'keyword', 'match_type'],
                    [
                        'bid',
                        'bid_start',
                        'bid_suggestion',
                        'bid_end',
                        'updated_at',
                    ]
                );
            }

            usleep(250000);
            gc_collect_cycles();
        }

        Log::channel('ads')->info('✅ Campaign Keyword Recommendation Completed');
    }
}
