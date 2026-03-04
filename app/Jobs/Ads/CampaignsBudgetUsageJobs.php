<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsCampaignsBudgetUsage;
use App\Models\AmzAdsProductPerformanceReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CampaignsBudgetUsageJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;

    /**
     * Limit to these ASINs (as requested).
     * If you want dynamic ASINs, pass them via constructor.
     */
    private const ASINS = [
        'B09DT8Z793',
        'B08T75LT1Z',
        'B08RZB4F63',
    ];

    public int $tries = 3;
    public int $backoff = 60;

    private const API_BATCH_SIZE = 100;

    public function __construct(string $country)
    {
        $this->country = strtoupper($country);
    }

    public function handle(AmazonAdsService $client): void
    {
        $profileId = match ($this->country) {
            'US' => config('amazon_ads.profiles.US'),
            'CA' => config('amazon_ads.profiles.CA'),
            default => config('amazon_ads.profiles.US'),
        };

        if (! $profileId) {
            Log::channel('ads')->error('CampaignsBudgetUsageJobs: Missing profileId', [
                'country' => $this->country,
            ]);
            return;
        }

        Log::channel('ads')->info("📥 Processing {$this->country} SP Campaign Budget Usage (ASIN filtered)", [
            'asins' => self::ASINS,
        ]);

        /**
         * IMPORTANT:
         * We fetch UNIQUE campaign IDs directly from DB using DISTINCT,
         * so we do not “lose” IDs due to duplicates in the report table.
         */
        $query = AmzAdsProductPerformanceReport::query()
            ->where('country', $this->country)
            ->whereIn('asin', self::ASINS)
            ->whereNotNull('campaign_id')
            ->select('campaign_id')
            ->distinct()
            ->orderBy('campaign_id');

        $buffer = [];

        foreach ($query->cursor() as $row) {
            $campaignId = (string) $row->campaign_id;

            if ($campaignId === '') {
                continue;
            }

            $buffer[] = $campaignId;

            if (count($buffer) >= self::API_BATCH_SIZE) {
                $this->processSpBatch($client, $profileId, $buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            $this->processSpBatch($client, $profileId, $buffer);
        }

        Log::channel('ads')->info("✅ Completed {$this->country} SP Campaign Budget Usage (ASIN filtered)");
    }

    /**
     * One batch = max 100 campaign IDs.
     * Continues even if API errors happen (logs and returns).
     */
    private function processSpBatch(AmazonAdsService $client, string $profileId, array $campaignIds): void
    {
        $campaignIds = array_values(array_unique(array_filter($campaignIds)));

        if (empty($campaignIds)) {
            return;
        }

        try {
            // Your existing service signature: spCampaignsBudgetUsage($payload, $profileId)
            $api = $client->spCampaignsBudgetUsage([
                'campaignIds' => $campaignIds,
            ], $profileId);

            $code = (int) ($api['code'] ?? 0);

            if ($code !== 200 || empty($api['response'])) {
                Log::channel('ads')->error('CampaignsBudgetUsageJobs: Invalid API response', [
                    'code'       => $code,
                    'batch_size' => count($campaignIds),
                    'first_id'   => $campaignIds[0] ?? null,
                    'last_id'    => $campaignIds[count($campaignIds) - 1] ?? null,
                    'request_id' => $api['requestId'] ?? null,
                ]);
                return;
            }

            // API response shape: {"error":[],"success":[...]}
            $body = json_decode($api['response'], true);

            if (! is_array($body)) {
                Log::channel('ads')->error('CampaignsBudgetUsageJobs: API response JSON decode failed', [
                    'batch_size' => count($campaignIds),
                    'raw'        => substr((string) $api['response'], 0, 2000),
                    'request_id' => $api['requestId'] ?? null,
                ]);
                return;
            }

            $errors = $body['error'] ?? [];
            if (! empty($errors)) {
                Log::channel('ads')->warning('CampaignsBudgetUsageJobs: API returned errors for batch', [
                    'batch_size' => count($campaignIds),
                    'errors'     => $errors,
                    'request_id' => $api['requestId'] ?? null,
                ]);
            }

            $rows = $body['success'] ?? [];
            if (empty($rows) || ! is_array($rows)) {
                return;
            }

            $now = now();
            $payload = [];

            foreach ($rows as $item) {
                $campaignId = isset($item['campaignId']) ? (string) $item['campaignId'] : null;
                if (! $campaignId) {
                    continue;
                }

                $usageUpdatedAt = null;
                if (! empty($item['usageUpdatedTimestamp'])) {
                    $usageUpdatedAt = Carbon::parse($item['usageUpdatedTimestamp'])->utc();
                }

                $payload[] = [
                    'campaign_id'          => $campaignId,
                    'campaign_type'        => 'SP',
                    'budget'               => (float) ($item['budget'] ?? 0),
                    'budget_usage_percent' => (float) ($item['budgetUsagePercent'] ?? 0),
                    'usage_updated_at'     => $usageUpdatedAt,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }

            if (empty($payload)) {
                return;
            }

            /**
             * Requires UNIQUE(campaign_id, campaign_type, usage_updated_at)
             * to prevent duplicates.
             */
            AmzAdsCampaignsBudgetUsage::upsert(
                $payload,
                ['campaign_id', 'campaign_type', 'usage_updated_at'],
                ['budget', 'budget_usage_percent', 'updated_at']
            );
        } catch (Throwable $e) {
            Log::channel('ads')->error('CampaignsBudgetUsageJobs: Batch exception', [
                'batch_size' => count($campaignIds),
                'first_id'   => $campaignIds[0] ?? null,
                'message'    => $e->getMessage(),
                'trace'      => substr($e->getTraceAsString(), 0, 2000),
            ]);
        }
    }
}
