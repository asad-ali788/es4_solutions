<?php

namespace App\Jobs\Ads;

use App\Models\CampaignBudgetRecommendation;
use App\Models\AmzAdsProductPerformanceReport;
use App\Models\CampaignBudgetRecommendations;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CampaignBudgetRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;

    private const ASINS = [
        'B09DT8Z793',
        'B08T75LT1Z',
        'B08RZB4F63',
    ];

    public int $tries = 3;
    public int $backoff = 60;

    private const API_BATCH_SIZE = 100;

    /**
     * Used when budgetRuleRecommendation is null.
     * This is REQUIRED because your DB schema has rule_id as NOT NULL + unique key uses rule_id.
     */
    private const NO_RULE_ID = '__NO_RULE__';

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
            Log::channel('ads')->error('CampaignBudgetRecommendationJob: Missing profileId', [
                'country' => $this->country,
            ]);
            return;
        }

        Log::channel('ads')->info("📥 Processing {$this->country} Campaign Budget Recommendations (ASIN filtered)", [
            'asins' => self::ASINS,
        ]);

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

        Log::channel('ads')->info("✅ Completed {$this->country} Campaign Budget Recommendations (ASIN filtered)");
    }

    private function processSpBatch(AmazonAdsService $client, string $profileId, array $campaignIds): void
    {
        $campaignIds = array_values(array_unique(array_filter($campaignIds)));

        if (empty($campaignIds)) {
            return;
        }

        try {
            // Your service signature: getBudgetRecommendations($payload, $profileId)
            $api = $client->getBudgetRecommendations([
                'campaignIds' => $campaignIds,
            ], $profileId);

            $code = (int) ($api['code'] ?? 0);

            if ($code !== 200 || empty($api['response'])) {
                Log::channel('ads')->error('getBudgetRecommendations: Invalid API response', [
                    'code'       => $code,
                    'batch_size' => count($campaignIds),
                    'first_id'   => $campaignIds[0] ?? null,
                    'last_id'    => $campaignIds[count($campaignIds) - 1] ?? null,
                    'request_id' => $api['requestId'] ?? null,
                ]);
                return;
            }

            $body = json_decode($api['response'], true);

            if (! is_array($body)) {
                Log::channel('ads')->error('getBudgetRecommendations: JSON decode failed', [
                    'batch_size' => count($campaignIds),
                    'raw'        => substr((string) $api['response'], 0, 2000),
                    'request_id' => $api['requestId'] ?? null,
                ]);
                return;
            }

            // Correct keys based on your sample response
            $errorResults = $body['budgetRecommendationsErrorResults'] ?? [];
            $successResults = $body['budgetRecommendationsSuccessResults'] ?? [];

            if (! empty($errorResults)) {
                Log::channel('ads')->warning('getBudgetRecommendations: API returned errorResults', [
                    'batch_size'    => count($campaignIds),
                    'error_count'   => count($errorResults),
                    'request_id'    => $api['requestId'] ?? null,
                ]);
            }

            if (empty($successResults) || ! is_array($successResults)) {
                return;
            }

            $now = now();
            $payload = [];

            foreach ($successResults as $item) {
                $campaignId = isset($item['campaignId']) ? (string) $item['campaignId'] : null;
                if (! $campaignId) {
                    continue;
                }

                $missed = $item['sevenDaysMissedOpportunities'] ?? [];
                $rule   = $item['budgetRuleRecommendation'] ?? null;

                // DB needs rule_id; handle null rule block safely
                $ruleId = is_array($rule) && !empty($rule['ruleId'])
                    ? (string) $rule['ruleId']
                    : self::NO_RULE_ID;

                $ruleName = is_array($rule) ? ($rule['ruleName'] ?? null) : null;

                // suggestedBudgetIncreasePercent is integer-like (5)
                $suggestedIncreasePercent = is_array($rule) && isset($rule['suggestedBudgetIncreasePercent'])
                    ? (int) $rule['suggestedBudgetIncreasePercent']
                    : 0;

                // Dates are YYYYMMDD
                $startDate = !empty($missed['startDate'])
                    ? Carbon::createFromFormat('Ymd', (string) $missed['startDate'])->format('Y-m-d')
                    : null;

                $endDate = !empty($missed['endDate'])
                    ? Carbon::createFromFormat('Ymd', (string) $missed['endDate'])->format('Y-m-d')
                    : null;

                $payload[] = [
                    'campaign_id'                        => $campaignId,
                    'campaign_type'                      => 'SP',
                    'rule_id'                            => $ruleId,
                    'rule_name'                          => $ruleName,
                    'suggested_budget'                   => isset($item['suggestedBudget']) ? (int) $item['suggestedBudget'] : null,
                    'suggested_budget_increase_percent'  => $suggestedIncreasePercent,
                    'seven_days_start_date'              => $startDate,
                    'seven_days_end_date'                => $endDate,
                    'estimated_missed_sales_lower'       => isset($missed['estimatedMissedSalesLower']) ? (float) $missed['estimatedMissedSalesLower'] : null,
                    'percent_time_in_budget'             => isset($missed['percentTimeInBudget']) ? (float) $missed['percentTimeInBudget'] : null,
                    'created_at'                         => $now,
                    'updated_at'                         => $now,
                ];
            }

            if (empty($payload)) {
                return;
            }

            // Unique key uses rule_id, so we include it here.
            CampaignBudgetRecommendations::upsert(
                $payload,
                ['campaign_id', 'campaign_type', 'rule_id'],
                [
                    'rule_name',
                    'suggested_budget',
                    'suggested_budget_increase_percent',
                    'seven_days_start_date',
                    'seven_days_end_date',
                    'estimated_missed_sales_lower',
                    'percent_time_in_budget',
                    'updated_at',
                ]
            );
        } catch (Throwable $e) {
            Log::channel('ads')->error('getBudgetRecommendations: Batch exception', [
                'batch_size' => count($campaignIds),
                'first_id'   => $campaignIds[0] ?? null,
                'message'    => $e->getMessage(),
                'trace'      => substr($e->getTraceAsString(), 0, 2000),
            ]);
        }
    }
}
