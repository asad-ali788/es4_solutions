<?php

declare(strict_types=1);

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\SpSearchTermSummaryReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SpSearchTermSummaryGetReportSaveJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $country;

    /**
     * Retry / timeout tuning for large imports.
     */
    public int $tries = 3;
    public int $timeout = 1800; // 30 min
    public int $backoff = 60;

    private const REPORT_TYPE = 'spSearchTermSummary';
    private const UPSERT_CHUNK_SIZE = 2000;

    public function __construct(string $country)
    {
        $this->country = strtoupper($country);
    }

    public function handle(AmazonAdsService $client): void
    {
        Log::channel('ads')->info("📥 Processing {$this->country} SP Search Term Summary Report");

        $reportLog = AmzAdsReportLog::query()
            ->where('country', $this->country)
            ->where('report_type', self::REPORT_TYPE)
            ->where('report_status', 'IN_PROGRESS')
            ->latest('id')
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[SpSearchTermSummary][{$this->country}] No report in progress.");
            return;
        }

        try {
            $profileId = $this->resolveProfileId();

            $response = $client->getReport($reportLog->report_id, $profileId);

            Log::channel('ads')->info(
                "[SpSearchTermSummary][{$this->country}] Response Code: {$response['code']}"
            );

            if ((int) ($response['code'] ?? 0) !== 200) {
                Log::channel('ads')->warning("[SpSearchTermSummary][{$this->country}] Invalid response code.");
                return;
            }

            $responseData = json_decode((string) ($response['response'] ?? ''), true);

            if (!is_array($responseData)) {
                Log::channel('ads')->warning("[SpSearchTermSummary][{$this->country}] Invalid response payload.");
                return;
            }

            $status = $responseData['status'] ?? null;

            if ($status === 'PENDING') {
                $reportLog->increment('r_iteration');

                Log::channel('ads')->info(
                    "[SpSearchTermSummary][{$this->country}] Still pending. Iteration++"
                );

                return;
            }

            if ($status !== 'COMPLETED') {
                Log::channel('ads')->warning(
                    "[SpSearchTermSummary][{$this->country}] Unexpected status: " . (string) $status
                );
                return;
            }

            $downloadUrl = $responseData['url'] ?? null;

            if (!$downloadUrl) {
                Log::channel('ads')->warning("[SpSearchTermSummary][{$this->country}] Missing download URL.");
                return;
            }

            $downloaded = $client->downloadReport($downloadUrl, true, $profileId);
            $reportRows = json_decode((string) ($downloaded['response'] ?? ''), true);

            if (empty($reportRows) || !is_array($reportRows)) {
                $client->deleteReport($reportLog->report_id);

                $reportLog->update([
                    'report_status' => 'EMPTY',
                ]);

                Log::channel('ads')->warning(
                    "[SpSearchTermSummary][{$this->country}] Empty report → deleted."
                );

                return;
            }

            $campaignIds = $this->extractCampaignIds($reportRows);

            $campaignNameMap = [];
            $campaignAsinMap = [];
            $asinProductNameMap = [];

            if ($campaignIds !== []) {
                $campaignNameMap = DB::table('amz_campaigns')
                    ->whereIn('campaign_id', $campaignIds)
                    ->pluck('campaign_name', 'campaign_id')
                    ->map(static fn($value) => $value !== null ? (string) $value : null)
                    ->all();

                $campaignProductRows = DB::table('amz_ads_products')
                    ->select(['campaign_id', 'asin'])
                    ->whereIn('campaign_id', $campaignIds)
                    ->get();

                foreach ($campaignProductRows as $row) {
                    if ($row->campaign_id !== null && !isset($campaignAsinMap[$row->campaign_id])) {
                        $campaignAsinMap[$row->campaign_id] = $row->asin !== null
                            ? (string) $row->asin
                            : null;
                    }
                }

                $asins = array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                static fn($asin) => $asin !== null ? (string) $asin : null,
                                $campaignAsinMap
                            )
                        )
                    )
                );

                if ($asins !== []) {
                    $productRows = DB::table('product_categorisations')
                        ->select(['child_asin', 'child_short_name'])
                        ->whereIn('child_asin', $asins)
                        ->get();

                    foreach ($productRows as $row) {
                        if ($row->child_asin !== null && !isset($asinProductNameMap[$row->child_asin])) {
                            $asinProductNameMap[$row->child_asin] = $row->child_short_name !== null
                                ? (string) $row->child_short_name
                                : null;
                        }
                    }
                }
            }

            $defaultDate = $this->normalizeDate((string) $reportLog->report_date);
            $now = now();

            $buffer = [];
            $processed = 0;

            foreach ($reportRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $campaignId = $row['campaignId'] ?? null;
                $campaignId = $campaignId !== null ? (string) $campaignId : null;

                $asin = $campaignId !== null
                    ? ($campaignAsinMap[$campaignId] ?? null)
                    : null;

                $productName = $asin !== null
                    ? ($asinProductNameMap[$asin] ?? null)
                    : null;

                $campaignName = $campaignId !== null
                    ? ($campaignNameMap[$campaignId] ?? null)
                    : null;

                $buffer[] = [
                    'country' => $this->country,
                    'date' => $this->normalizeDate($row['date'] ?? null) ?? $defaultDate,
                    'campaign_id' => $campaignId,
                    'ad_group_id' => $this->nullableString($row['adGroupId'] ?? null),
                    'keyword_id' => $this->nullableString($row['keywordId'] ?? null),
                    'keyword' => $this->nullableString($row['keyword'] ?? null),
                    'search_term' => $this->nullableString($row['searchTerm'] ?? null),

                    'product_name' => $productName,
                    'asin' => $asin,
                    'keyword_name' => $this->nullableString($row['keyword'] ?? null),
                    'campaign_name' => $campaignName,

                    'impressions' => $this->nullableInt($row['impressions'] ?? null),
                    'clicks' => $this->nullableInt($row['clicks'] ?? null),
                    'cost_per_click' => $this->nullableDecimal($row['costPerClick'] ?? null),
                    'cost' => $this->nullableDecimal($row['cost'] ?? null),
                    'purchases_1d' => $this->nullableInt($row['purchases1d'] ?? null),
                    'purchases_7d' => $this->nullableInt($row['purchases7d'] ?? null),
                    'purchases_14d' => $this->nullableInt($row['purchases14d'] ?? null),
                    'sales_1d' => $this->nullableDecimal($row['sales1d'] ?? null),
                    'sales_7d' => $this->nullableDecimal($row['sales7d'] ?? null),
                    'sales_14d' => $this->nullableDecimal($row['sales14d'] ?? null),
                    'campaign_budget_amount' => $this->nullableDecimal($row['campaignBudgetAmount'] ?? null),
                    'keyword_bid' => $this->nullableDecimal($row['keywordBid'] ?? null),
                    'keyword_type' => $this->nullableString($row['keywordType'] ?? null),
                    'match_type' => $this->nullableString($row['matchType'] ?? null),
                    'targeting' => $this->nullableString($row['targeting'] ?? null),
                    'ad_keyword_status' => $this->nullableString($row['adKeywordStatus'] ?? null),
                    'start_date' => $this->normalizeDate($row['startDate'] ?? null),
                    'end_date' => $this->normalizeDate($row['endDate'] ?? null),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= self::UPSERT_CHUNK_SIZE) {
                    $this->upsertRows($buffer);
                    $processed += count($buffer);
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                $this->upsertRows($buffer);
                $processed += count($buffer);
            }

            $reportLog->update([
                'report_status' => 'COMPLETED',
            ]);

            Log::channel('ads')->info(
                "[SpSearchTermSummary][{$this->country}] Report saved to DB → COMPLETED. Rows processed: {$processed}"
            );
        } catch (Throwable $e) {
            Log::channel('ads')->error(
                "[SpSearchTermSummary][{$this->country}] ERROR: {$e->getMessage()}",
                [
                    'country' => $this->country,
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractCampaignIds(array $rows): array
    {
        $campaignIds = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $campaignId = $row['campaignId'] ?? null;

            if ($campaignId === null || $campaignId === '') {
                continue;
            }

            $campaignIds[] = (string) $campaignId;
        }

        return array_values(array_unique($campaignIds));
    }

    private function upsertRows(array $rows): void
    {
        SpSearchTermSummaryReport::query()->upsert(
            $rows,
            [
                'country',
                'date',
                'campaign_id',
                'ad_group_id',
                'keyword_id',
                'search_term',
            ],
            [
                'keyword',
                'product_name',
                'asin',
                'keyword_name',
                'campaign_name',
                'impressions',
                'clicks',
                'cost_per_click',
                'cost',
                'purchases_1d',
                'purchases_7d',
                'purchases_14d',
                'sales_1d',
                'sales_7d',
                'sales_14d',
                'campaign_budget_amount',
                'keyword_bid',
                'keyword_type',
                'match_type',
                'targeting',
                'ad_keyword_status',
                'start_date',
                'end_date',
                'updated_at',
            ]
        );
    }

    private function resolveProfileId(): string|int|null
    {
        return match ($this->country) {
            'US' => config('amazon_ads.profiles.US'),
            'CA' => config('amazon_ads.profiles.CA'),
            default => config('amazon_ads.profiles.US'),
        };
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
