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

class SyncListProductAds implements ShouldQueue
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
            'maxResults'                => 1000,
            'stateFilter'               => ['include' => ['ENABLED']],
            'includeExtendedDataFields' => true,
        ];

        $nextToken          = null;
        $emptyResponseCount = 0;
        $maxEmptyResponses  = 5;

        Log::channel('ads')->info("Sync List Product Ads Started for " . $this->country);

        do {
            if ($nextToken) {
                $filter['nextToken'] = $nextToken;
            }

            $response     = $amazonAdsService->listProductAds($filter, $profileId);
            $responseBody = $response['response'] ?? null;

            if (empty($responseBody)) {
                if (++$emptyResponseCount >= $maxEmptyResponses) break;
                continue;
            }

            $emptyResponseCount = 0;
            $data = json_decode($responseBody, true);
            $productAds = $data['productAds'] ?? [];
            $nextToken = $data['nextToken'] ?? null;

            if (empty($productAds)) continue;

            $chunk = [];
            foreach ($productAds as $productAd) {
                $chunk[] = [
                    'country'     => $this->country,
                    'campaign_id' => $productAd['campaignId'],
                    'ad_group_id' => $productAd['adGroupId'] ?? null,
                    'ad_id'       => $productAd['adId'] ?? null,
                    'asin'        => $productAd['asin'] ?? null,
                    'sku'         => $productAd['sku'] ?? null,
                    'state'       => $productAd['state'] ?? null,
                    'added'       => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            foreach (array_chunk($chunk, 1000) as $batch) {
                DB::table('amz_ads_products')->upsert(
                    $batch,
                    ['ad_group_id', 'country'],
                    ['campaign_id', 'ad_id', 'asin', 'sku', 'state', 'added', 'updated_at']
                );
            }

            usleep(200000); // 200ms
        } while (!empty($nextToken));

        Log::channel('ads')->info("Sync List Product Ads Finished for " . $this->country);
    }
}
