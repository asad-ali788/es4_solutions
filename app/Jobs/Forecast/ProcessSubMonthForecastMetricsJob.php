<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecastMetric;
use App\Models\HistoricalForecastMetric;
use App\Models\MonthlyAdsProductPerformance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessSubMonthForecastMetricsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $timeout = 1200;

    public function handle(): void
    {
        Log::info('🚀 SKU-level forecast job started');

        try {
            $now = now();

            // Pre-calculated labels & keys
            $lastCompletedMonth = now()->subMonth()->format('Y-m');
            $lastCompletedLabel = Carbon::parse($lastCompletedMonth . '-01')->format('M Y');

            $nextYearMonth = Carbon::parse($lastCompletedMonth . '-01')->addYear()->format('Y-m');
            $nextYearLabel = Carbon::parse($nextYearMonth . '-01')->format('M Y');

            $subMonthKey    = "fc_month_{$lastCompletedMonth}";
            $newForecastKey = "fc_month_{$nextYearMonth}";

            /**
             * Preload ads metrics ONCE — no N+1
             */
            $adsMetrics = MonthlyAdsProductPerformance::where('month', $lastCompletedMonth . '-01')
                ->get()
                ->keyBy(fn($row) => $row->sku . '_' . $row->asin);

            /**
             * Process forecast in chunks (fast)
             */
            OrderForecastMetric::select('id', 'product_id', 'product_sku', 'asin1', 'metrics_by_month')
                ->orderBy('id')
                ->chunk(800, function ($rows) use (
                    $adsMetrics,
                    $now,
                    $subMonthKey,
                    $newForecastKey,
                    $lastCompletedLabel,
                    $nextYearLabel
                ) {

                    $historyRows = [];
                    $updates = [];

                    foreach ($rows as $row) {

                        $sku  = $row->product_sku;
                        $asin = $row->asin1;
                        $key  = $sku . '_' . $asin;

                        $metrics = $row->metrics_by_month ?? [];
                        $hadSubMonth = isset($metrics[$subMonthKey]);

                        if (!$hadSubMonth) {
                            continue; // Skip ASAP
                        }

                        // 1. Push history row to array
                        $historyRows[] = [
                            'product_id'  => $row->product_id,
                            'product_sku' => $sku,
                            'asin1'       => $asin,
                            'metrics_key' => $subMonthKey,
                            'metrics'     => json_encode($metrics[$subMonthKey]),
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];

                        unset($metrics[$subMonthKey]);

                        // 2. Add next year metrics
                        if (!isset($metrics[$newForecastKey])) {

                            $ads = $adsMetrics->get($key);

                            $sold    = (int)($ads->sold ?? 0);
                            $revenue = (float)($ads->revenue ?? 0);
                            $asp     = $sold > 0 ? round($revenue / $sold, 2) : 0;

                            $metrics[$newForecastKey] = [
                                'label'       => $nextYearLabel,
                                'sold'        => $sold,
                                'asp'         => $asp,
                                'ad_spend'    => (float)($ads->ad_spend ?? 0),
                                'ad_sales'    => (float)($ads->ad_sales ?? 0),
                                'acos'        => (float)($ads->acos ?? 0),
                                'tacos'       => (float)($ads->tacos ?? 0),
                                'actual_date' => $lastCompletedLabel,
                            ];
                        }

                        ksort($metrics);

                        $updates[$row->id] = json_encode($metrics);
                    }

                    // 3. BULK INSERT HISTORY (very fast)
                    if (!empty($historyRows)) {
                        HistoricalForecastMetric::insert($historyRows);
                    }

                    // 4. BULK UPDATE FORECASTS (no row-by-row update)
                    if (!empty($updates)) {
                        $case = "";
                        $ids = implode(",", array_keys($updates));

                        foreach ($updates as $id => $json) {
                            $json = str_replace("'", "''", $json);

                            $case .= "WHEN {$id} THEN CAST('{$json}' AS JSON) ";
                        }
                        $sql = "
                        UPDATE order_forecast_metrics
                        SET metrics_by_month = CASE id
                            {$case}
                        END,
                        updated_at = '{$now}'
                        WHERE id IN ({$ids})
                    ";

                        DB::statement($sql);
                    }
                });

            Log::info("✅ Finished optimized job");
        } catch (\Throwable $e) {

            Log::error('❌ SKU-level forecast job failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            throw $e;
        } finally {

            // 🔓 ALWAYS UNLOCK AFTER JOB FINISHES
            OrderForecastMetric::where('is_not_ready', 1)->update([
                'is_not_ready' => 0
            ]);

            Log::info('🔓 order_forecast_metrics unlocked (is_not_ready = 0)');
        }
    }
}
