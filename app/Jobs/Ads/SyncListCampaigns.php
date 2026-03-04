<?php

namespace App\Jobs\Ads;

use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncListCampaigns implements ShouldQueue
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
            'maxResults' => 1000,
            'stateFilter' => [
                'include' => ['ENABLED', 'PAUSED']
            ],
            'includeExtendedDataFields' => true,
        ];

        $nextToken          = null;
        $emptyResponseCount = 0;
        $maxEmptyResponses  = 5;
        $chunk              = [];

        Log::channel('ads')->info("Sync List Campaings Started for " . $this->country);

        do {
            if ($nextToken) {
                $filter['nextToken'] = $nextToken;
            }

            $response     = $amazonAdsService->listCampaigns($filter,$profileId);
            $responseBody = $response['response'] ?? null;
            
            if (empty($responseBody)) {
                if (++$emptyResponseCount >= $maxEmptyResponses) break;
                continue;
            }

            $emptyResponseCount = 0;
            $data               = json_decode($responseBody, true);
            $campaigns          = $data['campaigns'] ?? [];
            $nextToken          = $data['nextToken'] ?? null;

            if (empty($campaigns)) continue;

            foreach ($campaigns as $campaign) {
                $chunk[] = [
                    'country'        => $this->country,
                    'campaign_id'    => $campaign['campaignId'],
                    'portfolio_id'   => $campaign['portfolioId'] ?? null,
                    'campaign_name'  => $campaign['name'] ?? null,
                    'campaign_type'  => 'SP',
                    'targeting_type' => $campaign['targetingType'] ?? null,
                    'daily_budget'   => $campaign['budget']['budget'] ?? null,
                    'start_date'     => $campaign['startDate'] ?? null,
                    'campaign_state' => $campaign['state'] ?? null,
                    'added'          => now(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                // Save every 2000 rows
                if (count($chunk) >= 2000) {
                    DB::table('amz_campaigns')->upsert(
                        $chunk,
                        ['campaign_id', 'country'],
                        ['campaign_name', 'portfolio_id', 'campaign_type', 'targeting_type', 'daily_budget', 'start_date', 'campaign_state', 'added', 'updated_at']
                    );
                    $chunk = [];
                }
            }

            usleep(200000);
        } while (!empty($nextToken));

        // Save leftover records < 2000
        if (!empty($chunk)) {
            DB::table('amz_campaigns')->upsert(
                $chunk,
                ['campaign_id', 'country'],
                ['campaign_name', 'portfolio_id', 'campaign_type', 'targeting_type', 'daily_budget', 'start_date', 'campaign_state', 'added', 'updated_at']
            );
        }
        Log::channel('ads')->info("Sync List Campaings Finished for " . $this->country);
    }
}
