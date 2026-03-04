<?php

namespace App\Services;

use App\Models\OrderForecast;
use App\Models\OrderForecastSnapshot;
use App\Models\OrderForecastSnapshotAsins;
use App\Models\OrderForecastMetric;
use App\Models\OrderForecastMetricAsins;
use App\Models\Product;
use App\Models\ProductWhInventory;
use App\Models\FbaInventoryUsa;
use App\Models\AmazonSoldPrice;
use App\Models\InboundShipmentDetailsSp;
use App\Models\Currency;
use App\Models\DailySales;
use App\Models\MonthlySales;
use App\Models\ProductForecast;
use App\Models\ProductForecastAsins;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderForecastSnapshotService
{
    /**
     * Handle both SKU + ASIN snapshots
     */
    public function insertSnapshots(OrderForecast $forecast): void
    {
        $this->insertSnapshotsForForecast($forecast);
        $this->insertSnapshotsForForecastAsins($forecast);
    }

    /**
     * SKU-level snapshots
     */
    public function insertSnapshotsForForecast(OrderForecast $forecast): void
    {
        try {
            $now = now();
            $emptyJsonArray = json_encode([]);

            $conversionRates = Currency::pluck('conversion_rate_to_usd', 'currency_code')->toArray();

            $allProducts = Product::select('id', 'sku', 'short_title')
                ->with([
                    'asins:id,product_id,asin1,asin2,asin3',
                    'listings:id,products_id,country',
                    'listings.additionalDetail:id,product_listings_id,image1'
                ])
                ->get();

            // Build SKU → ASIN map
            $skuAsinMap = [];
            foreach ($allProducts as $product) {
                if ($product->sku && $product->asins) {
                    foreach (['asin1', 'asin2', 'asin3'] as $asinField) {
                        if (!empty($product->asins->$asinField)) {
                            $skuAsinMap[$product->sku][$product->asins->$asinField] = $product->id;
                        }
                    }
                }
            }
            $allSkus = array_keys($skuAsinMap);

            // Prefetch data
            $warehouseStockMap = ProductWhInventory::whereIn('product_id', $allProducts->pluck('id'))
                ->selectRaw("
                    product_id,
                    SUM(available_quantity) as total_available,
                    SUM(CASE WHEN warehouse_id = 2 THEN available_quantity ELSE 0 END) as tactical_stock,
                    SUM(CASE WHEN warehouse_id = 3 THEN available_quantity ELSE 0 END) as afd_stock
                ")
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');

            $inventoryMap = FbaInventoryUsa::whereIn('sku', $allSkus)->pluck('totalstock', 'sku');
            $priceMap     = AmazonSoldPrice::whereIn('seller_sku', $allSkus)->pluck('listing_price', 'seller_sku');

            $inTransitMap = InboundShipmentDetailsSp::whereIn('sku', $allSkus)
                ->get()
                ->groupBy('sku')
                ->mapWithKeys(fn($items, $sku) => [
                    $sku => $items->sum(fn($item) => max(0, ($item->qty_ship ?? 0) - ($item->qty_received ?? 0)))
                ]);

            $ytdSalesMap = DailySales::whereIn('sku', $allSkus)
                ->whereYear('sale_date', $now->year)
                ->get()
                ->groupBy('sku')
                ->map(fn($items) => $items->sum('total_units'));

            $last3MonthsRange = [$now->copy()->subMonths(3)->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
            $last3MonthsSalesMap = DailySales::whereIn('sku', $allSkus)
                ->whereBetween('sale_date', $last3MonthsRange)
                ->get()
                ->groupBy(['sku', fn($item) => Carbon::parse($item->sale_date)->format('Y-m')])
                ->map(fn($months) => collect($months)->map->sum('total_units')->toArray());

            $months = collect(range(0, 11))->map(fn($i) => $now->copy()->addMonths($i)->format('Y-m'));
            $totalSalesData = DailySales::selectRaw("
                sku,
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                SUM(total_revenue) as total_revenue
            ")
                ->whereIn('sku', $allSkus)
                ->whereIn(DB::raw("DATE_FORMAT(sale_date, '%Y-%m')"), $months)
                ->groupBy('sku', 'month')
                ->get();

            $totalSalesMap = [];
            foreach ($totalSalesData as $row) {
                $totalSalesMap[$row->sku][$row->month] = $row->total_revenue;
            }

            $metricsMap = OrderForecastMetric::whereIn('product_sku', $allSkus)
                ->get()
                ->groupBy('product_sku')
                ->map(fn($items) => $items->keyBy(fn($item) => $item->asin1 ?? ''));

            $maps = [
                'inventory'       => $inventoryMap,
                'price'           => $priceMap,
                'in_transit'      => $inTransitMap,
                'ytd'             => $ytdSalesMap,
                'last3'           => $last3MonthsSalesMap,
                'total_sales'     => $totalSalesMap,
                'warehouse_stock' => $warehouseStockMap,
            ];

            $allProducts->chunk(200)->each(function ($products) use ($forecast, $maps, $now, $emptyJsonArray, $conversionRates, $metricsMap, $skuAsinMap) {
                $snapshots = [];

                foreach ($products as $product) {
                    $sku = $product->sku;
                    if (isset($skuAsinMap[$sku])) {
                        foreach ($skuAsinMap[$sku] as $asin => $productId) {
                            $snapshots[] = $this->buildSnapshotData(
                                $product,
                                $forecast,
                                $maps,
                                $now,
                                $emptyJsonArray,
                                $conversionRates,
                                $metricsMap[$sku][$asin] ?? null,
                                $asin
                            );
                        }
                    }
                }

                if (!empty($snapshots)) {
                    OrderForecastSnapshot::insert($snapshots);
                }
            });
        } catch (\Throwable $e) {

            throw $e;
        }
    }

    private function buildSnapshotData(
        Product $product,
        OrderForecast $forecast,
        array $maps,
        Carbon $now,
        string $emptyJsonArray,
        array $conversionRates,
        ?OrderForecastMetric $metricItem,
        string $asin
    ): array {

        $listing = $product->listings->first();
        $country = $listing?->country;

        $metrics = $metricItem?->metrics_by_month ?? [];

        // -------------------------------------------------------
        // Build monthly sales (Y-m => sold)
        // -------------------------------------------------------
        $monthlySales = [];
        foreach ($metrics as $monthKey => $metric) {
            $month = str_replace('fc_month_', '', $monthKey);
            $monthlySales[$month] = (int) ($metric['sold'] ?? 0);
        }

        // Ensure forecast window (next 12 months)
        $months = collect(range(0, 11))
            ->map(fn($i) => $now->copy()->addMonths($i)->format('Y-m'));

        foreach ($months as $month) {
            $monthlySales[$month] = $monthlySales[$month] ?? 0;
        }

        // Calculate SYS SOLD (shared engine)
        $finalAdjusted = $this->calculateSysSold(
            $monthlySales,
            $metricItem->growth_percent ?? 0.10
        );

        // -------------------------------------------------------
        // Build forecastMonths array with SYS SOLD
        // -------------------------------------------------------
        $forecastMonths = [];

        foreach ($months as $monthKey) {

            $metricData = $metrics['fc_month_' . $monthKey] ?? [];

            // ASP conversion for CA / MX
            $asp = $metricData['asp'] ?? 0;
            if (in_array($country, ['CA', 'MX'], true)) {
                $currencyCode = $country === 'CA' ? 'CAD' : 'MXN';
                $rate = $conversionRates[$currencyCode] ?? 1;
                $asp = round($asp * $rate, 2);
            }

            $forecastMonths[] = [
                'key'          => $monthKey,
                'label'        => $metricData['label'] ?? Carbon::parse($monthKey)->format('M Y'),
                'sold'         => isset($metricData['sold']) ? (int) $metricData['sold'] : 0,
                'sys_sold'     => $finalAdjusted[$monthKey] ?? 0,
                'asp'          => $asp,
                'asp_original' => $metricData['asp_original'] ?? $asp,
                'asp_currency' => $metricData['asp_currency'] ?? 'USD',
                'ad_spend'     => $metricData['ad_spend'] ?? 0,
                'ad_sales'     => $metricData['ad_sales'] ?? 0,
                'acos'         => $metricData['acos'] ?? 0,
                'tacos'        => $metricData['tacos'] ?? 0,
            ];
        }

        $last12TotalSold = collect($forecastMonths)
            ->sum(fn($m) => (int) ($m['sold'] ?? 0));

        return [
            'order_forecast_id'             => $forecast->id,
            'product_id'                    => $product->id,
            'product_sku'                   => $product->sku,
            'product_title'                 => $product->short_title,
            'product_img'                   => $listing?->additionalDetail?->image1,
            'product_price'                 => $maps['price'][$product->sku] ?? 0.0,
            'country'                       => $country,
            'amazon_stock'                  => $maps['inventory'][$product->sku] ?? 0,
            'warehouse_stock'               => (($maps['warehouse_stock'][$product->id]->total_available ?? 0)
                + ($maps['warehouse_stock'][$product->id]->tactical_stock ?? 0)
                + ($maps['warehouse_stock'][$product->id]->afd_stock ?? 0)),
            'routes'                        => json_encode(['in_transit' => $maps['in_transit'][$product->sku] ?? 0]),
            'shipment_in_transit'           => $emptyJsonArray,
            'ytd_sales'                     => $maps['ytd'][$product->sku] ?? 0,
            'last12_total_sold'             => $last12TotalSold,

            'sales_by_month_last_3_months'  => json_encode($maps['last3'][$product->sku] ?? []),
            'sales_by_month_last_12_months' => json_encode($forecastMonths),
            'input_data_by_month_12_months' => $emptyJsonArray,
            'created_at'                    => $now,
            'updated_at'                    => $now,
            'forecast_month'                => $now->format('Y-m'),
        ];
    }

    /**
     * ASIN-level snapshots
     */
    public function insertSnapshotsForForecastAsins(OrderForecast $forecast): void
    {
        try {
            $now = now();
            $emptyJsonArray = [];

            $conversionRates = Currency::pluck('conversion_rate_to_usd', 'currency_code')->toArray();

            $allProducts = Product::select('id', 'sku', 'short_title')
                ->with([
                    'asins:id,product_id,asin1,asin2,asin3',
                    'listings:id,products_id,country',
                    'listings.additionalDetail:id,product_listings_id,image1'
                ])
                ->get();

            // Build ASIN -> [SKUs] mapping
            $asinSkuMap = [];
            foreach ($allProducts as $product) {
                if ($product->asins) {
                    foreach (['asin1', 'asin2', 'asin3'] as $field) {
                        $asin = $product->asins->$field;
                        if (!empty($asin)) {
                            $asinSkuMap[$asin][] = $product->sku;
                        }
                    }
                }
            }

            $allSkus  = $allProducts->pluck('sku')->filter()->toArray();
            $allAsins = array_keys($asinSkuMap);

            // SKU-level data sources
            $warehouseStockMap = ProductWhInventory::whereIn('product_id', $allProducts->pluck('id'))
                ->selectRaw("
                product_id,
                    SUM(CASE WHEN warehouse_id = 2 THEN available_quantity ELSE 0 END) as tactical_stock,
                    SUM(CASE WHEN warehouse_id = 3 THEN available_quantity ELSE 0 END) as afd_stock,
                    SUM(CASE WHEN warehouse_id NOT IN (2,3) THEN available_quantity ELSE 0 END) as other_stock,
                    SUM(available_quantity) as total_available
                ")
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');

            $inventoryMap = FbaInventoryUsa::whereIn('sku', $allSkus)->pluck('totalstock', 'sku');
            // $priceMap     = AmazonSoldPrice::whereIn('seller_sku', $allSkus)->pluck('listing_price', 'seller_sku');

            $inTransitMap = InboundShipmentDetailsSp::whereIn('sku', $allSkus)
                ->get()
                ->groupBy('sku')
                ->mapWithKeys(fn($items, $sku) => [
                    $sku => $items->sum(fn($item) => max(0, ($item->qty_ship ?? 0) - ($item->qty_received ?? 0)))
                ]);

            // ASIN-level data sources
            $ytdSalesMap = MonthlySales::whereIn('asin', $allAsins)
                ->whereYear('sale_date', $now->year)
                ->get()
                ->groupBy('asin')
                ->map(fn($items) => $items->sum('total_units'));

            // $last3MonthsRange = [$now->copy()->subMonths(3)->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
            // $last3MonthsSalesMap = MonthlySales::whereIn('asin', $allAsins)
            //     ->whereBetween('sale_date', $last3MonthsRange)
            //     ->get()
            //     ->groupBy(['asin', fn($item) => Carbon::parse($item->sale_date)->format('Y-m')])
            //     ->map(fn($months) => collect($months)->map->sum('total_units')->toArray());

            // --- Last 3 full months (excluding current month) from MonthlySales ---
            $prev3MonthsRange = [
                $now->copy()->subMonths(3)->startOfMonth(),  // e.g. 2025-10-01
                $now->copy()->subMonth()->endOfMonth(),      // e.g. 2025-12-31
            ];

            $prev3MonthsSalesMap = MonthlySales::whereIn('asin', $allAsins)
                ->whereBetween('sale_date', $prev3MonthsRange)
                ->get()
                ->groupBy([
                    'asin',
                    fn($item) => Carbon::parse($item->sale_date)->format('Y-m'),
                ])
                ->map(fn($months) => collect($months)->map->sum('total_units')->toArray());

            // --- Current month MTD from DailySales ---
            $currentMonthRange = [
                $now->copy()->startOfMonth(), // e.g. 2026-01-01
                $now->copy()->endOfMonth(),   // or $now if you want strictly till "today"
            ];

            $currentMonthKey = $now->format('Y-m');

            $currentMonthSalesMap = DailySales::whereIn('asin', $allAsins)
                ->whereBetween('sale_date', $currentMonthRange) // adjust column name if yours differs
                ->get()
                ->groupBy('asin')
                ->map(fn($rows) => (int) $rows->sum('total_units')); // adjust field name if needed

            // --- Merge: asin => [YYYY-MM => units] and ensure current month exists ---
            $last3MonthsSalesMap = $prev3MonthsSalesMap->map(function (array $monthMap, string $asin) use (
                $currentMonthSalesMap,
                $currentMonthKey
            ) {
                $monthMap[$currentMonthKey] = $currentMonthSalesMap[$asin] ?? 0;
                ksort($monthMap);
                return $monthMap;
            });

            // Also include asins that have ONLY current month sales (not present in prev3MonthsSalesMap)
            foreach ($currentMonthSalesMap as $asin => $units) {
                if (!isset($last3MonthsSalesMap[$asin])) {
                    $last3MonthsSalesMap[$asin] = [$currentMonthKey => $units];
                }
            }

            $metricsMap = OrderForecastMetricAsins::whereIn('product_asin', $allAsins)
                ->get()
                ->keyBy('product_asin');

            $productPriceMap = DB::table('product_rankings as pr')
                ->join('product_asins as pa', 'pr.product_id', '=', 'pa.product_id')
                ->select('pa.asin1 as asin', 'pr.current_price', 'pr.country')
                ->whereIn('pa.asin1', $allAsins)
                ->where('pr.country', 'US')
                ->orderBy('pr.created_at', 'DESC')
                ->get()
                ->groupBy('asin')
                ->map(fn($rows) => $rows->first()->current_price);


            // 🚀 Bulk upsert (ASIN aggregated)
            collect($asinSkuMap)->chunk(200)->each(function ($asinSkus) use (
                $forecast,
                $now,
                $emptyJsonArray,
                $conversionRates,
                $metricsMap,
                $inventoryMap,
                $warehouseStockMap,
                $inTransitMap,
                $ytdSalesMap,
                $last3MonthsSalesMap,
                $allProducts,
                $productPriceMap
            ) {
                $snapshots = [];

                foreach ($asinSkus as $asin => $skus) {
                    $amazonStock    = collect($skus)->sum(fn($sku) => $inventoryMap[$sku] ?? 0);
                    $warehouseStock = $allProducts->whereIn('sku', $skus)
                        ->sum(function ($p) use ($warehouseStockMap) {
                            $stock = $warehouseStockMap[$p->id] ?? null;
                            return ($stock->total_available ?? 0)
                                + ($stock->tactical_stock ?? 0)
                                + ($stock->afd_stock ?? 0);
                        });

                    $inTransit      = collect($skus)->sum(fn($sku) => $inTransitMap[$sku] ?? 0);

                    // $sumPrice = collect($skus)->sum(fn($sku) => $priceMap[$sku] ?? 0.0);

                    $currentPrice = $productPriceMap[$asin] ?? 0.0;

                    $repProduct = $allProducts->firstWhere('sku', $skus[0]);
                    $listing = $repProduct?->listings->first();

                    $snapshots[] = $this->buildAsinSnapshotData(
                        $repProduct,
                        $forecast,
                        $asin,
                        // $sumPrice,
                        $currentPrice,
                        $amazonStock,
                        $warehouseStock,
                        $inTransit,
                        $ytdSalesMap[$asin] ?? 0,
                        $last3MonthsSalesMap[$asin] ?? [],
                        $metricsMap[$asin] ?? null,
                        $now,
                        $emptyJsonArray,
                        $conversionRates
                    );
                }

                if (!empty($snapshots)) {
                    OrderForecastSnapshotAsins::upsert(
                        $snapshots,
                        ['order_forecast_id', 'product_asin'],
                        [
                            'product_title',
                            'product_price',
                            'country',
                            'amazon_stock',
                            'warehouse_stock',
                            'routes',
                            'shipment_in_transit',
                            'ytd_sales',
                            'sales_by_month_last_3_months',
                            'sales_by_month_last_12_months',
                            'input_data_by_month_12_months',
                            'sold_values_by_month',
                            'product_img',
                            'order_amount',
                            'updated_at'
                        ]
                    );
                }
            });
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function buildAsinSnapshotData(
        Product $product,
        OrderForecast $forecast,
        string $asin,
        float $sumPrice,
        int $amazonStock,
        int $warehouseStock,
        int $inTransit,
        int $ytdSales,
        array $last3MonthsSales,
        ?OrderForecastMetricAsins $metricItem,
        $now,
        $emptyJsonArray,
        $conversionRates
    ): array {

        $listing = $product->listings->first();
        $country = $listing?->country;

        $forecastMonths = [];
        $metrics = $metricItem?->metrics_by_month ?? [];

        // Sort keys to maintain correct order
        ksort($metrics);

        // -------------------------------------------------------
        // SYS SOLD CALCULATION (USING SHARED ENGINE)
        // -------------------------------------------------------

        // Build monthly sales (Y-m => sold)
        $monthlySales = [];
        foreach ($metrics as $monthKey => $metric) {
            $month = str_replace('fc_month_', '', $monthKey);
            $monthlySales[$month] = (int) ($metric['sold'] ?? 0);
        }

        // Ensure exactly next 12 months exist
        $months = collect(range(0, 11))
            ->map(fn($i) => $now->copy()->addMonths($i)->format('Y-m'));

        foreach ($months as $month) {
            $monthlySales[$month] = $monthlySales[$month] ?? 0;
        }

        // Calculate SYS SOLD (single source of truth)
        $finalAdjusted = $this->calculateSysSold(
            $monthlySales,
            $metricItem->growth_percent ?? 0.10
        );

        // -------------------------------------------------------
        // Build forecastMonths array
        // -------------------------------------------------------
        foreach ($metrics as $monthKey => $metric) {

            $monthKeyFormatted = str_replace('fc_month_', '', $monthKey);

            // ASP conversion for CA / MX
            $asp = $metric['asp'] ?? 0;
            if (in_array($country, ['CA', 'MX'], true)) {
                $currencyCode = $country === 'CA' ? 'CAD' : 'MXN';
                $rate = $conversionRates[$currencyCode] ?? 1;
                $asp = round($asp * $rate, 2);
            }

            $forecastMonths[] = [
                'key'          => $monthKeyFormatted,
                'label'        => $metric['label'] ?? '',
                'sold'         => (int) ($metric['sold'] ?? 0),
                'asp'          => $asp,
                'asp_original' => $metric['asp_original'] ?? $asp,
                'asp_currency' => $metric['asp_currency'] ?? 'USD',
                'ad_spend'     => $metric['ad_spend'] ?? 0,
                'ad_sales'     => $metric['ad_sales'] ?? 0,
                'acos'         => $metric['acos'] ?? 0,
                'tacos'        => $metric['tacos'] ?? 0,
                'sys_sold'     => $finalAdjusted[$monthKeyFormatted] ?? 0,
            ];
        }

        $last12TotalSold = collect($forecastMonths)
            ->sum(fn($m) => (int) ($m['sold'] ?? 0));

        return [
            'order_forecast_id'             => $forecast->id,
            'product_asin'                  => $asin,
            'product_title'                 => $product->short_title,
            'product_price'                 => json_encode($sumPrice),
            'country'                       => $country,
            'amazon_stock'                  => $amazonStock,
            'warehouse_stock'               => $warehouseStock,
            'routes'                        => json_encode(['in_transit' => $inTransit]),
            'shipment_in_transit'           => json_encode($emptyJsonArray),
            'ytd_sales'                     => $ytdSales,
            'last12_total_sold'             => $last12TotalSold,
            'sales_by_month_last_3_months'  => json_encode($last3MonthsSales),
            'sales_by_month_last_12_months' => json_encode($forecastMonths),
            'input_data_by_month_12_months' => json_encode($emptyJsonArray),
            'sold_values_by_month'          => json_encode($emptyJsonArray),
            'product_img'                   => $listing?->additionalDetail?->image1,
            'order_amount'                  => 0,
            'created_at'                    => $now,
            'updated_at'                    => $now,
            'forecast_month'                => $now->format('Y-m'),
        ];
    }


    /**
     * Process snapshots for display in table
     * Works for both SKU and ASIN snapshots
     */
    public function processSnapshots($snapshots, $orderDate): array
    {
        $now = Carbon::parse($orderDate)->startOfMonth();
        return $snapshots->map(function ($snapshot) use ($now) {
            $snapArray = $snapshot->toArray();

            // Last 3 months
            $last3Months = collect(range(0, 3))->map(function ($i) use ($now) {
                $month = $now->copy()->subMonths($i);
                return [
                    'key'   => $month->format('Y-m'),
                    'label' => $month->format('M Y'),
                ];
            })->reverse()->values()->toArray();

            // Next 12 months
            $next12Months = collect(range(0, 11))->map(function ($i) use ($now) {
                $month = $now->copy()->addMonths($i);
                return [
                    'key'   => $month->format('Y-m'),
                    'label' => $month->format('M Y'),
                ];
            });

            $salesByMonth12     = collect($snapArray['sales_by_month_last_12_months'] ?? [])->keyBy('key');
            $soldValues         = $snapArray['sold_values_by_month'] ?? [];
            $aiRecommendations  = $snapArray['ai_recommendation_data_by_month_12_months'] ?? [];

            // 🔥 Support new array format
            $newForecastArray = isset($aiRecommendations['forecast']) && is_array($aiRecommendations['forecast'])
                ? collect($aiRecommendations['forecast'])->keyBy('month')
                : collect();

            // 🔥 Support old array format
            $oldForecastArray = isset($aiRecommendations['forecast_12_months']) && is_array($aiRecommendations['forecast_12_months'])
                ? collect($aiRecommendations['forecast_12_months'])
                : collect();

            $forecastMonths = $next12Months->map(function ($month) use ($salesByMonth12, $soldValues, $newForecastArray, $oldForecastArray) {
                $key = $month['key'];

                // Try NEW Format First
                $aiData = $newForecastArray->get($key);

                // If missing fallback to OLD format
                if (!$aiData) {
                    $aiData = $oldForecastArray->get($key);
                }

                return [
                    'key'          => $key,
                    'label'        => $month['label'],
                    'sold'         => $salesByMonth12[$key]['sold'] ?? 0,
                    'sys_sold'     => $salesByMonth12[$key]['sys_sold'] ?? 0,
                    'asp'          => $salesByMonth12[$key]['asp'] ?? 0,
                    'acos'         => $salesByMonth12[$key]['acos'] ?? 0,
                    'tacos'        => $salesByMonth12[$key]['tacos'] ?? 0,
                    'asp_original' => $salesByMonth12[$key]['asp_original'] ?? null,
                    'asp_currency' => $salesByMonth12[$key]['asp_currency'] ?? null,
                    'input_value'  => $soldValues[$key] ?? '',

                    // Handle NEW FORMAT => ai, sys
                    'ai_sold'      => isset($aiData['ai'])
                        ? (int)$aiData['ai']
                        : (isset($aiData['units']) ? (int)$aiData['units'] : null),
                ];
            })->values()->toArray();

            return [
                ...$snapArray,
                'months_last_3'   => $last3Months,
                'forecast_months' => $forecastMonths,
                'sold_values_sum' => collect($soldValues)->sum(),
                'row_total_stock' => ($snapArray['amazon_stock'] ?? 0) + ($snapArray['warehouse_stock'] ?? 0),
            ];
        })->toArray();
    }


    /**
     * Generate last 3 months for table headers
     */
    public function generateLast3Months(): array
    {
        return collect(range(1, 3))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'key'   => $date->format('Y-m'),
                'label' => $date->format('M Y'),
            ];
        })->reverse()->values()->toArray();
    }

    /**
     * Generate next 12 months for table headers
     */
    public function generateNext12Months(): array
    {
        return collect(range(0, 11))->map(function ($i) {
            $date = now()->addMonths($i);
            return [
                'key'   => $date->format('Y-m'),
                'label' => $date->format('M Y'),
            ];
        })->toArray();
    }

    public function syncFinalizedForecast(OrderForecast $forecast): void
    {
        $now = now();
        $chunkSize = 100; // adjust as needed for MySQL limits

        DB::transaction(function () use ($forecast, $now, $chunkSize): void {

            // Generate next 12 months starting from forecast order_date
            $startMonth = optional($forecast->order_date)->format('Y-m') ?? $now->format('Y-m');
            $months = collect(range(0, 11))
                ->map(fn($i) => now()->parse($startMonth . '-01')->addMonths($i)->format('Y-m'));

            // ---------- SKU-level forecasts ----------
            $skuUpsert = $forecast->snapshots
                ->flatMap(function ($snapshot) use ($months, $now): array {

                    $soldValues = is_array($snapshot->sold_values_by_month)
                        ? $snapshot->sold_values_by_month
                        : json_decode($snapshot->sold_values_by_month, true);

                    $aiData = is_array($snapshot->ai_recommendation_data_by_month_12_months)
                        ? $snapshot->ai_recommendation_data_by_month_12_months
                        : json_decode($snapshot->ai_recommendation_data_by_month_12_months, true);

                    return $months->map(function ($month) use ($snapshot, $soldValues, $aiData, $now): array {

                        // Step1: user input value
                        $userInput = $soldValues[$month] ?? null;

                        // Step2: extract sys_sold from sales_by_month_last_12_months array
                        $sysSold = null;
                        if (!empty($snapshot->sales_by_month_last_12_months)) {
                            $decodedMonths = is_string($snapshot->sales_by_month_last_12_months)
                                ? json_decode($snapshot->sales_by_month_last_12_months, true)
                                : $snapshot->sales_by_month_last_12_months;


                            if (is_array($decodedMonths)) {
                                foreach ($decodedMonths as $row) {
                                    if (($row['key'] ?? null) === $month) {
                                        $sysSold = $row['sys_sold'] ?? null;
                                        break;
                                    }
                                }
                            }
                        }

                        // Step3: extract AI units
                        $aiUnits = $aiData['forecast_12_months'][$month]['units'] ?? 0;

                        // priority condition
                        $forecastUnits =
                            $userInput !== null
                            ? (int) $userInput
                            : ($sysSold !== null ? (int) $sysSold : (int) $aiUnits);

                        $monthNumber = (int) substr($month, 5, 2);
                        $forecastHalf = $monthNumber <= 6 ? 'H1' : 'H2';

                        return [
                            'product_id'        => $snapshot->product_id,
                            'forecast_month'    => $month . '-01',
                            'forecast_half'     => $forecastHalf,
                            'forecast_units'    => $forecastUnits,
                            'actual_units_sold' => 0,
                            'updated_at'        => $now,
                            'created_at'        => $now,
                        ];
                    })->toArray();
                })->toArray();

            foreach (array_chunk($skuUpsert, $chunkSize) as $chunk) {
                ProductForecast::upsert(
                    $chunk,
                    ['product_id', 'forecast_month', 'forecast_half'],
                    ['forecast_units', 'actual_units_sold', 'updated_at']
                );
            }

            // ---------- ASIN-level forecasts ----------
            $asinUpsert = $forecast->snapshotAsins
                ->flatMap(function ($snapshotAsin) use ($months, $now): array {

                    $soldValues = is_array($snapshotAsin->sold_values_by_month)
                        ? $snapshotAsin->sold_values_by_month
                        : json_decode($snapshotAsin->sold_values_by_month, true);

                    $aiData = is_array($snapshotAsin->ai_recommendation_data_by_month_12_months)
                        ? $snapshotAsin->ai_recommendation_data_by_month_12_months
                        : json_decode($snapshotAsin->ai_recommendation_data_by_month_12_months, true);

                    return $months->map(function ($month) use ($snapshotAsin, $soldValues, $aiData, $now): array {

                        // Step1: user input value
                        $userInput = $soldValues[$month] ?? null;

                        // Step2: extract sys_sold from 12M array
                        $sysSold = null;
                        if (!empty($snapshotAsin->sales_by_month_last_12_months)) {
                            $decodedMonths = is_string($snapshotAsin->sales_by_month_last_12_months)
                                ? json_decode($snapshotAsin->sales_by_month_last_12_months, true)
                                : $snapshotAsin->sales_by_month_last_12_months;

                            if (is_array($decodedMonths)) {
                                foreach ($decodedMonths as $row) {
                                    if (($row['key'] ?? null) === $month) {
                                        $sysSold = $row['sys_sold'] ?? null;
                                        break;
                                    }
                                }
                            }
                        }

                        // Step3: extract AI units
                        $aiUnits = $aiData['forecast_12_months'][$month]['units'] ?? 0;

                        // priority condition
                        $forecastUnits =
                            $userInput !== null
                            ? (int) $userInput
                            : ($sysSold !== null ? (int) $sysSold : (int) $aiUnits);

                        $monthNumber = (int) substr($month, 5, 2);
                        $forecastHalf = $monthNumber <= 6 ? 'H1' : 'H2';

                        return [
                            'product_asin'      => $snapshotAsin->product_asin,
                            'forecast_month'    => $month . '-01',
                            'forecast_half'     => $forecastHalf,
                            'forecast_units'    => $forecastUnits,
                            'actual_units_sold' => 0,
                            'updated_at'        => $now,
                            'created_at'        => $now,
                        ];
                    })->toArray();
                })->toArray();

            foreach (array_chunk($asinUpsert, $chunkSize) as $chunk) {
                ProductForecastAsins::upsert(
                    $chunk,
                    ['product_asin', 'forecast_month', 'forecast_half'],
                    ['forecast_units', 'actual_units_sold', 'updated_at']
                );
            }
        });
    }

    public function handleGenerateAI(Request $request, string $modelClass, string $jobClass)
    {
        $request->validate([
            'snapshot_id' => 'required|integer|exists:' . (new $modelClass)->getTable() . ',id',
        ]);

        $snapshotId = $request->snapshot_id;

        $modelClass::where('id', $snapshotId)->update(['run_status' => 'dispatched']);

        $jobClass::dispatch($snapshotId)->onQueue('ai');

        return response()->json([
            'success' => true,
            'message' => '🧠 AI forecast is being generated.',
            'snapshot_id' => $snapshotId,
        ]);
    }

    public function handleGetStatus($id, string $modelClass)
    {
        try {
            $snapshot = $modelClass::find($id);

            if (!$snapshot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot not found.'
                ], 404);
            }

            // Fetch forecast directly
            $forecastData = $snapshot->ai_recommendation_data_by_month_12_months['forecast'] ?? [];

            return response()->json([
                'success' => true,
                'run_update' => $snapshot->run_update,
                'forecast' => $forecastData, // directly pass the forecast array
            ]);
        } catch (\Exception $e) {
            Log::error('Snapshot status fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed. Please contact admin.'
            ], 500);
        }
    }

    public function calculateSysSold(array $monthlySales, float $growthPercent): array
    {
        // Ensure exactly 12 months & sorted
        ksort($monthlySales);
        $monthlySales = array_slice($monthlySales, -12, 12, true);

        // Annual total & monthly average
        $annualTotal = array_sum($monthlySales);
        $annualTotal = $annualTotal > 0 ? $annualTotal : 1;
        $monthlyAvg  = $annualTotal / 12;

        // Detect seasonality
        $peakMonthValue = max($monthlySales);
        $isSeasonal = ($peakMonthValue / $monthlyAvg) >= 1.2;

        // Seasonality index
        $seasonalityIndex = [];
        foreach ($monthlySales as $month => $value) {
            $seasonalityIndex[$month] = $value / $annualTotal;
        }

        // Growth adjustment (cap for seasonal ASIN)
        $effectiveGrowth = $isSeasonal
            ? min($growthPercent, 0.07)
            : $growthPercent;

        $nextYearTotal = $annualTotal * (1 + $effectiveGrowth);

        // Base seasonal forecast
        $baseForecast = [];
        foreach ($seasonalityIndex as $month => $index) {
            $baseForecast[$month] = round($index * $nextYearTotal);
        }

        // Recent 3-month average
        $recentSales = array_slice($monthlySales, -3);
        $recentAvg   = array_sum($recentSales) / max(count($recentSales), 1);

        // Identify peak months
        $peakThreshold = $monthlyAvg * 1.2;
        $peakMonths   = [];

        foreach ($monthlySales as $month => $value) {
            if ($value >= $peakThreshold) {
                $peakMonths[] = $month;
            }
        }

        // Final SYS SOLD with smoothing + safety floor + off-season guard
        $finalAdjusted = [];

        foreach ($baseForecast as $month => $seasonalValue) {

            // if (in_array($month, $peakMonths, true)) {
            //     $seasonalWeight = 0.95;
            //     $recentWeight   = 0.05;
            // } elseif ($isSeasonal) {
            //     $seasonalWeight = 0.85;
            //     $recentWeight   = 0.15;
            // } else {
            //     $seasonalWeight = 0.80;
            //     $recentWeight   = 0.20;
            // }


            // $adjusted =
            //     ($seasonalValue * $seasonalWeight) +
            //     ($recentAvg * $recentWeight);


            if (in_array($month, $peakMonths, true)) {
                // Peak months: trust seasonality fully
                $adjusted = $seasonalValue;
            } elseif ($isSeasonal) {
                // Seasonal ASIN: still trust seasonality
                $adjusted = $seasonalValue;
            } else {
                // Non-seasonal ASIN: trust recent demand
                $adjusted = $recentAvg;
            }


            // Safety floor
            $lastYear = $monthlySales[$month] ?? 0;
            $floor    = $lastYear * 0.6;

            $final = max($adjusted, $floor);

            /* -----------------------------
            * Off-season inflation guard
            * ----------------------------- */
            $isOffSeason    = !in_array($month, $peakMonths, true);
            $upliftedSales  = $lastYear * (1 + $effectiveGrowth);

            if ($isOffSeason && $final > $upliftedSales) {
                // Use base seasonal forecast instead of inflated value
                $final = $seasonalValue;
            }

            $finalAdjusted[$month] = (int) round($final);
        }

        return $finalAdjusted;
    }
}
