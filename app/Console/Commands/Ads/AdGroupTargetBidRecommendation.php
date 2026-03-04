<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsGroups;
use App\Models\AmzCampaigns;
use App\Models\SpTargetSugByAdgroup;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdGroupTargetBidRecommendation extends Command
{
    protected $signature = 'app:adgroup-target-bid-recommendation';
    protected $description = 'Fetch bid recommendations for ad groups from Amazon Ads API';

    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);
        Log::channel('ads')->info('✅ Ad Group Bid Recommendation Started');

        AmzCampaigns::where('campaign_state', 'ENABLED')
            ->where('targeting_type', 'AUTO')
            ->cursor()
            ->each(function ($campaign) use ($amazonAdsService) {
                $profileId = match (strtoupper($campaign->country)) {
                    'US' => config('amazon_ads.profiles.US'),
                    'CA' => config('amazon_ads.profiles.CA'),
                    default => null,
                };

                if (!$profileId) {
                    return;
                }

                AmzAdsGroups::where('campaign_id', $campaign->campaign_id)
                    ->cursor()
                    ->each(function ($adGroup) use ($campaign, $amazonAdsService, $profileId) {
                        $payload = [
                            'adGroupId'          => $adGroup->ad_group_id,
                            'campaignId'         => $campaign->campaign_id,
                            'recommendationType' => 'BIDS_FOR_EXISTING_AD_GROUP',
                            'targetingExpressions' => [
                                ['type' => 'CLOSE_MATCH'],
                                ['type' => 'LOOSE_MATCH'],
                                ['type' => 'SUBSTITUTES'],
                                ['type' => 'COMPLEMENTS'],
                            ],
                        ];

                        $data = $amazonAdsService->getThemeBasedBidRecommendationForAdGroup($payload, $profileId);

                        if (empty($data['success']) || empty($data['response'])) {
                            return;
                        }

                        $response = json_decode($data['response'], true);

                        foreach ($response['bidRecommendations'] ?? [] as $bidGroup) {
                            $theme = $bidGroup['theme'] ?? null;

                            foreach ($bidGroup['bidRecommendationsForTargetingExpressions'] ?? [] as $targetExp) {
                                $targetingExp = $targetExp['targetingExpression'] ?? [];

                                $keywordText = $targetingExp['value'] ?? $targetingExp['type'] ?? null;
                                $matchType   = $targetingExp['type'] ?? null;
                                $targetType  = $targetingExp['type'] ?? null;

                                $bids = collect($targetExp['bidValues'] ?? [])->pluck('suggestedBid')->values();

                                $rangeStart  = $bids->get(0, 0);
                                $rangeMedian = $bids->get(1, 0);
                                $rangeEnd    = $bids->get(2, 0);

                                $targetId = DB::table('amz_targeting_clauses as atc')
                                    ->join('amz_ads_groups as adg', 'atc.ad_group_id', '=', 'adg.ad_group_id')
                                    ->where('atc.ad_group_id', $adGroup->ad_group_id)
                                    // ->where('atc.campaign_id', $campaign->campaign_id)
                                    ->value('atc.target_id');

                                try {
                                    SpTargetSugByAdgroup::updateOrCreate(
                                        [
                                            'ad_group_id'  => $adGroup->ad_group_id,
                                            'campaign_id'  => $campaign->campaign_id,
                                            'keyword_text' => $keywordText,
                                            'match_type'   => $matchType,
                                            'theme'        => $theme,
                                            'target_type'  => $targetType,
                                        ],
                                        [
                                            'bid_start'  => $rangeStart,
                                            'bid_median' => $rangeMedian,
                                            'bid_end'    => $rangeEnd,
                                            'country'    => $campaign->country,
                                            'target_id'  => $targetId,
                                        ]
                                    );
                                } catch (\Throwable $e) {
                                    Log::channel('ads')->error("❌ Failed to save recommendation", [
                                        'ad_group_id' => $adGroup->ad_group_id,
                                        'keyword'     => $keywordText,
                                        'error'       => $e->getMessage(),
                                    ]);
                                }
                            }
                        }

                        // small delay to avoid API throttling
                        usleep(200000); // 200ms
                    });
            });

        Log::channel('ads')->info('✅ Ad Group Bid Recommendation Completed');
        return Command::SUCCESS;
    }
}
