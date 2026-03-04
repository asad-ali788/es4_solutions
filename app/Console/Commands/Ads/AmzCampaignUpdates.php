<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzCampaignsSd;
use App\Models\AmzCampaignUpdates as ModelsAmzCampaignUpdates;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades\Log;

class AmzCampaignUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:amz-campaign-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Campaign & SB Budget and Status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);
        $pendingUpdates   = ModelsAmzCampaignUpdates::where('status', 'pending')->get();

        Log::channel('ads')->info('✅ AmzCampaignUpdates Started');

        foreach ($pendingUpdates as $pendingUpdate) {
            // pick profileId based on country
            $profileId = match (strtoupper($pendingUpdate->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => throw new Exception("Unhandled country: {$pendingUpdate->country}"),
            };

            $payload = [
                'campaigns' => []
            ];

            // Sponsored Products (SP)
            if (strtoupper($pendingUpdate->campaign_type) === 'SP') {
                $payload['campaigns'][] = [
                    "campaignId" => $pendingUpdate->campaign_id,
                    "state"      => strtoupper($pendingUpdate->new_status),
                    "budget"     => [
                        "budgetType" => "DAILY",
                        "budget"     => (float) $pendingUpdate->new_budget,
                    ]
                ];

                $response = $amazonAdsService->updateCampaigns($payload, $profileId);

                if (!empty($response) && isset($response['success']) && $response['success'] === true) {
                    $pendingUpdate->status = 'processed';
                    $pendingUpdate->api_response = json_encode($response);
                    $pendingUpdate->save();

                    $campaign = AmzCampaigns::where('country', $pendingUpdate->country)
                        ->where('campaign_id', $pendingUpdate->campaign_id)
                        ->first();

                    if ($campaign) {
                        $campaign->update([
                            'daily_budget'   => $pendingUpdate->new_budget,
                            'campaign_state' => strtoupper($pendingUpdate->new_status),
                            'updated_at'     => now(),
                        ]);
                    } else {
                        Log::channel('ads')->warning("⚠️ Campaign SP {$pendingUpdate->campaign_id} not found in amz_campaigns table.");
                    }
                } else {
                    $pendingUpdate->status = 'failed';
                    $pendingUpdate->api_response = json_encode($response);
                    $pendingUpdate->save();
                    Log::channel('ads')->error("❌ Failed to update campaign {$pendingUpdate->campaign_id}", $response ?? []);
                }
            }

            // Sponsored Brands (SB)
            elseif (strtoupper($pendingUpdate->campaign_type) === 'SB') {
                $payload['campaigns'][] = [
                    "campaignId" => $pendingUpdate->campaign_id,
                    "state"      => strtoupper($pendingUpdate->new_status),
                    "budget"     => (float) $pendingUpdate->new_budget,
                ];

                $response = $amazonAdsService->updateSBCampaigns($payload, $profileId);

                if (!empty($response) && isset($response['success']) && $response['success'] === true) {
                    $pendingUpdate->status = 'processed';
                    $pendingUpdate->api_response = json_encode($response);
                    $pendingUpdate->save();

                    $campaign = AmzCampaignsSb::where('country', $pendingUpdate->country)
                        ->where('campaign_id', $pendingUpdate->campaign_id)
                        ->first();

                    if ($campaign) {
                        $campaign->update([
                            'daily_budget'   => $pendingUpdate->new_budget,
                            'campaign_state' => strtoupper($pendingUpdate->new_status),
                            'updated_at'     => now(),
                        ]);
                    } else {
                        Log::channel('ads')->warning("⚠️ Campaign SB {$pendingUpdate->campaign_id} not found in amz_campaigns_sb table.");
                    }
                } else {
                    $pendingUpdate->status = 'failed';
                    $pendingUpdate->api_response = json_encode($response);
                    $pendingUpdate->save();
                    Log::channel('ads')->error("❌ Failed to update SB campaign {$pendingUpdate->campaign_id}", $response ?? []);
                }
            }

            // Sponsored Display (SD)
            elseif (strtoupper($pendingUpdate->campaign_type) === 'SD') {
                $payload = [[
                    "campaignId"  => (int) $pendingUpdate->campaign_id,
                    "state"       => strtolower($pendingUpdate->new_status), // enabled/paused/archived
                    "budgetType"  => "daily",
                    "budget"      => (float) $pendingUpdate->new_budget,
                ]];

                $response = $amazonAdsService->updateSDCampaigns($payload, $profileId);

                if (!empty($response) && isset($response['success']) && $response['success'] === true) {
                    $pendingUpdate->status = 'processed';
                    $pendingUpdate->api_response = json_encode($response);
                    $pendingUpdate->save();

                    $campaign = AmzCampaignsSd::where('country', $pendingUpdate->country)
                        ->where('campaign_id', $pendingUpdate->campaign_id)
                        ->first();

                    if ($campaign) {
                        $campaign->update([
                            'daily_budget'   => $pendingUpdate->new_budget,
                            'campaign_state' => strtoupper($pendingUpdate->new_status),
                            'updated_at'     => now(),
                        ]);
                    } else {
                        Log::channel('ads')->warning("⚠️ Campaign SD {$pendingUpdate->campaign_id} not found in amz_campaigns_sd table.");
                    }
                } else {
                    $pendingUpdate->status = 'failed';
                    $pendingUpdate->api_response = json_encode($response);
                    $pendingUpdate->save();
                    Log::channel('ads')->error("❌ Failed to update SD campaign {$pendingUpdate->campaign_id}", $response ?? []);
                }
            } else {
                throw new Exception("Unhandled campaign type: {$pendingUpdate->campaign_type}");
            }

            sleep(3);
        }
        Log::channel('ads')->info("✅ AmzCampaignUpdates Completed");
    }
}
