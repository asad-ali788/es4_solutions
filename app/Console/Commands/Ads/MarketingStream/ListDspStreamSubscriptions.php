<?php

namespace App\Console\Commands\Ads\MarketingStream;

use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListDspStreamSubscriptions extends Command
{
    protected $signature = 'app:list-dsp-stream-subscriptions';
    protected $description = 'Fetch DSP stream subscriptions from Amazon Marketing Stream';

    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);
        Log::info('🚀 DSP Stream Subscription Fetch Started');

        // Example: iterate over multiple DSP advertiser accounts
        $accounts = [
            'US' => config('amazon_ads.profiles.US'),
            'CA' => config('amazon_ads.profiles.CA'),
        ];

        foreach ($accounts as $country => $profileId) {
            if (!$profileId) continue;

            $nextToken = null;

            do {
                $payload = [
                    'maxResults'    => 1000,
                    'startingToken' => $nextToken,
                ];

                $response = $amazonAdsService->listDspStreamSubscriptions($payload, $profileId);
                Log::info('Raw DSP Subscription Response', ['response' => $response]);

                if (empty($response['success']) || empty($response['data'])) {
                    Log::warning("⚠️ No subscriptions returned for profile {$profileId}");
                    break;
                }

                $subscriptions = $response['data']['subscriptions'] ?? [];
                $nextToken = $response['data']['nextToken'] ?? null;

                foreach ($subscriptions as $sub) {
                    Log::info('📄 DSP Subscription', [
                        'subscriptionId' => $sub['subscriptionId'] ?? null,
                        'status'         => $sub['status'] ?? null,
                        'dataSetId'      => $sub['dataSetId'] ?? null,
                        'destinationArn' => $sub['destinationArn'] ?? null,
                        'createdDate'    => $sub['createdDate'] ?? null,
                        'updatedDate'    => $sub['updatedDate'] ?? null,
                        'notes'          => $sub['notes'] ?? null,
                    ]);
                }

                // Delay to avoid throttling
                usleep(200000); // 200ms
            } while ($nextToken);
        }

        Log::info('🏁 DSP Stream Subscription Fetch Completed');
        return Command::SUCCESS;
    }
}
