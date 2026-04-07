<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordSb;
use App\Services\Api\AmazonAdsService;
use Exception;
use App\Models\AmzKeywordUpdate as ModelAmzKeywordUpdates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AmzKeywordUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:amz-keyword-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Apply Pending Keyword Bid and Status Updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $amazonAdsService = app(AmazonAdsService::class);
            $pendingUpdates   = ModelAmzKeywordUpdates::where('status', 'pending')->get();

            Log::channel('ads')->info("✅ AmzKeywordUpdates Started");

            foreach ($pendingUpdates as $pendingUpdate) {
                // pick profileId based on country
                $profileId = match (strtoupper($pendingUpdate->country)) {
                    'US' => config('amazon_ads.profiles.US'),
                    'CA' => config('amazon_ads.profiles.CA'),
                    default => throw new Exception("Unhandled country: {$pendingUpdate->country}"),
                };

                // Sponsored Products (SP)
                if (strtoupper($pendingUpdate->keyword_type) === 'SP') {
                    $payload = [
                        'keywords' => [
                            [
                                'keywordId' => $pendingUpdate->keyword_id,
                                'bid'       => (float) $pendingUpdate->new_bid,
                                'state'     => strtoupper($pendingUpdate->new_state),
                            ]
                        ]
                    ];
                    $response = $amazonAdsService->updateKeywords($payload, $profileId);

                    if (!empty($response) && isset($response['success']) && $response['success'] === true) {
                        $pendingUpdate->status       = 'processed';
                        $pendingUpdate->api_response = json_encode($response);
                        $pendingUpdate->save();

                        $keyword = AmzAdsKeywords::where('country', $pendingUpdate->country)
                            ->where('keyword_id', $pendingUpdate->keyword_id)
                            ->first();

                        if ($keyword) {
                            $keyword->update([
                                'bid'           => $pendingUpdate->new_bid,
                                'keyword_state' => strtoupper($pendingUpdate->new_state),
                                'updated_at'    => now(),
                            ]);
                        } else {
                            Log::channel('ads')->warning("⚠️ SP Keyword {$pendingUpdate->keyword_id} not found in amz_ads_keywords table.");
                        }
                    } else {
                        $pendingUpdate->status = 'failed';
                        $pendingUpdate->api_response = json_encode($response);
                        $pendingUpdate->save();
                        Log::channel('ads')->error("❌ Failed to update SP keyword {$pendingUpdate->keyword_id}", $response ?? []);
                    }
                }

                // Sponsored Brands (SB)
                elseif (strtoupper($pendingUpdate->keyword_type) === 'SB') {
                    $payload = [[
                        'keywordId'  => (string) $pendingUpdate->keyword_id,
                        'adGroupId'  => (string) $pendingUpdate->ad_group_id,
                        'campaignId' => (string) $pendingUpdate->campaign_id,
                        'state'      => strtolower($pendingUpdate->new_state),
                        'bid'        => (float) $pendingUpdate->new_bid,
                    ]];

                    Log::channel('ads')->info("SB update payload", $payload);

                    $response = $amazonAdsService->updateSBKeywords($payload, $profileId);

                    Log::channel('ads')->info("SB update response", $response);

                    if (!empty($response) && isset($response['success']) && $response['success'] === true) {
                        $pendingUpdate->status       = 'processed';
                        $pendingUpdate->api_response = json_encode($response);
                        $pendingUpdate->save();

                        $keyword = AmzAdsKeywordSb::where('country', $pendingUpdate->country)
                            ->where('keyword_id', $pendingUpdate->keyword_id)
                            ->first();

                        if ($keyword) {
                            $keyword->update([
                                'bid'           => $pendingUpdate->new_bid,
                                'keyword_state' => strtolower($pendingUpdate->new_state),
                                'updated_at'    => now(),
                            ]);
                        } else {
                            Log::channel('ads')->warning("⚠️ SB Keyword {$pendingUpdate->keyword_id} not found in amz_ads_keyword_sb table.");
                        }
                    } else {
                        $pendingUpdate->status       = 'failed';
                        $pendingUpdate->api_response = json_encode($response);
                        $pendingUpdate->save();
                        Log::channel('ads')->error("❌ Failed to update SB keyword {$pendingUpdate->keyword_id}", $response ?? []);
                    }
                }

                sleep(3);
            }

            Log::channel('ads')->info("✅ AmzKeywordUpdates Completed");
        } catch (Exception $e) {
        }
    }
}
