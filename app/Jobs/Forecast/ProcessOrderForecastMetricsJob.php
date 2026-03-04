<?php

namespace App\Jobs\Forecast;

use App\Models\Currency;
use App\Models\Product;
use App\Models\OrderForecastMetric;
use App\Models\MonthlyAdsProductPerformance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessOrderForecastMetricsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $timeout = 3600;

    public function handle(): void
    {
        Log::info('🚀 Starting all-products forecast metrics job');

        // Cache currency rates once
        $conversionRates = Currency::pluck('conversion_rate_to_usd', 'currency_code')->toArray();

        Product::query()
            ->select(['id', 'sku'])
            ->whereNotNull('sku')
            ->with([
                'asins:id,product_id,asin1,asin2,asin3',
                'listings:id,products_id,country',
            ])
            ->chunkById(500, function ($products, $page) use ($conversionRates) {

                Log::info("📦 Processing batch {$page}");

                foreach ($products as $product) {
                    try {
                        $this->processProduct($product, $conversionRates);
                    } catch (\Throwable $e) {
                        Log::error("❌ Product {$product->id} failed", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                gc_collect_cycles();
            });

        Log::info('✅ Finished all-products forecast metrics job');
    }

    private function processProduct(Product $product, array $conversionRates): void
    {
        $sku = $product->sku;
        if (!$sku || !$product->relationLoaded('asins')) {
            return;
        }

        /** -------------------------------
         * Currency resolution
         * -------------------------------- */
        $country = $product->listings->first()?->country;
        $currencyCode = match ($country) {
            'CA' => 'CAD',
            'MX' => 'MXN',
            default => 'USD',
        };

        /** -------------------------------
         * Collect ASINs
         * -------------------------------- */
        $asins = array_values(array_filter([
            $product->asins->asin1 ?? null,
            $product->asins->asin2 ?? null,
            $product->asins->asin3 ?? null,
        ]));

        if (empty($asins)) {
            return;
        }

        /** -------------------------------
         * Forecast months (next 12)
         * -------------------------------- */
        $now = now()->startOfMonth();

        $forecastMonths = collect(range(0, 11))
            ->map(fn($i) => $now->copy()->addMonths($i)->format('Y-m'));

        /** -------------------------------
         * Previous year range (index-safe)
         * -------------------------------- */
        $prevYearStart = $now->copy()->subYear()->toDateString();
        $prevYearEnd   = $now->copy()->subYear()->addMonths(12)->toDateString();

        /** -------------------------------
         * Skip already generated ASINs
         * -------------------------------- */
        $existingAsins = OrderForecastMetric::where('product_sku', $sku)
            ->whereIn('asin1', $asins)
            ->pluck('asin1')
            ->all();

        $asinsToProcess = array_values(array_diff($asins, $existingAsins));
        if (empty($asinsToProcess)) {
            return;
        }

        /** -------------------------------
         * Fetch monthly performance ONCE
         * -------------------------------- */
        $perfRows = MonthlyAdsProductPerformance::query()
            ->where('sku', $sku)
            ->whereIn('asin', $asinsToProcess)
            ->whereBetween('month', [$prevYearStart, $prevYearEnd])
            ->get()
            ->groupBy(fn($row) => "{$row->asin}|" . substr($row->month, 0, 7));

        /** -------------------------------
         * Build metrics in memory
         * -------------------------------- */
        foreach ($asinsToProcess as $asin) {

            $metrics = [];

            foreach ($forecastMonths as $monthKey) {
                $prevYearKey = Carbon::createFromFormat('Y-m', $monthKey)
                    ->subYear()
                    ->format('Y-m');

                $perf = $perfRows["{$asin}|{$prevYearKey}"]->first() ?? null;

                $sold    = (int) ($perf?->sold ?? 0);
                $revenue = (float) ($perf?->revenue ?? 0.0);

                $aspOriginal = $sold > 0 ? round($revenue / $sold, 2) : 0.0;
                $asp = $currencyCode !== 'USD'
                    ? round($aspOriginal * ($conversionRates[$currencyCode] ?? 1), 2)
                    : $aspOriginal;

                $metrics["fc_month_{$monthKey}"] = [
                    'key'           => $monthKey,
                    'label'         => Carbon::createFromFormat('Y-m', $monthKey)->format('M Y'),
                    'sold'          => $sold,
                    'asp'           => $asp,
                    'asp_original'  => $aspOriginal,
                    'asp_currency'  => $currencyCode,
                    'ad_spend'      => (float) ($perf?->ad_spend ?? 0),
                    'ad_sales'      => (float) ($perf?->ad_sales ?? 0),
                    'acos'          => (float) ($perf?->acos ?? 0),
                    'tacos'         => (float) ($perf?->tacos ?? 0),
                    'actual_date'   => Carbon::createFromFormat('Y-m', $monthKey)
                        ->subYear()
                        ->format('M Y'),
                ];
            }

            /** -------------------------------
             * Persist forecast
             * -------------------------------- */
            OrderForecastMetric::create([
                'product_id'       => $product->id,
                'product_sku'      => $sku,
                'asin1'            => $asin,
                'metrics_by_month' => $metrics,
            ]);
        }
    }
}
