<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsCampaignSchedule;
use App\Models\AmzAdsCampaignScheduleLogs;
use App\Models\AmzAdsCampaignsUnderSchedule;
use Illuminate\Console\Command;
use Exception;
use App\Services\Api\AmazonAdsService;
use Illuminate\Support\Facades\Log;
use App\Models\AmzCampaigns;
use Carbon\Carbon;
use App\Models\AmzCampaignsSb;
use App\Models\AmzCampaignsSd;

class AmzScheduleCampaignUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:amz-schedule-campaign-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Amz Ads Campaign Scheduling Status Updates';

    /**
     * Execute the console command.
     */
    protected array $campaignModels = [
        'SP' => AmzCampaigns::class,
        'SB' => AmzCampaignsSb::class,
        'SD' => AmzCampaignsSd::class,
    ];

    public function handle()
    {
        Log::channel('ads')->info(" ✅ Campaign Schedule Started");
        $this->info("🚀 Campaign Schedule Started");
        $amazonAdsService = app(AmazonAdsService::class);
        $today            = Carbon::now()->format('l');
        $schedules        = AmzAdsCampaignSchedule::where('day_of_week', $today)->get();

        if ($schedules->isEmpty()) {
            $msg = "No schedules found for today.";
            $this->warn($msg);
            return $msg;
        }

        $now     = now()->format('H:i:s');
        $results = [];

        foreach ($schedules as $schedule) {
            $isWithinSchedule = $now >= $schedule->start_time && $now <= $schedule->end_time;
            $desiredState     = $isWithinSchedule ? 'ENABLED' : 'PAUSED';

            if (!$isWithinSchedule) {
                $msg = "⏰ Outside schedule window for {$schedule->country}. Desired state: {$desiredState}.";
                $this->warn($msg);
                $results[] = $msg;
                continue;
            }

            foreach (['SP', 'SB', 'SD'] as $campaignType) {
                $pendingUpdates = AmzAdsCampaignsUnderSchedule::where('run_status', true)
                    ->where('country', $schedule->country)
                    ->where('campaign_type', $campaignType)
                    ->where('campaign_status', '!=', $desiredState)
                    ->get();

                if ($pendingUpdates->isEmpty()) {
                    $msg = "✅ All {$campaignType} campaigns in {$schedule->country} already in desired state ({$desiredState}).";
                    $this->info($msg);
                    $results[] = $msg;
                    continue;
                }

                $payloads = $this->processArray($campaignType, $schedule->country, $desiredState);

                $apiMethod = match ($campaignType) {
                    'SP' => 'updateCampaigns',
                    'SB' => 'updateSBCampaigns',
                    'SD' => 'updateSDCampaigns',
                    default => null,
                };

                if (!$apiMethod) {
                    Log::channel('ads')->warning("⚠️ Campaign Schedule skipped unsupported type: {$campaignType}");
                    $this->warn($msg);
                    continue;
                }

                foreach ($payloads as $payload) {
                    $response = $amazonAdsService->$apiMethod(
                        $payload,
                        $this->getProfileId($schedule->country)
                    );

                    $apiResponse = !empty($response['response'])
                        ? (is_string($response['response']) ? json_decode($response['response'], true) : $response['response'])
                        : [];

                    $successCount = 0;

                    if (!empty($apiResponse['campaigns']['success'])) {
                        $successIds   = collect($apiResponse['campaigns']['success'])->pluck('campaignId')->toArray();
                        $successCount = count($successIds);

                        // Update under-schedule
                        AmzAdsCampaignsUnderSchedule::whereIn('campaign_id', $successIds)
                            ->update(['campaign_status' => $desiredState]);

                        // Update main campaign table
                        if (isset($this->campaignModels[$campaignType])) {
                            $model = $this->campaignModels[$campaignType];
                            $model::whereIn('campaign_id', $successIds)
                                ->update(['campaign_state' => $desiredState]);
                        }
                    }

                    // Insert one consolidated log entry per run
                    AmzAdsCampaignScheduleLogs::create([
                        'country'         => $schedule->country,
                        'payload_request' => json_encode($payload),
                        'action'          => strtolower($desiredState), // enabled / paused
                        'executed_at'     => now(),
                        'status'          => $successCount > 0 ? 'success' : 'failed',
                        'api_response'    => json_encode($apiResponse),
                    ]);

                    $results[] = "Processed {$campaignType} in {$schedule->country} → {$desiredState} (Success: {$successCount})";
                }
            }
        }

        Log::channel('ads')->info(" ✅ Campaign Schedule Run Finished");
        $this->info("🏁 Campaign Schedule Finished");
        // Convert results array to a single string
        $finalResult = implode("\n", $results);
        Log::channel('ads')->info("Campaign Schedule Run Results:\n" . $finalResult);
        return $finalResult;
    }

    public function processArray($campaignType, $country, $state)
    {
        $pendingUpdates = AmzAdsCampaignsUnderSchedule::where('campaign_type', $campaignType)
            ->where('country', $country)
            ->where('run_status', true)
            ->get();

        $payloads = [];

        foreach ($pendingUpdates->chunk(999) as $campaignChunk) {
            $payload = ['campaigns' => []];

            foreach ($campaignChunk as $pendingUpdate) {
                $payload['campaigns'][] = [
                    "campaignId" => (string) $pendingUpdate->campaign_id,
                    "state"      => $state,
                ];
            }

            if (!empty($payload['campaigns'])) {
                $payloads[] = $payload;
            }
        }

        return $payloads;
    }


    private function getProfileId(string $country): string
    {
        return match (strtoupper($country)) {
            'US' => config('amazon_ads.profiles.US'),
            'CA' => config('amazon_ads.profiles.CA'),
            default => throw new \Exception("Unhandled country: {$country}"),
        };
    }
}
