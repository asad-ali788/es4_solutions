<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecastSnapshotAsins;
use App\Services\Api\OpenAIService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAsinAIForecast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $snapshotId;

    public function __construct(int $snapshotId)
    {
        $this->snapshotId = $snapshotId;
    }

    public function handle(OpenAIService $openAIService): void
    {
        $snapshot = OrderForecastSnapshotAsins::find($this->snapshotId);

        if (!$snapshot) {
            Log::channel('ai')->warning('AI Forecast: Snapshot not found', [
                'snapshot_id' => $this->snapshotId
            ]);
            return;
        }

        try {
            $months = collect($snapshot->sales_by_month_last_12_months ?? []);

            if ($months->isEmpty()) {
                throw new \RuntimeException('Last12 sales data missing');
            }

            $forecastYear = Carbon::parse($months->first()['key'])->year;

            $formattedMonths = $months
                ->map(function ($m) {
                    if (empty($m['key'])) {
                        return null;
                    }

                    try {
                        $baselineMonth = Carbon::createFromFormat('Y-m', $m['key'])
                            ->subYear()
                            ->format('Y-m');
                    } catch (\Throwable) {
                        return null;
                    }

                    return [
                        'month'    => $baselineMonth, // ⭐ baseline year
                        'sold'     => (int) ($m['sold'] ?? 0),
                        'asp'      => (float) ($m['asp'] ?? 0),
                        'ad_spend' => (float) ($m['ad_spend'] ?? 0),
                        'ad_sales' => (float) ($m['ad_sales'] ?? 0),
                        'acos'     => (float) ($m['acos'] ?? 0),
                        'tacos'    => (float) ($m['tacos'] ?? 0),
                    ];
                })
                ->filter()
                ->sortBy('month')
                ->values()
                ->toArray();

            if (count($formattedMonths) < 6) {
                throw new \RuntimeException('Insufficient historical months for AI forecast');
            }

            /**
             * Build AI payload
             */
            $payload = [
                'asins' => [
                    [
                        'asin'          => $snapshot->product_asin,
                        'country'       => $snapshot->country,
                        'forecast_year' => $forecastYear, // ⭐ tells AI target year

                        'inventory' => [
                            'fba'     => (int) $snapshot->amazon_stock,
                            'wh'      => (int) $snapshot->warehouse_stock,
                            'inbound' => (int) ($snapshot->inbound_stock ?? 0),
                        ],

                        'last12'         => $formattedMonths,
                        'lead_time_days' => $snapshot->lead_time_days ?? 30,
                    ],
                ],
            ];

            /**
             * Call AI
             */
            $aiResponse = $openAIService->forecastPrediction(
                $payload,
                'demand_forecast_asin_new'
            );

            if (empty($aiResponse['results'])) {
                throw new \RuntimeException('Empty AI response');
            }

            $result = collect($aiResponse['results'])
                ->firstWhere('asin', $snapshot->product_asin);

            if (!$result) {
                throw new \RuntimeException('AI result missing for ASIN');
            }

            $aiFc12Total = collect($result['forecast'] ?? [])
                ->sum(fn($m) => (int) ($m['ai'] ?? 0));

            /**
             * Persist AI output
             */
            $snapshot->update([
                'ai_recommendation_data_by_month_12_months' => $result,
                'ai_fc12_total'                             => $aiFc12Total,
                'run_status' => 'done',
                'run_update' => true,
            ]);

            Log::channel('ai')->info('AI Forecast completed', [
                'snapshot_id' => $this->snapshotId,
                'asin'        => $snapshot->product_asin,
            ]);
        } catch (\Throwable $e) {

            Log::channel('ai')->error('AI Forecast failed', [
                'snapshot_id' => $this->snapshotId,
                'asin'        => $snapshot->product_asin ?? null,
                'error'       => $e->getMessage(),
            ]);

            $snapshot->update([
                'run_status' => 'failed',
                'run_update' => false,
            ]);
        }
    }
}
