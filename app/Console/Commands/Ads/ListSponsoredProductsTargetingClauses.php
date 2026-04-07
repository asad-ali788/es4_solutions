<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzCampaigns;
use App\Models\AmzTargetingClauses;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ListSponsoredProductsTargetingClauses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-sp-targeting-clauses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Sync SP Targeting Clauses [US/CA] from Amazon Ads API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);

        // fetch enabled auto campaigns
        $campaigns = AmzCampaigns::where('campaign_state', 'ENABLED')
            ->where('targeting_type', 'AUTO')
            ->get();

        foreach ($campaigns as $campaign) {
            $profileId = match (strtoupper($campaign->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => null,
            };

            if (!$profileId) {
                Log::channel('ads')->warning("No profileId mapping for country {$campaign->country}");
                continue;
            }

            $payload = [
                "campaignIdFilter" => [
                    "include" => [(string) $campaign->campaign_id],
                ],
                "includeExtendedDataFields" => true,
                "stateFilter" => [
                    "include" => ["ENABLED"], 
                ],
                "maxResults" => 1000,
            ];

            $nextToken = null;
            do {
                if ($nextToken) {
                    $payload['nextToken'] = $nextToken;
                }

                // --- Retry wrapper for API call ---
                $data = null;
                $attempts = 0;
                do {
                    $attempts++;
                    $data = $amazonAdsService->listSponsoredProductsTargetingClauses($payload, $profileId);

                    if (!empty($data['success']) && $data['success']) {
                        break; // success
                    }

                    Log::channel('ads')->warning("TargetingClauses API attempt {$attempts} failed", [
                        'campaign' => $campaign->campaign_id,
                        'response' => $data,
                    ]);

                    if ($attempts < 3) {
                        sleep(pow(2, $attempts)); // 2s, 4s
                    }
                } while ($attempts < 3);

                if (empty($data['success']) || !$data['success']) {
                    Log::channel('ads')->error("TargetingClauses API failed after retries", [
                        'campaign' => $campaign->campaign_id,
                    ]);
                    break;
                }

                $response = json_decode($data['response'], true);

                if (!empty($response['targetingClauses'])) {
                    foreach ($response['targetingClauses'] as $clause) {
                        try {
                            AmzTargetingClauses::updateOrCreate(
                                [
                                    'target_id'   => (string) $clause['targetId'],
                                    'campaign_id' => (string) $campaign->campaign_id,
                                    'ad_group_id' => (string) $clause['adGroupId'],
                                ],
                                [
                                    'country'        => $campaign->country,
                                    'bid'            => $clause['bid'] ?? null,
                                    'expression'     => json_encode($clause['expression'] ?? []),
                                    'expression_val' => $clause['expression'][0]['value'] ?? null,
                                    'state'          => $clause['state'] ?? null,
                                    'added'          => !empty($clause['extendedData']['creationDateTime']) ? Carbon::parse($clause['extendedData']['creationDateTime'])->format('Y-m-d') : null,
                                ]
                            );
                        } catch (\Throwable $e) {
                            Log::channel('ads')->error("Failed to save targeting clause", [
                                'campaign' => $campaign->campaign_id,
                                'clause'   => $clause,
                                'error'    => $e->getMessage(),
                            ]);
                        }
                    }
                }

                $nextToken = $response['nextToken'] ?? null;

                // throttle requests (1s)
                usleep(1_000_000);
            } while ($nextToken);
        }

        return Command::SUCCESS;
    }
}
