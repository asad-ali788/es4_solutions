<?php

namespace App\Jobs\Forecast;

use App\Models\Product;
use App\Models\Currency;
use App\Models\OrderForecastMetricAsins;
use App\Models\MonthlyAdsProductPerformance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ProcessOrderForecastMetricsAsinJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $timeout = 3600;

    public function handle(): void
    {
        Log::info('🚀 Starting ASIN-level forecast job');

        $conversionRates = Currency::pluck('conversion_rate_to_usd', 'currency_code')->toArray();

        $now = now()->startOfMonth();

        /**
         * Forecast months (future)
         * Previous-year months (data source)
         */
        $forecastMonths = [];
        $prevYearMap    = [];

        for ($i = 0; $i < 12; $i++) {
            $forecastYm = $now->copy()->addMonths($i)->format('Y-m');
            $forecastMonths[] = $forecastYm;
            $prevYearMap[$forecastYm] = $now->copy()->addMonths($i)->subYear()->format('Y-m');
        }

        /**
         * SQL-equivalent date window
         * BETWEEN DATE_SUB(now, 1 YEAR) AND DATE_SUB(now, 1 MONTH)
         */
        $startDate = $now->copy()->subYear()->startOfMonth()->toDateString();
        $endDate   = $now->copy()->subMonth()->endOfMonth()->toDateString();

        Product::query()
            ->select(['id', 'sku'])
            ->whereNotNull('sku')
            ->with([
                'asins:id,product_id,asin1,asin2,asin3',
                'listings:id,products_id,country',
            ])
            ->chunkById(300, function ($products) use (
                $conversionRates,
                $forecastMonths,
                $prevYearMap,
                $startDate,
                $endDate
            ) {
                foreach ($products as $product) {
                    try {
                        $this->processProduct(
                            $product,
                            $conversionRates,
                            $forecastMonths,
                            $prevYearMap,
                            $startDate,
                            $endDate
                        );
                    } catch (\Throwable $e) {
                        Log::error("❌ Product {$product->id} failed", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('✅ Finished ASIN-level forecast job');
    }

    private function processProduct(
        Product $product,
        array $conversionRates,
        array $forecastMonths,
        array $prevYearMap,
        string $startDate,
        string $endDate
    ): void {
        $listing = $product->listings->first();

        $currencyCode = match ($listing?->country) {
            'CA' => 'CAD',
            'MX' => 'MXN',
            default => 'USD',
        };

        $asins = array_values(array_filter([
            $product->asins->asin1 ?? null,
            $product->asins->asin2 ?? null,
            $product->asins->asin3 ?? null,
        ]));

        if (!$asins) {
            return;
        }

        /**
         * Skip already processed ASINs
         */
        $existing = OrderForecastMetricAsins::whereIn('product_asin', $asins)
            ->pluck('product_asin')
            ->all();

        $asinsToProcess = array_diff($asins, $existing);
        if (!$asinsToProcess) {
            return;
        }

        /**
         * Fetch only SQL-relevant rows
         */
        $perfRows = MonthlyAdsProductPerformance::query()
            // ->where('sku', $product->sku)
            ->whereIn('asin', $asinsToProcess)
            ->whereBetween('month', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($r) => "{$r->asin}|" . substr($r->month, 0, 7));

        foreach ($asinsToProcess as $asin) {
            $metrics = [];

            foreach ($forecastMonths as $forecastYm) {
                $sourceYm = $prevYearMap[$forecastYm];

                /** @var Collection $rows */
                $rows = $perfRows["{$asin}|{$sourceYm}"] ?? collect();

                // === EXACT SQL AGGREGATION ===
                $sold    = (int) $rows->sum('sold');
                $revenue = (float) $rows->sum('revenue');
                $adSpend = (float) $rows->sum('ad_spend');
                $adSales = (float) $rows->sum('ad_sales');
                $acos    = (float) ($rows->avg('acos') ?? 0);
                $tacos   = (float) ($rows->avg('tacos') ?? 0);

                $aspOriginal = $sold > 0 ? round($revenue / $sold, 2) : 0.0;

                $asp = $currencyCode !== 'USD'
                    ? round($aspOriginal * ($conversionRates[$currencyCode] ?? 1), 2)
                    : $aspOriginal;

                $metrics["fc_month_{$forecastYm}"] = [
                    'label'         => date('M Y', strtotime($forecastYm)),
                    'sold'          => $sold,
                    'asp'           => $asp,
                    'asp_original'  => $aspOriginal,
                    'asp_currency'  => $currencyCode,
                    'ad_spend'      => $adSpend,
                    'ad_sales'      => $adSales,
                    'acos'          => $acos,
                    'tacos'         => $tacos,
                    'actual_date'   => date('M Y', strtotime($sourceYm)),
                ];
            }

            OrderForecastMetricAsins::create([
                'product_asin'     => $asin,
                'metrics_by_month' => $metrics,
            ]);
        }
    }
}
