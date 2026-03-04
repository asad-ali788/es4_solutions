<?php

namespace App\Jobs\Ads;

use App\Models\AmzCampaignsSd;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncListCampaignsSd implements ShouldQueue
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

        $count = 100;     // page size
        $startIndex = 0;  // offset

        $chunk = [];
        $maxEmptyPages = 3;
        $emptyPages = 0;

        Log::channel('ads')->info("Sync List Campaigns SD Started", [
            'country' => $this->country,
            'profileId' => $profileId,
            'count' => $count,
        ]);

        while (true) {
            $filter = [
                'count'       => $count,
                'startIndex'  => $startIndex,
                'stateFilter' => 'enabled,paused',
            ];

            $response = $amazonAdsService->listSDCampaigns($filter, $profileId);

            $raw = (string) ($response['response'] ?? '');
            $campaigns = $raw ? json_decode($raw, true) : [];

            if (!is_array($campaigns)) {
                Log::channel('ads')->error("SD campaigns response not array", [
                    'country' => $this->country,
                    'startIndex' => $startIndex,
                ]);
                break;
            }

            $fetched = count($campaigns);

            // Log::channel('ads')->info("Fetched SD campaigns page", [
            //     'country' => $this->country,
            //     'startIndex' => $startIndex,
            //     'count' => $count,
            //     'fetched' => $fetched,
            //     'sample' => $campaigns[0] ?? null,
            // ]);

            if ($fetched === 0) {
                $emptyPages++;
                if ($emptyPages >= $maxEmptyPages) break;
                // move forward anyway just in case
                $startIndex += $count;
                continue;
            }

            $emptyPages = 0;

            foreach ($campaigns as $campaign) {
                if (!isset($campaign['campaignId'])) {
                    continue;
                }

                $chunk[] = [
                    'country'        => $this->country,
                    'campaign_id'    => (int) $campaign['campaignId'],
                    'campaign_name'  => $campaign['name'] ?? null,
                    'campaign_type'  => 'SD',
                    'targeting_type' => $campaign['tactic'] ?? null,
                    'daily_budget'   => $campaign['budget'] ?? null,
                    'start_date'     => $campaign['startDate'] ?? null,
                    'campaign_state' => $campaign['state'] ?? null,
                    'portfolio_id'   => $campaign['portfolioId'] ?? null,
                    'added'          => now(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                if (count($chunk) >= 2000) {
                    $this->upsertChunk($chunk);
                }
            }

            // next page
            $startIndex += $count;

            // if API returns less than requested, it's the last page
            if ($fetched < $count) {
                break;
            }

            usleep(200000);
        }

        if (!empty($chunk)) {
            $this->upsertChunk($chunk);
        }

        Log::channel('ads')->info("Sync List Campaigns SD Finished", [
            'country' => $this->country,
        ]);
    }

    private function upsertChunk(array &$chunk): void
    {
        $count = count($chunk);

        AmzCampaignsSd::upsert(
            $chunk,
            ['campaign_id', 'country'], // unique keys
            ['campaign_name', 'campaign_type', 'targeting_type', 'daily_budget', 'start_date', 'campaign_state', 'portfolio_id', 'added', 'updated_at']
        );

        // Log::channel('ads')->info("Inserted/Updated {$count} SD campaigns via model.");
        $chunk = [];
    }
}
