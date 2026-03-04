<?php

namespace App\Jobs\Ads;

use App\Services\Api\AmazonAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductsAdGroups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $country) {}

    public function handle(AmazonAdsService $amazonAdsService): void
    {
        ini_set('memory_limit', '1024M');

        $profileId = config("amazon_ads.profiles.{$this->country}");

        if (!$profileId) {
            Log::channel('ads')->error("❌ Missing profile ID for {$this->country}");
            return;
        }

        $filter = [
            'stateFilter' => ['include' => ['ENABLED', 'PAUSED']],
            'includeExtendedDataFields' => true,
            'maxResults' => 1000,
        ];

        $nextToken = null;
        $totalFetched = 0;
        $emptyResponseCount = 0;
        $maxEmptyResponses = 5;
        $chunk = [];

        Log::channel('ads')->info("🚀 Syncing Ad Groups for country: {$this->country}");

        do {
            if ($nextToken) {
                $filter['nextToken'] = $nextToken;
            }

            $response = $amazonAdsService->listAdGroups($filter, $profileId);
            $responseBody = $response['response'] ?? null;

            if (empty($responseBody)) {
                if (++$emptyResponseCount >= $maxEmptyResponses) break;
                continue;
            }

            $data = json_decode($responseBody, true);

            if (!is_array($data)) {
                logger()->warning("⚠️ Invalid response format for {$this->country}");
                return;
            }

            $emptyResponseCount = 0;
            $adGroups = $data['adGroups'] ?? [];
            $nextToken = $data['nextToken'] ?? null;

            if (empty($adGroups)) continue;

            foreach ($adGroups as $group) {
                $chunk[] = [
                    'country'       => $this->country,
                    'campaign_id'   => $group['campaignId'],
                    'ad_group_id'   => $group['adGroupId'],
                    'gr_name'       => $group['name'] ?? null,
                    'gr_state'      => $group['state'] ?? null,
                    'default_bid'   => $group['defaultBid'] ?? null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                    'added'         => now(),
                ];

                // Flush chunk every 2000 rows
                if (count($chunk) >= 2000) {
                    DB::table('amz_ads_groups')->upsert(
                        $chunk,
                        ['ad_group_id', 'country'],
                        ['campaign_id', 'gr_name', 'gr_state', 'default_bid', 'updated_at']
                    );
                    $chunk = [];
                }
            }

            $totalFetched += count($adGroups);
            usleep(200000); // Throttle

        } while (!empty($nextToken));

        // Insert remaining data
        if (!empty($chunk)) {
            DB::table('amz_ads_groups')->upsert(
                $chunk,
                ['ad_group_id', 'country'],
                ['campaign_id', 'gr_name', 'gr_state', 'default_bid', 'updated_at']
            );
        }

        Log::channel('ads')->info("✅ Ad Groups Sync Completed for {$this->country}. Total synced: {$totalFetched}");
    }
}
