<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecastSnapshotAsins;
use App\Services\Api\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAsinAIForecastBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $forecastId;
    protected int $batchSize = 20;

    public function __construct(int $forecastId)
    {
        $this->forecastId = $forecastId;
    }

    public function handle(OpenAIService $openAIService)
    {
        // SAFETY: reset any snapshots left in 'running' from previous crashes
        OrderForecastSnapshotAsins::where('order_forecast_id', $this->forecastId)
            ->where('run_status', 'running')
            ->update(['run_status' => 'dispatched']);

        while (true) {

            // Fetch next batch of dispatched/pending snapshots
            $batch = OrderForecastSnapshotAsins::where('order_forecast_id', $this->forecastId)
                ->whereIn('run_status', ['dispatched', 'pending'])
                ->limit($this->batchSize)
                ->get();

            if ($batch->isEmpty()) {
                Log::channel('ai')->info("No more ASINs to process for forecast {$this->forecastId}");
                break;
            }

            Log::channel('ai')->info("Processing batch of {$batch->count()} ASINs");

            // Mark batch as running
            $batch->each(fn($snapshot) => $snapshot->update(['run_status' => 'running']));

            try {
                foreach ($batch as $snapshot) {
                    try {
                        // PREPARE INPUT
                        $now = now();
                        $next12Months = collect(range(0, 11))->map(fn($i) => [
                            'key' => $now->copy()->addMonths($i)->format('Y-m'),
                            'label' => $now->copy()->addMonths($i)->format('M Y'),
                        ]);

                        $salesByMonth12 = collect($snapshot->sales_by_month_last_12_months ?? [])->keyBy('key');

                        $asinMetrics = [
                            'asin' => $snapshot->product_asin,
                            'country' => $snapshot->country,
                            'amazon_stock' => $snapshot->amazon_stock,
                            'warehouse_stock' => $snapshot->warehouse_stock,
                            'ytd_sales' => $snapshot->ytd_sales,
                            'past_12_months_sales' => $salesByMonth12->map(function ($m) {
                                return [
                                    'month' => $m['key'],
                                    'sold' => (int) $m['sold'],
                                    'asp' => (float) $m['asp'],
                                    'ad_spend' => (float) $m['ad_spend'],
                                    'ad_sales' => (float) $m['ad_sales'],
                                    'acos' => (float) $m['acos'],
                                    'tacos' => (float) $m['tacos'],
                                ];
                            })->values(),
                            'next_12_months' => $next12Months->pluck('key')->toArray(),
                        ];

                        // AI CALL
                        $aiResults = $openAIService->forecastPrediction($asinMetrics, 'order_forecast_asin');

                        if (!$aiResults) {
                            $snapshot->update(['run_status' => 'failed']);
                            continue;
                        }

                        // PREPARE RESULT
                        $aiMonths = collect($aiResults)->map(function ($metrics, $key) use ($next12Months) {
                            $label = $next12Months->firstWhere('key', $key)['label'] ?? $key;
                            return [
                                'ai_key' => $key,
                                'ai_label' => $label,
                                'ai_sold' => $metrics['ai_sold'] ?? 0,
                                'ai_asp' => $metrics['ai_asp'] ?? 0,
                                'ai_ad_spend' => $metrics['ai_ad_spend'] ?? 0,
                                'ai_ad_sales' => $metrics['ai_ad_sales'] ?? 0,
                                'ai_acos' => $metrics['ai_acos'] ?? 0,
                                'ai_tacos' => $metrics['ai_tacos'] ?? 0,
                                'recommendations' => $metrics['recommendations'] ?? [],
                            ];
                        })->toArray();

                        // SAVE RESULT
                        $snapshot->update([
                            'ai_recommendation_data_by_month_12_months' => $aiMonths,
                            'run_status' => 'done',
                            'run_update' => 1
                        ]);
                    } catch (\Throwable $e) {
                        $snapshot->update(['run_status' => 'failed']);
                        Log::channel('ai')->error("AI error for snapshot {$snapshot->id}: " . $e->getMessage());
                    }
                }
            } finally {
                // SAFETY: revert any remaining 'running' snapshots to 'dispatched' for resumability
                OrderForecastSnapshotAsins::where('order_forecast_id', $this->forecastId)
                    ->where('run_status', 'running')
                    ->update(['run_status' => 'dispatched']);
            }

            // Optional sleep to reduce API load
            sleep(10);
        }

        Log::channel('ai')->info("Batch processing completed for forecast {$this->forecastId}");
    }
}
