<?php

namespace App\Jobs\Ads;

use App\Models\AmzCampaignsSd;
use App\Models\AmzTargetsSd;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
class SyncListTargetsSd implements ShouldQueue
{
    use Queueable;

    protected string $region;

    public function __construct(string $region)
    {
        $this->region = $region;
    }

    public function handle(AmazonAdsService $amazonAdsService): void
    {
        try {
            $profileId = config("amazon_ads.profiles.{$this->region}");

            // $filters = [
            //     "stateFilter" => "enabled,paused,archived",
            //     "startIndex"  => 0,
            //     "count"       => 100
            // ];

            $campaignIds = AmzCampaignsSd::where('country', $this->region)
                ->pluck('campaign_id')
                ->toArray();

            if (empty($campaignIds)) {
                Log::channel('ads')->info("ℹ️ No SD campaigns found for {$this->region}, skipping.");
                return;
            }

            $filters = [
                "stateFilter"     => "enabled,paused,archived",
                "startIndex"      => 0,
                "count"           => 100,
                "campaignIdFilter" => implode(",", $campaignIds), 
            ];

            $response = $amazonAdsService->listTargetsSd($filters, $profileId);

            if (!isset($response['response']) || !is_string($response['response'])) {
                return;
            }

            $targets = json_decode($response['response'], true);

            if (!is_array($targets)) {
                return;
            }

            $data = [];
            foreach ($targets as $target) {
                $data[] = [
                    'target_id'           => $target['targetId'] ?? null,
                    'ad_group_id'         => $target['adGroupId'] ?? null,
                    'campaign_id'         => $target['campaignId'] ?? null,
                    'state'               => $target['state'] ?? null,
                    'bid'                 => $target['bid'] ?? null,
                    'expression_type'     => $target['expressionType'] ?? null,
                    'expression'          => isset($target['expression']) ? json_encode($target['expression']) : null,
                    'resolved_expression' => isset($target['resolvedExpression']) ? json_encode($target['resolvedExpression']) : null,
                    'region'              => $this->region,
                    'updated_at'          => now(),
                    'created_at'          => now(),
                ];
            }

            if (!empty($data)) {
                AmzTargetsSd::upsert(
                    $data,
                    ['target_id', 'region'],
                    [
                        'ad_group_id',
                        'campaign_id',
                        'state',
                        'bid',
                        'expression_type',
                        'expression',
                        'resolved_expression',
                        'updated_at',
                    ]
                );
            }

            Log::channel('ads')->info("✅ Upserted SD Targets for {$this->region}", [
                'count' => count($data)
            ]);
        } catch (\Throwable $e) {
            Log::channel('ads')->error("❌ Error syncing SD Targets for {$this->region}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
