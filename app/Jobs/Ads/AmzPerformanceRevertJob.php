<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordSb;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmzPerformanceRevertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $date;
    public string $filterType;
    public ?int $userId;

    // Job-level retries (optional but recommended)
    public int $tries = 3;

    public function backoff(): array
    {
        // seconds: 30s, 60s, 120s
        return [30, 60, 120];
    }

    public function __construct(string $date, string $filterType = 'all', ?int $userId = null)
    {
        $this->date = Carbon::parse($date)->toDateString();
        $this->filterType = $filterType ?: 'all';
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $amazonAdsService = app(AmazonAdsService::class);

        $logs = AmzPerformanceChangeLog::query()
            ->where('run_status', 'dispatched')
            ->where('run_update', true)
            ->whereDate('date', $this->date)
            ->when($this->filterType !== 'all', fn($q) => $q->where('change_type', $this->filterType))
            ->whereNotNull('old_value')
            ->get();

        if ($logs->isEmpty()) {
            Log::info("No dispatched revert logs found.", ['date' => $this->date, 'type' => $this->filterType]);
            return;
        }

        // Group by country + change_type (campaign|keyword)
        $countryChangeGroups = $logs->groupBy(function ($l) {
            $country = strtoupper((string) ($l->country ?? ''));
            $change  = strtolower((string) ($l->change_type ?? ''));
            return $country . '_' . $change;
        });

        $typeOrder = ['SP', 'SB', 'SD'];

        foreach ($typeOrder as $index => $adType) {

            foreach ($countryChangeGroups as $groupKey => $group) {
                try {
                    [$country, $changeType] = explode('_', $groupKey, 2);

                    $profileId = match ($country) {
                        'US' => config('amazon_ads.profiles.US'),
                        'CA' => config('amazon_ads.profiles.CA'),
                        default => throw new Exception("Unhandled country: {$country}"),
                    };

                    $changeType = strtolower(trim($changeType)); // campaign|keyword

                    /** @var Collection $typedLogs */
                    $typedLogs = $group->filter(fn($l) => strtoupper((string) ($l->type ?? '')) === $adType);

                    if ($typedLogs->isEmpty()) {
                        continue;
                    }

                    Log::info("Starting revert batch", [
                        'date' => $this->date,
                        'country' => $country,
                        'change_type' => $changeType,
                        'ad_type' => $adType,
                        'count' => $typedLogs->count(),
                    ]);

                    if ($changeType === 'campaign') {
                        $this->revertCampaignBudgets($amazonAdsService, $profileId, $country, $adType, $typedLogs);
                    } elseif ($changeType === 'keyword') {
                        $this->revertKeywordBids($amazonAdsService, $profileId, $country, $adType, $typedLogs);
                    } else {
                        Log::warning("Skipping unknown change_type", [
                            'change_type' => $changeType,
                            'country' => $country,
                            'ad_type' => $adType,
                        ]);

                        AmzPerformanceChangeLog::whereIn('id', $typedLogs->pluck('id')->all())
                            ->update(['run_status' => 'failed', 'updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                    Log::error("Revert batch failed: {$e->getMessage()}", [
                        'group' => $groupKey,
                        'ad_type' => $adType,
                    ]);

                    $failedIds = $group
                        ->filter(fn($l) => strtoupper((string) ($l->type ?? '')) === $adType)
                        ->pluck('id')
                        ->all();

                    if (!empty($failedIds)) {
                        AmzPerformanceChangeLog::whereIn('id', $failedIds)
                            ->update(['run_status' => 'failed', 'updated_at' => now()]);
                    }
                }

                // gentle rate limit between country/change batches
                sleep(2);
            }

            // REQUIRED: wait 10 seconds between ad types (SP -> SB -> SD)
            if ($index < count($typeOrder) - 1) {
                sleep(10);
            }
        }
    }

    /**
     * Retry wrapper for Amazon API calls.
     * Retries on transport issues (http_code=0), empty response, and 5xx.
     */
    private function callAmazonWithRetry(callable $fn, array $context = [], int $maxAttempts = 3)
    {
        $attempt = 1;

        while (true) {
            $response = null;
            try {
                $response = $fn();

                if ($this->isAmazonSuccess($response)) {
                    return $response;
                }

                if (!$this->isRetryableAmazonFailure($response)) {
                    // Non-retryable failure (likely 4xx / validation / auth)
                    return $response;
                }

                Log::warning("Amazon API retryable failure", array_merge($context, [
                    'attempt' => $attempt,
                    'response' => $response,
                ]));
            } catch (\Throwable $e) {
                // Exceptions can be retryable too (network, timeouts)
                Log::warning("Amazon API exception (retrying)", array_merge($context, [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]));
            }

            if ($attempt >= $maxAttempts) {
                return $response; // last response (or null)
            }

            // backoff: 3s, 6s, 12s ...
            sleep(3 * (2 ** ($attempt - 1)));
            $attempt++;
        }
    }

    private function isAmazonSuccess($response): bool
    {
        return !empty($response) && (($response['success'] ?? false) === true);
    }

    private function isRetryableAmazonFailure($response): bool
    {
        if (empty($response)) {
            return true;
        }

        $http = (int) ($response['responseInfo']['http_code'] ?? 0);

        // http_code 0 => curl couldn't connect / DNS / TLS / timeout
        if ($http === 0) {
            return true;
        }

        // retry on 5xx
        if ($http >= 500) {
            return true;
        }

        return false;
    }

    private function revertCampaignBudgets(
        AmazonAdsService $amazonAdsService,
        string $profileId,
        string $country,
        string $adType,
        $group
    ): void {
        foreach ($group->chunk(200) as $batch) {
            $valid = collect();
            $payload = [];

            foreach ($batch as $log) {
                $oldBudget = (float) $log->old_value;
                if (!$log->campaign_id || $oldBudget <= 0) {
                    continue;
                }

                switch (strtoupper($adType)) {
                    case 'SP':
                        $payload['campaigns'][] = [
                            "campaignId" => (string) $log->campaign_id,
                            "budget"     => [
                                "budgetType" => "DAILY",
                                "budget"     => $oldBudget,
                            ],
                        ];
                        break;

                    case 'SB':
                        $payload['campaigns'][] = [
                            "campaignId" => (string) $log->campaign_id,
                            "budget"     => $oldBudget,
                        ];
                        break;

                    case 'SD':
                        $payload[] = [
                            "campaignId" => (string) $log->campaign_id,
                            "budgetType" => "daily",
                            "budget"     => $oldBudget,
                        ];
                        break;

                    default:
                        Log::error("Unhandled campaign adType: {$adType}", ['log_id' => $log->id]);
                        continue 2;
                }

                $valid->push([$log, $oldBudget]);
            }

            if ($valid->isEmpty()) {
                continue;
            }

            $context = [
                'flow' => 'revertCampaignBudgets',
                'country' => $country,
                'adType' => $adType,
                'count' => $valid->count(),
            ];

            $response = $this->callAmazonWithRetry(function () use ($amazonAdsService, $adType, $payload, $profileId) {
                return match (strtoupper($adType)) {
                    'SP' => $amazonAdsService->updateCampaigns($payload, $profileId),
                    'SB' => $amazonAdsService->updateSBCampaigns($payload, $profileId),
                    'SD' => $amazonAdsService->updateSDCampaigns($payload, $profileId),
                };
            }, $context, 3);

            if ($this->isAmazonSuccess($response)) {
                foreach ($valid as [$log, $oldBudget]) {
                    $campaignModel = match (strtoupper($adType)) {
                        'SP' => AmzCampaigns::class,
                        'SB' => AmzCampaignsSb::class,
                        'SD' => AmzCampaignsSd::class,
                    };

                    $campaignModel::where('country', $country)
                        ->where('campaign_id', (int) $log->campaign_id)
                        ->update([
                            'daily_budget' => $oldBudget,
                            'updated_at'   => now(),
                        ]);

                    AmzPerformanceChangeLog::where('id', $log->id)->update([
                        'run_status'         => 'reverted',
                        'reverted_by'        => $this->userId,
                        'revert_executed_at' => now(),
                        'updated_at'         => now(),
                    ]);
                }

                Log::info("✅ Reverted {$valid->count()} {$adType} campaign budgets for {$country}");
            } else {
                AmzPerformanceChangeLog::whereIn('id', $valid->map(fn($x) => $x[0]->id)->all())
                    ->update(['run_status' => 'failed', 'updated_at' => now()]);

                Log::error("❌ Campaign revert failed for {$valid->count()} {$adType} in {$country}", $response ?? []);
            }

            sleep(2);
        }
    }

    private function revertKeywordBids(
        AmazonAdsService $amazonAdsService,
        string $profileId,
        string $country,
        string $adType,
        $group
    ): void {
        foreach ($group as $log) {
            try {
                $profileId = match (strtoupper($log->country ?? $country)) {
                    'US' => config('amazon_ads.profiles.US'),
                    'CA' => config('amazon_ads.profiles.CA'),
                    default => throw new Exception("Unhandled country: " . ($log->country ?? $country)),
                };

                $logCountry = strtoupper($log->country ?? $country);
                $logAdType  = strtoupper($log->type ?? $adType);

                $oldBid = (float) $log->old_value;

                if (!$log->keyword_id || $oldBid <= 0) {
                    AmzPerformanceChangeLog::where('id', $log->id)->update([
                        'run_status' => 'failed',
                        'updated_at' => now(),
                    ]);
                    Log::warning("⚠️ Revert skipped: invalid keyword/old_bid", [
                        'log_id' => $log->id,
                        'keyword_id' => $log->keyword_id,
                        'old_value' => $log->old_value,
                    ]);
                    continue;
                }

                // SP
                if ($logAdType === 'SP') {
                    $payload = [
                        'keywords' => [[
                            'keywordId' => (string) $log->keyword_id,
                            'bid'       => (float) $oldBid,
                            'state'     => 'ENABLED',
                        ]],
                    ];

                    $context = [
                        'flow' => 'revertKeywordBids',
                        'adType' => 'SP',
                        'country' => $logCountry,
                        'keyword_id' => (int) $log->keyword_id,
                        'log_id' => (int) $log->id,
                    ];

                    $response = $this->callAmazonWithRetry(function () use ($amazonAdsService, $payload, $profileId) {
                        return $amazonAdsService->updateKeywords($payload, $profileId);
                    }, $context, 3);

                    if ($this->isAmazonSuccess($response)) {
                        $keyword = AmzAdsKeywords::where('country', $logCountry)
                            ->where('keyword_id', (int) $log->keyword_id)
                            ->first();

                        if ($keyword) {
                            $keyword->update([
                                'bid'           => $oldBid,
                                'keyword_state' => 'enabled',
                                'updated_at'    => now(),
                            ]);
                        }

                        AmzPerformanceChangeLog::where('id', $log->id)->update([
                            'run_status'         => 'reverted',
                            'reverted_by'        => $this->userId,
                            'revert_executed_at' => now(),
                            'updated_at'         => now(),
                        ]);

                        Log::info("✅ Reverted SP keyword bid", [
                            'country' => $logCountry,
                            'keyword_id' => (int) $log->keyword_id,
                            'bid' => $oldBid,
                        ]);
                    } else {
                        AmzPerformanceChangeLog::where('id', $log->id)->update([
                            'run_status' => 'failed',
                            'updated_at' => now(),
                        ]);

                        Log::error("❌ Failed to revert SP keyword bid", [
                            'country' => $logCountry,
                            'keyword_id' => (int) $log->keyword_id,
                            'response' => $response ?? [],
                        ]);
                    }

                    sleep(2);
                    continue;
                }

                // SB
                if ($logAdType === 'SB') {
                    $adGroup = DB::table('amz_ads_keyword_performance_report_sb')
                        ->select('ad_group_id')
                        ->where('keyword_id', (int) $log->keyword_id)
                        ->orderByDesc('c_date')
                        ->first();

                    if (!$adGroup) {
                        AmzPerformanceChangeLog::where('id', $log->id)->update([
                            'run_status' => 'failed',
                            'updated_at' => now(),
                        ]);
                        Log::warning("⚠️ No ad_group_id found for SB keyword", [
                            'keyword_id' => (int) $log->keyword_id,
                            'country' => $logCountry,
                        ]);
                        sleep(1);
                        continue;
                    }

                    $payload = [[
                        'keywordId' => (string) $log->keyword_id,
                        'adGroupId' => (string) $adGroup->ad_group_id,
                        'state'     => 'enabled',
                        'bid'       => (float) $oldBid,
                    ]];

                    $context = [
                        'flow' => 'revertKeywordBids',
                        'adType' => 'SB',
                        'country' => $logCountry,
                        'keyword_id' => (int) $log->keyword_id,
                        'ad_group_id' => (int) $adGroup->ad_group_id,
                        'log_id' => (int) $log->id,
                    ];

                    $response = $this->callAmazonWithRetry(function () use ($amazonAdsService, $payload, $profileId) {
                        return $amazonAdsService->updateSBKeywords($payload, $profileId);
                    }, $context, 3);

                    if ($this->isAmazonSuccess($response)) {
                        $keyword = AmzAdsKeywordSb::where('country', $logCountry)
                            ->where('keyword_id', (int) $log->keyword_id)
                            ->first();

                        if ($keyword) {
                            $keyword->update([
                                'bid'           => $oldBid,
                                'keyword_state' => 'enabled',
                                'updated_at'    => now(),
                            ]);
                        }

                        AmzPerformanceChangeLog::where('id', $log->id)->update([
                            'run_status'         => 'reverted',
                            'reverted_by'        => $this->userId,
                            'revert_executed_at' => now(),
                            'updated_at'         => now(),
                        ]);

                        Log::info("✅ Reverted SB keyword bid", [
                            'country' => $logCountry,
                            'keyword_id' => (int) $log->keyword_id,
                            'bid' => $oldBid,
                            'ad_group_id' => (int) $adGroup->ad_group_id,
                        ]);
                    } else {
                        AmzPerformanceChangeLog::where('id', $log->id)->update([
                            'run_status' => 'failed',
                            'updated_at' => now(),
                        ]);

                        Log::error("❌ Failed to revert SB keyword bid", [
                            'country' => $logCountry,
                            'keyword_id' => (int) $log->keyword_id,
                            'ad_group_id' => (int) $adGroup->ad_group_id,
                            'response' => $response ?? [],
                        ]);
                    }

                    sleep(2);
                    continue;
                }

                // Anything else
                AmzPerformanceChangeLog::where('id', $log->id)->update([
                    'run_status' => 'failed',
                    'updated_at' => now(),
                ]);

                Log::error("Unhandled keyword adType in revertKeywordBids", [
                    'adType' => $logAdType,
                    'log_id' => $log->id,
                ]);

                sleep(1);
            } catch (\Throwable $e) {
                AmzPerformanceChangeLog::where('id', $log->id)->update([
                    'run_status' => 'failed',
                    'updated_at' => now(),
                ]);

                Log::error("❌ Exception in revertKeywordBids: {$e->getMessage()}", [
                    'log_id' => $log->id,
                    'keyword_id' => $log->keyword_id,
                ]);

                sleep(1);
            }
        }
    }
}
