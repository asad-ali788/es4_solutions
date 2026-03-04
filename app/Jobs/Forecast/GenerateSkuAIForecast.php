<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecastSnapshot;
use App\Services\Api\OpenAIService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSkuAIForecast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $snapshotId;

    public function __construct(int $snapshotId)
    {
        $this->snapshotId = $snapshotId;
    }

    public function handle(OpenAIService $openAIService): void
    {
        $snapshot = OrderForecastSnapshot::find($this->snapshotId);

        if (!$snapshot) {
            Log::channel('ai')->warning("Forecast Job: Snapshot not found (ID: {$this->snapshotId})");
            return;
        }

        try {
            $months = collect($snapshot->sales_by_month_last_12_months ?? []);

            $formattedMonths = $months->map(function ($m) {
                $monthStr = $m['key'] ?? null;

                if ($monthStr) {
                    try {
                        $dt = Carbon::parse($monthStr)->subYear();
                        $shiftedMonth = $dt->format('Y-m');
                    } catch (\Exception $e) {
                        $shiftedMonth = $monthStr;
                    }
                } else {
                    $shiftedMonth = null;
                }

                return [
                    'month'    => $shiftedMonth,
                    'sold'     => (int) ($m['sold'] ?? 0),
                    'asp'      => (float) ($m['asp'] ?? 0),
                    'ad_spend' => (float) ($m['ad_spend'] ?? 0),
                    'ad_sales' => (float) ($m['ad_sales'] ?? 0),
                    'acos'     => (float) ($m['acos'] ?? 0),
                    'tacos'    => (float) ($m['tacos'] ?? 0),
                ];
            })->toArray();

            // Build SKU payload
            $payload = [
                'skus' => [
                    [
                        'sku'    => $snapshot->product_sku,
                        'country' => $snapshot->country,
                        'inventory' => [
                            'fba'     => (int) $snapshot->amazon_stock,
                            'wh'      => (int) $snapshot->warehouse_stock,
                            'inbound' => (int) ($snapshot->inbound_stock ?? 0),
                        ],
                        'last12' => $formattedMonths,
                        'lead_time_days' => $snapshot->lead_time_days ?? 30,
                    ]
                ]
            ];

            Log::channel('ai')->info("Forecast Job: Sending payload for SKU: {$snapshot->product_sku}");

            $aiResponse = $openAIService->forecastPrediction($payload, 'demand_forecast_sku_new');

            if (empty($aiResponse['results'])) {
                Log::channel('ai')->error("Forecast Job: Empty AI response", [
                    'snapshot_id' => $this->snapshotId
                ]);

                $snapshot->update([
                    'run_status' => 'failed',
                    'run_update' => false,
                ]);
                return;
            }

            // Match returning SKU
            $result = collect($aiResponse['results'])
                ->firstWhere('sku', $snapshot->product_sku);

            if (!$result) {
                Log::channel('ai')->error("Forecast Job: SKU result missing", [
                    'sku' => $snapshot->product_sku,
                    'snapshot_id' => $this->snapshotId
                ]);

                $snapshot->update(['run_status' => 'failed']);
                return;
            }

            $aiFc12Total = collect($result['forecast'] ?? [])
                ->sum(fn($m) => (int) ($m['ai'] ?? 0));

            // Save AI recommendation
            $snapshot->update([
                'ai_recommendation_data_by_month_12_months' => $result,
                'ai_fc12_total'                             => $aiFc12Total,
                'run_status' => 'done',
                'run_update' => true,
            ]);

            Log::channel('ai')->info("Forecast Job: Completed (SKU: {$snapshot->product_sku}, Snapshot: {$this->snapshotId})");
        } catch (\Throwable $e) {

            Log::channel('ai')->error("Forecast Job Exception", [
                'snapshot_id' => $this->snapshotId,
                'error' => $e->getMessage(),
            ]);

            $snapshot->update(['run_status' => 'failed']);
        }
    }
}
