<?php

namespace App\Jobs\Ads;

use App\Models\CampaignRecommendations;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzCampaignsSd;
use App\Models\AmzPerformanceChangeLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AmzCampaignPerformanceUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $date;
    public $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($date, $userId = null)
    {
        $this->date = Carbon::parse($date);
        $this->userId = $userId;
    }
    
    /**
     * Execute the job.
     */
    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);

        $pendingUpdates = CampaignRecommendations::where('run_status', 'dispatched')
            ->where('run_update', true)
            ->whereDate('report_week', $this->date)
            ->get();

        if ($pendingUpdates->isEmpty()) {
            Log::info('No dispatched campaigns found.');
            return;
        }

        $grouped = $pendingUpdates->groupBy(fn($item) => strtoupper($item->country . '_' . $item->campaign_types));

        foreach ($grouped as $groupKey => $group) {
            [$country, $type] = explode('_', $groupKey);

            $profileId = match ($country) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => throw new Exception("Unhandled country: {$country}"),
            };

            foreach ($group->chunk(200) as $batch) {
                $payload = [];
                $validBatch = collect();

                foreach ($batch as $pendingUpdate) {
                    $budget = $pendingUpdate->manual_budget
                        ?? $pendingUpdate->suggested_budget
                        ?? $pendingUpdate->ai_suggested_budget;

                    $budget = (float) trim((string) $budget);

                    if (is_null($budget)) {
                        // Log::warning("⏩ Skipping campaign {$pendingUpdate->campaign_id} — no valid budget found.");
                        continue;
                    }

                    switch (strtoupper($type)) {
                        case 'SP':
                            $payload['campaigns'][] = [
                                "campaignId" => $pendingUpdate->campaign_id,
                                "budget"     => [
                                    "budgetType" => "DAILY",
                                    "budget"     => $budget,
                                ],
                            ];
                            break;

                        case 'SB':
                            $payload['campaigns'][] = [
                                "campaignId" => $pendingUpdate->campaign_id,
                                "budget"     => $budget,
                            ];
                            break;

                        case 'SD':
                            $payload[] = [
                                "campaignId"  => $pendingUpdate->campaign_id,
                                "budgetType"  => "daily",
                                "budget"      => $budget,
                            ];
                            break;

                        default:
                            Log::error("❌ Unhandled campaign type: {$type}");
                            continue 2;
                    }

                    $validBatch->push([
                        'model'  => $pendingUpdate,
                        'budget' => $budget
                    ]);
                }

                if ($validBatch->isEmpty()) continue;

                // Call appropriate API
                $response = null;
                switch (strtoupper($type)) {
                    case 'SP':
                        $response = $amazonAdsService->updateCampaigns($payload, $profileId);
                        break;
                    case 'SB':
                        $response = $amazonAdsService->updateSBCampaigns($payload, $profileId);
                        break;
                    case 'SD':
                        $response = $amazonAdsService->updateSDCampaigns($payload, $profileId);
                        break;
                }

                // Process API response
                if (!empty($response) && isset($response['success']) && $response['success'] === true) {
                    foreach ($validBatch as $item) {
                        $pendingUpdate = $item['model'];
                        $budget        = $item['budget'];

                        $oldBudget = $pendingUpdate->total_daily_budget;

                        // Mark as done and update campaign_recommendations
                        $pendingUpdate->update([
                            'run_status'          => 'done',
                            'old_budget'          => $oldBudget,
                            'total_daily_budget'  => $budget,
                        ]);

                        $campaignModel = match (strtoupper($type)) {
                            'SP' => AmzCampaigns::class,
                            'SB' => AmzCampaignsSb::class,
                            'SD' => AmzCampaignsSd::class,
                        };

                        $campaignModel::where('country', $pendingUpdate->country)
                            ->where('campaign_id', $pendingUpdate->campaign_id)
                            ->update([
                                'daily_budget' => $budget,
                                'updated_at'   => now(),
                            ]);

                        AmzPerformanceChangeLog::create([
                            'change_type' => 'campaign',
                            'campaign_id' => (int)$pendingUpdate->campaign_id,
                            'keyword_id'  => null,
                            'target_id'   => null,
                            'country'     => $pendingUpdate->country,
                            'old_value'   => $oldBudget, 
                            'new_value'   => $budget,
                            'type'        => strtoupper($type),
                            'user_id'     => $this->userId,
                            'executed_at' => now(),
                            'date'        => Carbon::parse($pendingUpdate->report_week)->toDateString(), 
                        ]);
                    }

                    Log::info("✅ Bulk updated {$validBatch->count()} {$type} campaigns for {$country}");
                } else {
                    foreach ($validBatch as $item) {
                        $pendingUpdate = $item['model'];
                        $pendingUpdate->run_status = 'failed';
                        $pendingUpdate->save();
                    }

                    Log::error("❌ Bulk update failed for {$validBatch->count()} {$type} campaigns in {$country}", $response ?? []);
                }

                sleep(3);
            }
        }
    }
}
