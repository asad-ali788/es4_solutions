<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzCampaignsSb;
use App\Models\KeywordSugSbVideo;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GetSbBidRecommendations extends Command
{
    protected $signature = 'app:get-sb-bid-recommendations';
    protected $description = 'ADS: Fetch SB Bid Recommendations [US/CA]';

    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);

        $campaigns = AmzCampaignsSb::select(
            'amz_campaigns_sb.*',
            'kws.keyword_text',
            'kws.match_type',
            'kws.keyword_id',
            'kws.ad_group_id'
        )
            ->join('amz_ads_keyword_sb as kws', 'kws.campaign_id', '=', 'amz_campaigns_sb.campaign_id')
            ->where('amz_campaigns_sb.campaign_state', 'ENABLED')
            ->get();

        foreach ($campaigns as $campaign) {
            $profileId = $this->getProfileId($campaign->country);
            if (!$profileId) continue;

            $keyword = trim((string) $campaign->keyword_text);
            if (!$keyword) continue;

            $payload = [
                "campaignId" => (int) $campaign->campaign_id,
                "keywords"   => [[
                    "matchType"   => strtolower($campaign->match_type ?? 'broad'),
                    "keywordText" => $keyword,
                ]],
                "targets"    => $targets ?? [],
                // "adFormat"   => "video"
                "adFormat"   => "productCollection"
            ];



            $response = $this->fetchWithRetries(
                fn() =>
                $amazonAdsService->getSponsoredBrandBidRecommendations($payload, $profileId)
            );

            if (
                empty($response['keywordsBidsRecommendationSuccessResults']) &&
                empty($response['targetsBidsRecommendationSuccessResults'])
            ) {
                continue;
            }

            $rows = $this->buildRows($response, $campaign);

            if ($rows) {
                KeywordSugSbVideo::upsert(
                    $rows,
                    ['campaign_id', 'ad_group_id', 'keyword_id'],
                    [
                        'keyword_text',
                        'match_type',
                        'key_bid_start',
                        'key_bid_end',
                        'key_bid_suggestion',
                        'target_bid_start',
                        'target_bid_end',
                        'target_bid_suggestion',
                        'country',
                        'added'
                    ]
                );
            }

            usleep(500_000); // avoid API throttling
        }

        return Command::SUCCESS;
    }

    private function getProfileId(string $country): ?string
    {
        return match (strtoupper($country)) {
            'US' => config('amazon_ads.profiles.US'),
            'CA' => config('amazon_ads.profiles.CA'),
            default => null,
        };
    }

    private function fetchWithRetries(callable $callback, int $maxAttempts = 3): ?array
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $callback();

                if (is_array($result)) {
                    return $result;
                }

                if (isset($result['response'])) {
                    $response = $result['response'];

                    // Decode only if it’s a JSON string
                    if (is_string($response)) {
                        $decoded = json_decode($response, true);
                        if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                            return $decoded;
                        }
                    }

                    // If it's already an array
                    if (is_array($response) && !empty($response)) {
                        return $response;
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('ads')->error(
                    "BidRecommendation attempt {$attempt} failed: " . $e->getMessage(),
                    ['trace' => $e->getTraceAsString()]
                );
            }

            sleep($attempt);
        }

        Log::channel('ads')->warning("BidRecommendation failed after {$maxAttempts} attempts - no valid data returned.");
        return null;
    }


    private function buildRows(array $response, $campaign): array
    {
        $rows = [];
        $date = Carbon::now()->format('Y-m-d');

        foreach ($response['keywordsBidsRecommendationSuccessResults'] ?? [] as $rec) {
            $rows[] = [
                'campaign_id'        => (string) $campaign->campaign_id,
                'ad_group_id'        => $campaign->ad_group_id,
                'keyword_id'         => $campaign->keyword_id,
                'keyword_text'       => $rec['keyword']['keywordText'] ?? null,
                'match_type'         => $rec['keyword']['matchType'] ?? null,
                'key_bid_start'      => $rec['recommendedBid']['rangeStart'] ?? null,
                'key_bid_end'        => $rec['recommendedBid']['rangeEnd'] ?? null,
                'key_bid_suggestion' => $rec['recommendedBid']['recommended'] ?? null,
                'target_bid_start'   => null,
                'target_bid_end'     => null,
                'target_bid_suggestion' => null,
                'country'            => $campaign->country,
                'added'              => $date,
            ];
        }

        foreach ($response['targetsBidsRecommendationSuccessResults'] ?? [] as $rec) {
            $rows[] = [
                'campaign_id'           => (string) $campaign->campaign_id,
                'ad_group_id'           => $campaign->ad_group_id,
                'keyword_id'            => null,
                'keyword_text'          => null,
                'match_type'            => null,
                'key_bid_start'         => null,
                'key_bid_end'           => null,
                'key_bid_suggestion'    => null,
                'target_bid_start'      => $rec['recommendedBid']['rangeStart'] ?? null,
                'target_bid_end'        => $rec['recommendedBid']['rangeEnd'] ?? null,
                'target_bid_suggestion' => $rec['recommendedBid']['recommended'] ?? null,
                'country'               => $campaign->country,
                'added'                 => $date,
            ];
        }

        return $rows;
    }
}
