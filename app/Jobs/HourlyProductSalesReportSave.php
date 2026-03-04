<?php

namespace App\Jobs;

use App\Models\AmzReportsLog;
use App\Models\HourlyProductSales;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HourlyProductSalesReportSave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Process up to N logs per run to avoid very long jobs.
     * Adjust if needed.
     */
    public int $limit = 20;

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        Log::channel('spApi')->info('✅ HourlyProductSalesReportSave Started');

        $marketTz = (string) config('timezone.market', 'America/Los_Angeles');

        $service = app(DailyReportGetService::class);
        $parser  = app(AmazonReportParser::class);

        $logs = AmzReportsLog::query()
            ->where('report_status', 'IN_PROGRESS')
            ->where('report_frequency', 'product_hourly_sales')
            ->where('report_type', 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL')
            ->whereNotNull('report_id')
            ->where('report_id', 'not like', 'PENDING_%')
            ->orderBy('start_date')
            ->limit($this->limit)
            ->get();

        if ($logs->isEmpty()) {
            return '📭 Hourly Product Sales - No IN_PROGRESS reports found';
        }

        $results = [];

        foreach ($logs as $log) {
            $reportId = (string) $log->report_id;

            // Hour window derived from log (UTC)
            $hourStartUtc = Carbon::parse($log->start_date, 'UTC')->startOfHour();
            $hourEndUtc   = Carbon::parse($log->end_date, 'UTC')->startOfHour();

            // Expect end = start + 1 hour (exclusive end)
            if (!$hourEndUtc->equalTo($hourStartUtc->copy()->addHour())) {
                Log::channel('spApi')->warning('⚠️ Hour window mismatch in AmzReportsLog', [
                    'report_id'  => $reportId,
                    'start_date' => $log->start_date,
                    'end_date'   => $log->end_date,
                ]);
            }

            // Fallback hour bucket from log window (marketplace local)
            $fallbackSaleHour = $hourStartUtc->copy()->timezone($marketTz)->startOfHour();

            try {
                // 1) Ensure report document exists / ready
                $doc = $service->getReportDocument($reportId);

                if (!$doc) {
                    $results[] = "⏳ Report not ready: {$reportId}";
                    continue;
                }

                // 2) Parse report rows
                $firstRowLogged = false;

                /**
                 * Aggregate in-memory
                 * Key: sku|sales_channel|sale_hour
                 *
                 * - purchase_date: earliest purchase timestamp in that hour (PST)
                 * - sale_hour: bucket hour start (PST)
                 * - total_units: sum
                 * - item_price: total revenue for that hour = sum(unit_price * units)
                 * - asin: keep latest non-null (metadata)
                 */
                $agg = [];

                foreach ($parser->parse($reportId) as $row) {
                    if (!$firstRowLogged) {
                        Log::channel('spApi')->info('🔎 Parser sample keys (HourlyProductSales)', [
                            'report_id' => $reportId,
                            'keys'      => array_keys((array) $row),
                            'sample'    => $this->safeSample($row),
                        ]);
                        $firstRowLogged = true;
                    }

                    $mapped = $this->mapParserRowToProductSales($row);
                    // Expected keys from mapper:
                    // sku, asin, sales_channel, purchase_date (PST actual), sale_hour (PST bucket),
                    // total_units, item_price (per unit), currency

                    $sku = (string) ($mapped['sku'] ?? '');
                    if ($sku === '') {
                        continue;
                    }

                    $asin = $mapped['asin'] ?? null;

                    $salesChannel = (string) ($mapped['sales_channel'] ?? '');
                    if ($salesChannel === '') {
                        continue;
                    }

                    $units = (int) ($mapped['total_units'] ?? 0);
                    if ($units <= 0) {
                        continue;
                    }

                    $currency = (string) ($mapped['currency'] ?? 'USD');

                    $purchaseDate = $mapped['purchase_date'] ?? null; // PST actual timestamp
                    $saleHour     = $mapped['sale_hour'] ?? null;     // PST hour bucket (should be startOfHour)

                    // Fallbacks if missing from parser
                    if ($saleHour === null) {
                        $saleHour = $fallbackSaleHour;
                    } else {
                        $saleHour = Carbon::parse($saleHour)->startOfHour();
                    }

                    if ($purchaseDate !== null) {
                        $purchaseDate = Carbon::parse($purchaseDate);
                    }

                    // Mapper item_price assumed as per-unit; store hourly total revenue in item_price
                    $unitPrice = (float) ($mapped['item_price'] ?? 0.0);
                    $lineTotal = $unitPrice * $units;

                    $key = implode('|', [
                        $sku,
                        $salesChannel,
                        $saleHour->format('Y-m-d H:00:00'),
                    ]);

                    if (!isset($agg[$key])) {
                        $agg[$key] = [
                            'sku'           => $sku,
                            'asin'          => $asin,
                            'sales_channel' => $salesChannel,
                            'purchase_date' => $purchaseDate,
                            'sale_hour'     => $saleHour,
                            'total_units'   => 0,
                            'item_price'    => 0.0,
                            'currency'      => $currency ?: 'USD',
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                    }

                    $agg[$key]['total_units'] += $units;
                    $agg[$key]['item_price']  += $lineTotal;

                    // Keep earliest purchase_date within the hour (audit/debug)
                    if ($purchaseDate !== null) {
                        $current = $agg[$key]['purchase_date'];
                        if ($current === null || $purchaseDate->lt(Carbon::parse($current))) {
                            $agg[$key]['purchase_date'] = $purchaseDate;
                        }
                    }

                    // Keep latest non-null ASIN (metadata)
                    if (!empty($asin)) {
                        $agg[$key]['asin'] = $asin;
                    }

                    // Currency: keep first non-empty
                    if (empty($agg[$key]['currency']) && !empty($currency)) {
                        $agg[$key]['currency'] = $currency;
                    }
                }

                if (empty($agg)) {
                    Log::channel('spApi')->warning('⚠️ No data aggregated for report', [
                        'report_id'  => $reportId,
                        'start_date' => $log->start_date,
                        'end_date'   => $log->end_date,
                    ]);

                    $results[] = "⚠️ No rows aggregated: {$reportId}";
                    continue;
                }

                $payload = array_values($agg);

                DB::transaction(function () use ($payload, $log) {
                    HourlyProductSales::query()->upsert(
                        $payload,
                        ['sku', 'sales_channel', 'sale_hour'],
                        [
                            'asin',
                            'purchase_date',
                            'total_units',
                            'item_price',
                            'currency',
                            'updated_at',
                        ]
                    );

                    $log->update([
                        'report_status' => 'DONE',
                    ]);
                });

                $results[] = "✅ Saved: {$reportId} | rows=" . count($payload)
                    . " | window_utc=" . $hourStartUtc->toDateTimeString() . "→" . $hourEndUtc->toDateTimeString();
            } catch (Throwable $e) {
                Log::channel('spApi')->error('❌ HourlyProductSalesReportSave failed', [
                    'report_id'  => $reportId,
                    'error'      => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                    'start_date' => $log->start_date,
                    'end_date'   => $log->end_date,
                ]);

                $log->update([
                    'report_status' => 'FATAL',
                ]);

                $results[] = "❌ Failed: {$reportId} ({$e->getMessage()})";
            }
        }


        return implode("\n", $results);
    }

    /**
     * Map whatever your parser returns into normalized fields.
     * This is defensive: it tries multiple possible keys.
     */
    private function mapParserRowToProductSales(array|object $row): array
    {
        $arr = (array) $row;

        // Normalize keys: lowercase + snake_case
        $normalized = [];
        foreach ($arr as $k => $v) {
            $key = strtolower(trim((string) $k));
            $key = str_replace([' ', '-', '__'], ['_', '_', '_'], $key);
            $normalized[$key] = $v;
        }

        // SKU / ASIN
        $sku  = $normalized['sku'] ?? $normalized['seller_sku'] ?? null;
        $asin = $normalized['asin'] ?? $normalized['asin1'] ?? null;

        // Sales channel
        $salesChannel = $normalized['sales_channel']
            ?? $normalized['marketplace']
            ?? $normalized['marketplace_name']
            ?? null;

        // Units
        $units = $normalized['quantity']
            ?? $normalized['quantity_purchased']
            ?? 0;

        // Price
        $itemPrice = $normalized['item_price']
            ?? $normalized['itemprice']
            ?? $normalized['price']
            ?? 0;

        $currency = $normalized['currency'] ?? 'USD';

        //  Time handling
        $purchaseDatePst = null; // actual timestamp in PST (market TZ)
        $saleHourPst     = null; // bucket start in PST (market TZ)

        $marketTz = (string) config('timezone.market', 'America/Los_Angeles');

        // Your normalized key becomes purchase_date (from "purchase-date")
        if (!empty($normalized['purchase_date'])) {
            // Parse as UTC (API gives +00:00), then convert to marketplace TZ
            $purchaseDatePst = Carbon::parse($normalized['purchase_date'], 'UTC')
                ->timezone($marketTz);

            // Bucket after conversion
            $saleHourPst = $purchaseDatePst->copy()->startOfHour();
        }

        return [
            'sku'           => $sku ? (string) $sku : null,
            'asin'          => $asin ? (string) $asin : null, // keep if you still need it elsewhere
            'sales_channel' => $salesChannel ? (string) $salesChannel : null,

            'purchase_date' => $purchaseDatePst, // PST actual (keeps seconds)
            'sale_hour'     => $saleHourPst,     // PST bucket (HH:00:00)

            'total_units'   => (int) $this->toNumber($units),
            'item_price'    => (float) $this->toNumber($itemPrice),
            'currency'      => (string) $currency,
        ];
    }

    /**
     * Convert values like "1,234.50" or "$12.34" into float-compatible numbers.
     */
    private function toNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return 0.0;
        }

        // remove currency symbols and spaces
        $s = preg_replace('/[^\d\.\,\-]/', '', $s) ?? '0';

        // if contains commas, remove them (assume thousands separator)
        $s = str_replace(',', '', $s);

        return is_numeric($s) ? (float) $s : 0.0;
    }

    /**
     * Log a safe small sample (prevents huge logs).
     */
    private function safeSample(array|object $row): array
    {
        $arr = (array) $row;

        // keep only first ~12 keys to avoid noisy logs
        $out = [];
        $i = 0;
        foreach ($arr as $k => $v) {
            $out[$k] = is_scalar($v) ? $v : (is_null($v) ? null : '[non-scalar]');
            $i++;
            if ($i >= 12) {
                break;
            }
        }

        return $out;
    }
}
