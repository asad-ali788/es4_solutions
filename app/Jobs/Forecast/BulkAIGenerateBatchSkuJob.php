<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecastSnapshot;
use App\Services\Api\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkAIGenerateBatchSkuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $forecastId;
    protected int $batchSize = 20;
    protected int $maxRetries = 3;

    public function __construct(int $forecastId)
    {
        $this->forecastId = $forecastId;
    }

    public function handle(OpenAIService $openAIService): void
    {
        Log::channel('ai')->info("Bulk SKU AI Job started for forecast {$this->forecastId}");

        while (true) {
            $batch = OrderForecastSnapshot::where('order_forecast_id', $this->forecastId)
                ->where('run_status', 'dispatched')
                ->limit($this->batchSize)
                ->get();

            if ($batch->isEmpty()) {
                Log::channel('ai')->info("No more dispatched SKU rows. Forecast {$this->forecastId} completed.");
                break;
            }

            Log::channel('ai')->info("Processing batch of {$batch->count()} SKU snapshots");

            $payload = $batch->map(function ($snapshot) {
                $now = now();
                $next12Months = collect(range(0, 11))
                    ->map(fn($i) => $now->copy()->addMonths($i)->format('Y-m'))
                    ->toArray();

                $salesByMonth12 = collect($snapshot->sales_by_month_last_12_months ?? [])
                    ->keyBy('key');

                return [
                    'snapshot_id' => $snapshot->id,
                    'sku' => $snapshot->product_sku,
                    'country' => $snapshot->country,
                    'amazon_stock' => $snapshot->amazon_stock,
                    'warehouse_stock' => $snapshot->warehouse_stock,
                    'ytd_sales' => $snapshot->ytd_sales,
                    'past_12_months_sales' => $salesByMonth12->map(fn($m) => [
                        'month' => $m['key'],
                        'sold' => (int)($m['sold'] ?? 0),
                        'asp' => (float)($m['asp'] ?? 0),
                        'ad_spend' => (float)($m['ad_spend'] ?? 0),
                        'ad_sales' => (float)($m['ad_sales'] ?? 0),
                        'acos' => (float)($m['acos'] ?? 0),
                        'tacos' => (float)($m['tacos'] ?? 0),
                    ])->values(),
                    'next_12_months' => $next12Months,
                ];
            })->values()->toArray();

            // Retry loop
            $retryCount = 0;
            do {
                $results = $openAIService->forecastBulkPrediction($payload, 'order_forecast_sku_bulk');
                if (is_array($results)) break;

                $retryCount++;
                Log::channel('ai')->warning("Invalid SKU AI response. Retry {$retryCount}/{$this->maxRetries} for this batch.");
                sleep(5);
            } while ($retryCount < $this->maxRetries);

            if (!is_array($results)) {
                Log::channel('ai')->error("SKU batch failed after {$this->maxRetries} retries. Marking snapshots failed.");
                foreach ($batch as $snapshot) {
                    $snapshot->update(['run_status' => 'failed']);
                }
                continue;
            }

            // Update snapshots
            foreach ($batch as $snapshot) {
                $snapshotId = $snapshot->id;

                if (!isset($results[$snapshotId])) {
                    Log::channel('ai')->warning("AI SKU response missing for snapshot {$snapshotId}");
                    $snapshot->update(['run_status' => 'failed']);
                    continue;
                }

                $snapshot->update([
                    'ai_recommendation_data_by_month_12_months' => $results[$snapshotId],
                    'run_status' => 'done',
                    'run_update' => true,
                ]);
            }

            Log::channel('ai')->info("SKU batch processed successfully. Sleeping 10 sec...");
            sleep(10);
        }

        Log::channel('ai')->info("Bulk SKU AI Job completed for forecast {$this->forecastId}");
    }
}
