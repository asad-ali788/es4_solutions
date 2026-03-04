<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsGroupsSd;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductsAdGroupsSd implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $country) {}

    public function handle(AmazonAdsService $amazonAdsService): void
    {
        ini_set('memory_limit', '1024M');

        $profileId = config("amazon_ads.profiles.{$this->country}");
        if (!$profileId) {
            Log::channel('ads')->error("Missing profile ID for {$this->country}");
            return;
        }

        $filter = [
            'count'       => 100,
            'stateFilter' => 'enabled,paused',
        ];

        $startIndex = 0;
        $chunk      = [];

        Log::channel('ads')->info("Sync Ad Groups SD Started for {$this->country}");

        do {
            $filter['startIndex'] = $startIndex;

            $response = $amazonAdsService->listSDAdGroups($filter, $profileId);

            $adGroups = json_decode($response['response'] ?? '[]', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('ads')->error("JSON decode error for AdGroups {$this->country}: " . json_last_error_msg());
                $adGroups = [];
            }

            Log::channel('ads')->info("Fetched " . count($adGroups) . " ad groups for {$this->country} at startIndex {$startIndex}");

            foreach ($adGroups as $adGroup) {
                $chunk[] = [
                    'country'         => $this->country,
                    'ad_group_id'     => $adGroup['adGroupId'],
                    'campaign_id'     => $adGroup['campaignId'] ?? null,
                    'name'            => $adGroup['name'] ?? null,
                    'default_bid'     => $adGroup['defaultBid'] ?? null,
                    'bid_optimization' => $adGroup['bidOptimization'] ?? null,
                    'state'           => $adGroup['state'] ?? null,
                    'tactic'          => $adGroup['tactic'] ?? null,
                    'creative_type'   => $adGroup['creativeType'] ?? null,
                    'added'           => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                if (count($chunk) >= 2000) {
                    $this->upsertChunk($chunk);
                }
            }

            $startIndex += 100;
        } while (!empty($adGroups));

        if (!empty($chunk)) {
            $this->upsertChunk($chunk);
        }

        Log::channel('ads')->info("Sync Ad Groups SD Finished for {$this->country}");
    }

    private function upsertChunk(array &$chunk): void
    {
        $count = count($chunk);

        AmzAdsGroupsSd::upsert(
            $chunk,
            ['ad_group_id', 'country'],
            ['name', 'campaign_id', 'default_bid', 'bid_optimization', 'state', 'tactic', 'creative_type', 'added', 'updated_at']
        );

        Log::channel('ads')->info("Inserted/Updated {$count} SD Ad Groups via model.");
        $chunk = [];
    }
}
