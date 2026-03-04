<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsProductsSd;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncListProductAdsSd implements ShouldQueue
{
    use Queueable;

    protected string $country;

    /**
     * Create a new job instance.
     */
    public function __construct(string $country)
    {
        $this->country = $country;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $amazonAdsService = app(AmazonAdsService::class);

            $filter = [
                'stateFilter' => 'enabled,paused,archived',
                'count'       => 50,
                'startIndex'  => 0,
            ];

            $response = $amazonAdsService->listProductAdsSd(
                $filter,
                config("amazon_ads.profiles.{$this->country}")
            );

            
            $ads = json_decode($response['response'] ?? '[]', true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('ads')->error("Failed to decode Product Ads response for {$this->country}", [
                    'error'    => json_last_error_msg(),
                    'response' => $response,
                ]);
                return;
            }

            if (empty($ads)) {
                Log::channel('ads')->info("No Product Ads found for {$this->country}");
                return;
            }

        
            $data = [];
            foreach ($ads as $ad) {
                $data[] = [
                    'ad_id'            => $ad['adId'],
                    'country'         => $this->country,
                    'state'           => $ad['state'] ?? null,
                    'ad_group_id'       => $ad['adGroupId'] ?? null,
                    'campaign_id'      => $ad['campaignId'] ?? null,
                    'ad_name'          => $ad['adName'] ?? null,
                    'asin'            => $ad['asin'] ?? null,
                    'sku'             => $ad['sku'] ?? null,
                    'landing_page_url'  => $ad['landingPageURL'] ?? null,
                    'landing_page_type' => $ad['landingPageType'] ?? null,
                    'updated_at'      => now(),
                    'created_at'      => now(), 
                ];
            }

            AmzAdsProductsSd::upsert(
                $data,
                ['ad_id', 'country'], 
                [
                    'state',
                    'ad_group_id',
                    'campaign_id',
                    'ad_name',
                    'asin',
                    'sku',
                    'landing_page_url',
                    'landing_page_type',
                    'updated_at',
                ]
            );

            Log::channel('ads')->info("Product Ads synced successfully for {$this->country}", [
                'count' => count($data),
            ]);

        } catch (\Throwable $e) {
            Log::channel('ads')->error("Error syncing Product Ads SD for {$this->country}", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
