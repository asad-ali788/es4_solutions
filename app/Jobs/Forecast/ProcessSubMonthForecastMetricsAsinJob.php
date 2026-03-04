<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecastMetricAsins;
use App\Models\HistoricalForecastMetricAsins;
use App\Models\MonthlyAdsProductPerformance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessSubMonthForecastMetricsAsinJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $timeout = 1200;

    public function handle(): void
    {
        Log::info('🚀 ASIN-level forecast job started');
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
             * Preload ads metrics aggregated by ASIN (sum sold, revenue, acos, tacos)
             */
            $adsMetrics = MonthlyAdsProductPerformance::where('month', $lastCompletedMonth . '-01')
                ->get()
                ->groupBy('asin')
                ->map(function ($rows) {
                    $sold = $rows->sum(fn($r) => (int)$r->sold);
                    $revenue = $rows->sum(fn($r) => (float)$r->revenue);
                    $adSpend = $rows->sum(fn($r) => (float)$r->ad_spend);
                    $adSales = $rows->sum(fn($r) => (float)$r->ad_sales);
                    $acos = $rows->sum(fn($r) => (float)$r->acos);
                    $tacos = $rows->sum(fn($r) => (float)$r->tacos);
                    $asp = $sold > 0 ? round($revenue / $sold, 2) : 0;

                    return [
                        'sold' => $sold,
                        'revenue' => $revenue,
                        'asp' => $asp,
                        'ad_spend' => $adSpend,
                        'ad_sales' => $adSales,
                        'acos' => $acos,
                        'tacos' => $tacos,
                    ];
                });

            /**
             * Process forecast in chunks
             */
            OrderForecastMetricAsins::select('product_asin', 'metrics_by_month')
                ->orderBy('product_asin')
                ->chunk(500, function ($rows) use ($adsMetrics, $now, $subMonthKey, $newForecastKey, $lastCompletedLabel, $nextYearLabel) {

                    $historyRows = [];
                    $updates = [];

                    foreach ($rows as $row) {
                        $asin = $row->product_asin;
                        $metrics = $row->metrics_by_month ?? [];

                        $hadSubMonth = isset($metrics[$subMonthKey]);
                        if (!$hadSubMonth) continue;

                        // 1. Push history row to array
                        $historyRows[] = [
                            'product_asin' => $asin,
                            'metrics_key'  => $subMonthKey,
                            'metrics'      => json_encode($metrics[$subMonthKey]),
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];

                        unset($metrics[$subMonthKey]);

                        // 2. Add next year metrics if not exists
                        if (!isset($metrics[$newForecastKey])) {
                            $ads = $adsMetrics->get($asin, [
                                'sold' => 0,
                                'revenue' => 0,
                                'asp' => 0,
                                'ad_spend' => 0,
                                'ad_sales' => 0,
                                'acos' => 0,
                                'tacos' => 0,
                            ]);

                            $metrics[$newForecastKey] = [
                                'label'       => $nextYearLabel,
                                'sold'        => $ads['sold'],
                                'asp'         => $ads['asp'],
                                'ad_spend'    => $ads['ad_spend'],
                                'ad_sales'    => $ads['ad_sales'],
                                'acos'        => $ads['acos'],
                                'tacos'       => $ads['tacos'],
                                'actual_date' => $lastCompletedLabel,
                            ];
                        }

                        ksort($metrics);
                        $updates[$asin] = json_encode($metrics);
                    }

                    // 3. Bulk insert historical metrics
                    if (!empty($historyRows)) {
                        HistoricalForecastMetricAsins::insert($historyRows);
                    }

                    // 4. Bulk update metrics using CASE WHEN
                    if (!empty($updates)) {
                        $case = "";
                        $ids = implode(",", array_map(fn($a) => "'$a'", array_keys($updates)));

                        // foreach ($updates as $asin => $json) {
                        //     $json = addslashes($json);
                        //     $case .= "WHEN '{$asin}' THEN '{$json}' ";
                        // }

                        foreach ($updates as $asin => $json) {
                            $json = str_replace("'", "''", $json);

                            $case .= "WHEN '{$asin}' THEN CAST('{$json}' AS JSON) ";
                        }

                        $sql = "
                        UPDATE order_forecast_metric_asins
                        SET metrics_by_month = CASE product_asin
                            {$case}
                        END,
                        updated_at = '{$now}'
                        WHERE product_asin IN ({$ids})
                    ";

                        DB::statement($sql);
                    }
                });

            Log::info("✅ Finished ASIN-level forecast job");
        } catch (\Throwable $e) {

            Log::error('❌ Asin-level forecast job failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            throw $e;
        } finally {

            // 🔓 ALWAYS UNLOCK AFTER JOB FINISHES
            OrderForecastMetricAsins::where('is_not_ready', 1)->update([
                'is_not_ready' => 0
            ]);

            Log::info('🔓 order_forecast_metrics unlocked (is_not_ready = 0)');
        }
    }
}
