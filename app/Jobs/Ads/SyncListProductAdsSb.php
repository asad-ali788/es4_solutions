<?php

namespace App\Jobs\Ads;

use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncListProductAdsSb implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $country) {}

    public function handle(AmazonAdsService $amazonAdsService): void
    {
        ini_set('memory_limit', '1024M');
        $profileId = config("amazon_ads.profiles.{$this->country}");
        if (!$profileId) {
            Log::channel('ads')->error("Missing profile ID for {$this->country} in Sync List Product Ads SB");
            return;
        }

        $filter = [
            'maxResults'  => 100,
            'stateFilter' => ['include' => ['ENABLED']],
        ];

        $nextToken          = null;
        $emptyResponseCount = 0;
        $maxEmptyResponses  = 5;
        $chunk              = [];

        Log::channel('ads')->info("Sync List Product Ads SB Started for " . $this->country);

        do {
            if ($nextToken) {
                $filter['nextToken'] = $nextToken;
            }

            $response     = $amazonAdsService->listProductAdsSb($filter, $profileId);
            $responseBody = $response['response'] ?? null;

            if (empty($responseBody)) {
                if (++$emptyResponseCount >= $maxEmptyResponses) break;
                continue;
            }

            $emptyResponseCount = 0;
            $data               = json_decode($responseBody, true);
            $productAds         = $data['ads'] ?? [];
            $nextToken          = $data['nextToken'] ?? null;

            if (empty($productAds)) continue;

            foreach ($productAds as $productAd) {

                $chunk[] = [
                    'country'       => $this->country,
                    'campaign_id'   => $productAd['campaignId'],
                    'ad_group_id'   => $productAd['adGroupId'] ?? null,
                    'ad_id'         => $productAd['adId'] ?? null,
                    'asin'          => $productAd['creative']['asins'][0] ?? null, //create a new column and store as []
                    'related_asins' => !empty($productAd['creative']['asins']) ? json_encode($productAd['creative']['asins']) : null,
                    'state'         => $productAd['state'] ?? null,
                    'added'         => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            if (count($chunk) >= 1000) {
                DB::table('amz_ads_products_sb')->upsert(
                    $chunk,
                    ['ad_group_id', 'country'],
                    ['campaign_id', 'ad_id', 'asin', 'related_asins', 'sku', 'state', 'added', 'updated_at']
                );
                $chunk = [];
            }

            usleep(200000); // 200ms
        } while (!empty($nextToken));
        // Insert any remaining <2000 records
        if (!empty($chunk)) {
            DB::table('amz_ads_products_sb')->upsert(
                $chunk,
                ['ad_group_id', 'country'],
                ['campaign_id', 'ad_id', 'asin', 'related_asins', 'sku', 'state', 'added', 'updated_at']
            );
            $chunk = [];
        }
        Log::channel('ads')->info("Sync List Product Ads SB Finished for " . $this->country);
    }
}
