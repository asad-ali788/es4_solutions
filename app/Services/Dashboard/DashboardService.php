<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;
use App\Models\AmzAdsCampaignPerformanceReport;
use App\Models\AmzAdsCampaignSBPerformanceReport;
use App\Models\AmzAdsCampaignPerformanceReportSd;
use App\Models\DailySales;
use App\Models\MonthlySales;

class DashboardService
{

    public function topSellingProducts(string $marketTz): array
    {
        $yesterday = Carbon::now($marketTz)->subDay()->toDateString();

        $salesKey = "daily_sales:v1:{$yesterday}:{$marketTz}";
        $topKey   = "top_selling_products:v2:{$yesterday}:{$marketTz}";

        $sales = Cache::remember($salesKey, 3600, function () use ($yesterday) {
            return DailySales::whereDate('sale_date', $yesterday)
                ->select('sku', 'asin', 'sale_date', 'marketplace_id', 'currency', 'total_units', 'total_revenue')
                ->get();
        });

        return Cache::remember($topKey, 3600, function () use ($sales) {
            return $sales
                ->whereIn('marketplace_id', ['Amazon.com', 'Amazon.ca', 'Amazon.com.mx'])
                ->groupBy('asin')
                ->map(fn($group) => [
                    'asin'          => $group->first()?->asin,
                    'sale_date'     => $group->first()?->sale_date,
                    'total_units'   => $group->sum('total_units'),
                    'total_revenue' => $group->sum('total_revenue'),
                ])
                ->sortByDesc('total_units')
                ->take(10)
                ->values()
                ->toArray(); // ✅ ALWAYS array
        });
    }


    public function getMonthToDateAndYearComparisons(string $marketTz, $marketplaceMap): array
    {
        $yesterday = Carbon::now($marketTz)->subDay();
        $yearStart = $yesterday->copy()->startOfMonth();

        $cacheKey = 'sales_summary_month_to_date_and_year_comparisons';

        return Cache::remember($cacheKey, 3600, function () use ($marketplaceMap, $yesterday, $yearStart) {
            $currencyRates = Currency::pluck('conversion_rate_to_usd', 'country_code');

            // Flatten marketplace IDs
            $allMarketplaceIds = collect($marketplaceMap)->flatten()->unique()->values();

            $summaryTotals = [
                'total_units_ty'                  => 0,
                'total_revenue_usd_ty'            => 0.0,
                'total_units_ly'                  => 0,
                'total_revenue_usd_ly'            => 0.0,
                'total_units_last_month'          => 0,
                'total_revenue_usd_last_month'    => 0.0,
                'total_units_last_month_ly'       => 0,
                'total_revenue_usd_last_month_ly' => 0.0,
            ];

            /* ---------------------------
         *  THIS YEAR (Month-to-date)
         * ---------------------------
         */
            $currentYearDailySales = Cache::remember(
                "daily_sales_ytm",
                3600,
                function () use ($yearStart, $yesterday, $allMarketplaceIds) {
                    return DailySales::whereBetween('sale_date', [$yearStart->toDateString(), $yesterday->toDateString()])
                        ->whereIn('marketplace_id', $allMarketplaceIds)
                        ->select('marketplace_id', 'currency', 'total_units', 'total_revenue')
                        ->get();
                }
            );

            /* ------------------------------------
         *  LAST YEAR (same month, full month)
         * ------------------------------------
         */
            $lastYear      = $yesterday->copy()->subYear();
            $lastYearStart = $lastYear->copy()->startOfMonth();
            $lastYearEnd   = $lastYear->copy()->endOfMonth();

            $lastYearSales = Cache::remember(
                "monthly_sales_last_year",
                86400,
                function () use ($lastYearStart, $lastYearEnd, $allMarketplaceIds) {
                    return MonthlySales::whereBetween('sale_date', [$lastYearStart->toDateString(), $lastYearEnd->toDateString()])
                        ->whereIn('marketplace_id', $allMarketplaceIds)
                        ->select('marketplace_id', 'currency', 'total_units', 'total_revenue')
                        ->get();
                }
            );

            /* -----------------------------
         *  LAST MONTH (this year)
         * -----------------------------
         */
            $lastMonth      = $yesterday->copy()->subMonthNoOverflow();
            $lastMonthStart = $lastMonth->copy()->startOfMonth();
            $lastMonthEnd   = $lastMonth->copy()->endOfMonth();

            $lastMonthSales = Cache::remember(
                "monthly_sales_last_month",
                86400,
                function () use ($lastMonthStart, $lastMonthEnd, $allMarketplaceIds) {
                    return MonthlySales::whereBetween('sale_date', [$lastMonthStart->toDateString(), $lastMonthEnd->toDateString()])
                        ->whereIn('marketplace_id', $allMarketplaceIds)
                        ->select('marketplace_id', 'currency', 'total_units', 'total_revenue')
                        ->get();
                }
            );
            /* -------------------------------------------------
         *  LAST YEAR SAME MONTH as LAST MONTH (LY-LM)
         * -------------------------------------------------
         */
            $lastYearLastMonthStart = $lastMonth->copy()->subYear()->startOfMonth();
            $lastYearLastMonthEnd   = $lastMonth->copy()->subYear()->endOfMonth();

            $lastYearLastMonthSales = Cache::remember(
                "monthly_sales_last_year_last_month",
                172800,
                function () use ($lastYearLastMonthStart, $lastYearLastMonthEnd, $allMarketplaceIds) {
                    return MonthlySales::whereBetween('sale_date', [
                        $lastYearLastMonthStart->toDateString(),
                        $lastYearLastMonthEnd->toDateString()
                    ])
                        ->whereIn('marketplace_id', $allMarketplaceIds)
                        ->select('marketplace_id', 'currency', 'total_units', 'total_revenue')
                        ->get();
                }
            );
            /* -------------------------
         *   CALCULATIONS
         * -------------------------
         */

            // THIS YEAR (month-to-date)
            $summaryTotals['total_units_ty'] = (int) $currentYearDailySales->sum('total_units');
            $summaryTotals['total_revenue_usd_ty'] = round(
                $currentYearDailySales->sum(function ($row) use ($currencyRates) {
                    return $row->total_revenue * ($currencyRates[$row->currency] ?? 1);
                }),
                2
            );

            // LAST YEAR (same month)
            $summaryTotals['total_units_ly'] = (int) $lastYearSales->sum('total_units');
            $summaryTotals['total_revenue_usd_ly'] = round(
                $lastYearSales->sum(function ($row) use ($currencyRates) {
                    return $row->total_revenue * ($currencyRates[$row->currency] ?? 1);
                }),
                2
            );

            // LAST MONTH (this year)
            $summaryTotals['total_units_last_month'] = (int) $lastMonthSales->sum('total_units');
            $summaryTotals['total_revenue_usd_last_month'] = round(
                $lastMonthSales->sum(function ($row) use ($currencyRates) {
                    return $row->total_revenue * ($currencyRates[$row->currency] ?? 1);
                }),
                2
            );

            // L-Y LAST MONTH
            $summaryTotals['total_units_last_month_ly'] = (int) $lastYearLastMonthSales->sum('total_units');
            $summaryTotals['total_revenue_usd_last_month_ly'] = round(
                $lastYearLastMonthSales->sum(function ($row) use ($currencyRates) {
                    return $row->total_revenue * ($currencyRates[$row->currency] ?? 1);
                }),
                2
            );

            /* -------------------------
         *    RETURN WITH ALL DATES
         * -------------------------
         */
            return [
                'summary' => [

                    // THIS YEAR MTD
                    'total_units_ty'       => $summaryTotals['total_units_ty'],
                    'total_revenue_usd_ty' => $summaryTotals['total_revenue_usd_ty'],
                    'mtd_start'            => $yearStart->toDateString(),

                    // LAST YEAR SAME MONTH
                    'total_units_ly'       => $summaryTotals['total_units_ly'],
                    'total_revenue_usd_ly' => $summaryTotals['total_revenue_usd_ly'],
                    'ly_start'             => $lastYearStart->toDateString(),

                    // % growth
                    'total_units_percentage' =>
                    $summaryTotals['total_units_ly'] > 0
                        ? round(($summaryTotals['total_units_ty'] / $summaryTotals['total_units_ly']) * 100)
                        : null,

                    // LAST MONTH
                    'total_units_last_month'          => $summaryTotals['total_units_last_month'],
                    'total_revenue_usd_last_month'    => $summaryTotals['total_revenue_usd_last_month'],
                    'last_month_start'                => $lastMonthStart->toDateString(),

                    // LAST YEAR LAST MONTH
                    'total_units_last_month_ly'       => $summaryTotals['total_units_last_month_ly'],
                    'total_revenue_usd_last_month_ly' => $summaryTotals['total_revenue_usd_last_month_ly'],
                    'last_month_ly_start'             => $lastYearLastMonthStart->toDateString(),
                ],
            ];
        });
    }

    public function getTodayAdReportSales(string $marketTz): array
    {
        $today    = Carbon::now($marketTz)->toDateString();
        $cacheKey = "sales_report_today";

        return Cache::remember($cacheKey, 300, function () use ($today) {
            $defaultMetrics = (object) [
                'total_sales' => 0,
                'total_cost'  => 0,
            ];

            // Safe DB queries with fallback
            $campaignMetrics = DB::table('temp_amz_ads_campaign_performance_report as c')
                ->leftJoin('currencies as cur', 'cur.country_code', '=', 'c.country')
                ->whereDate('c.c_date', $today)
                ->selectRaw('
                COALESCE(SUM(c.sales7d * COALESCE(cur.conversion_rate_to_usd, 1)), 0) AS total_sales,
                COALESCE(SUM(c.cost * COALESCE(cur.conversion_rate_to_usd, 1)), 0) AS total_cost
            ')
                ->first() ?? $defaultMetrics;

            $campaignSbMetrics = DB::table('temp_amz_ads_campaign_performance_reports_sb as sb')
                ->leftJoin('currencies as cur', 'cur.country_code', '=', 'sb.country')
                ->whereDate('sb.date', $today)
                ->selectRaw('
                COALESCE(SUM(sb.sales * COALESCE(cur.conversion_rate_to_usd, 1)), 0) AS total_sales,
                COALESCE(SUM(sb.cost * COALESCE(cur.conversion_rate_to_usd, 1)), 0) AS total_cost
            ')
                ->first() ?? $defaultMetrics;

            $campaignSdMetrics = DB::table('temp_amz_campaign_sd_performance_report as sd')
                ->leftJoin('currencies as cur', 'cur.country_code', '=', 'sd.country')
                ->whereDate('sd.c_date', $today)
                ->selectRaw('
                COALESCE(SUM(sd.sales * COALESCE(cur.conversion_rate_to_usd, 1)), 0) AS total_sales,
                COALESCE(SUM(sd.cost * COALESCE(cur.conversion_rate_to_usd, 1)), 0) AS total_cost
            ')
                ->first() ?? $defaultMetrics;

            // Normalize structure
            $campaign = [
                'sales' => $campaignMetrics->total_sales ?? 0,
                'spend' => $campaignMetrics->total_cost ?? 0,
            ];
            $campaign_sb = [
                'sales' => $campaignSbMetrics->total_sales ?? 0,
                'spend' => $campaignSbMetrics->total_cost ?? 0,
            ];
            $campaign_sd = [
                'sales' => $campaignSdMetrics->total_sales ?? 0,
                'spend' => $campaignSdMetrics->total_cost ?? 0,
            ];

            // Ensure cumulative always exists
            $cumulative = [
                'sales' => ($campaign['sales'] ?? 0) + ($campaign_sb['sales'] ?? 0) + ($campaign_sd['sales'] ?? 0) ?? 0,
                'spend' => ($campaign['spend'] ?? 0) + ($campaign_sb['spend'] ?? 0) + ($campaign_sd['spend'] ?? 0) ?? 0,
            ];

            return [
                'today'       => $today,
                'campaign'    => $campaign,
                'campaign_sb' => $campaign_sb,
                'campaign_sd' => $campaign_sd,
                'cumulative'  => $cumulative,
            ];
        });
    }
    // Ads Chart
    public function adsPerformanceChart($startDate = null, $endDate = null, $grouping = 'day')
    {
        $periodExpr = function (string $col) use ($grouping) {
            return match ($grouping) {
                'week'  => "YEARWEEK($col, 1)",
                'month' => "DATE_FORMAT($col, '%Y-%m')",
                default => "DATE($col)",
            };
        };

        // SP: amz_ads_campaign_performance_report (c_date, cost, sales7d, country)
        $sp = DB::table('amz_ads_campaign_performance_report as t')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 't.country')
            ->when($startDate, fn($q) => $q->where('t.c_date', '>=', $startDate))
            ->when($endDate,   fn($q) => $q->where('t.c_date', '<=', $endDate))
            ->selectRaw($periodExpr('t.c_date') . " AS period,
                      SUM(t.cost   * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend,
                      SUM(t.sales7d* COALESCE(cur.conversion_rate_to_usd, 1)) AS sales")
            ->groupBy('period');

        // SD: amz_ads_campaign_performance_report_sd (c_date, cost, sales, country)
        $sd = DB::table('amz_ads_campaign_performance_report_sd as t')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 't.country')
            ->when($startDate, fn($q) => $q->where('t.c_date', '>=', $startDate))
            ->when($endDate,   fn($q) => $q->where('t.c_date', '<=', $endDate))
            ->selectRaw($periodExpr('t.c_date') . " AS period,
                      SUM(t.cost * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend,
                      SUM(t.sales* COALESCE(cur.conversion_rate_to_usd, 1)) AS sales")
            ->groupBy('period');

        // SB: amz_ads_campaign_performance_reports_sb (date, cost, sales, country)
        $sb = DB::table('amz_ads_campaign_performance_reports_sb as t')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 't.country')
            ->when($startDate, fn($q) => $q->where('t.date', '>=', $startDate))
            ->when($endDate,   fn($q) => $q->where('t.date', '<=', $endDate))
            ->selectRaw($periodExpr('t.date') . " AS period,
                      SUM(t.cost * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend,
                      SUM(t.sales* COALESCE(cur.conversion_rate_to_usd, 1)) AS sales")
            ->groupBy('period');

        // Combine and re-aggregate to sum SP + SD + SB per period
        $union = $sp->unionAll($sd)->unionAll($sb);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->selectRaw('period, SUM(spend) AS total_spend, SUM(sales) AS total_sales')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($row) use ($grouping) {
                // Pretty labels
                if ($grouping === 'week') {
                    $year = (int)substr($row->period, 0, 4);
                    $week = (int)substr($row->period, 4);
                    $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
                    $endOfWeek   = Carbon::now()->setISODate($year, $week)->endOfWeek();
                    $label = $startOfWeek->format('M') === $endOfWeek->format('M')
                        ? $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('j')
                        : $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j');
                } elseif ($grouping === 'month') {
                    $label = Carbon::parse($row->period . '-01')->format('F');
                } else {
                    $label = Carbon::parse($row->period)->format('M-d');
                }

                return [
                    'period' => $label,
                    'spend'  => round((float) $row->total_spend, 2),
                    'sales'  => round((float) $row->total_sales, 2),
                ];
            });

        return $rows;
    }

    // Top 10 campaigns for SP and SB
    public function getCampaignYesterdaysSalesReport(string $marketTz): array
    {
        $yesterday =  Carbon::now($marketTz)->subDay()->toDateString();
        $cacheKey  = "sales_report_yesterday";

        return Cache::remember($cacheKey, 3600, function () use ($yesterday, $marketTz) {
            $currencyRates   = Currency::pluck('conversion_rate_to_usd', 'country_code');
            $c_date          = Carbon::parse($yesterday, $marketTz)->toDateString();

            // Campaign performance (SP)
            $campaignMetrics = DB::table('amz_ads_campaign_performance_report as c')
                ->join('currencies as cur', 'cur.country_code', '=', 'c.country')
                ->selectRaw('SUM(c.cost * cur.conversion_rate_to_usd) as total_cost_usd,
                            SUM(c.sales7d * cur.conversion_rate_to_usd) as total_sales_usd')
                ->whereDate('c.c_date', $c_date)->first();

            // Sponsored Brand cost
            $campSb = DB::table('amz_ads_campaign_performance_reports_sb as sb')
                ->leftJoin('currencies as cur', 'cur.country_code', '=', 'sb.country')
                ->whereDate('sb.date', $c_date)
                ->selectRaw('
                        SUM(sb.cost * COALESCE(cur.conversion_rate_to_usd, 1)) as total_cost_usd,
                        SUM(sb.sales * COALESCE(cur.conversion_rate_to_usd, 1)) as total_sales_usd
                    ')->first();

            $campSd = DB::table('amz_ads_campaign_performance_report_sd as sd')
                ->leftJoin('currencies as cur', 'cur.country_code', '=', 'sd.country')
                ->whereDate('sd.c_date', $c_date)
                ->selectRaw('
                    SUM(sd.cost * COALESCE(cur.conversion_rate_to_usd, 1)) as total_cost_usd,
                    SUM(sd.sales * COALESCE(cur.conversion_rate_to_usd, 1)) as total_sales_usd
                ')->first();

            // Product metrics
            $productMetrics = DB::table('amz_ads_product_performance_report as p')
                ->leftJoin('currencies as cur', 'cur.country_code', '=', 'p.country')
                ->whereDate('p.c_date', $c_date)
                ->selectRaw('
                SUM(p.cost * COALESCE(cur.conversion_rate_to_usd, 1)) as total_cost_usd,
                SUM(p.sales7d * COALESCE(cur.conversion_rate_to_usd, 1)) as total_sales_usd
            ')->first();

            // Top 10 SP campaigns (2-step)
            $top10SpRaw = AmzAdsCampaignPerformanceReport::with('campaign')->select('campaign_id', 'c_date', 'cost', 'sales7d', 'country')
                ->where('c_date', $c_date)
                ->orderByDesc('cost')
                ->limit(10)
                ->get();

            $top10Sp = $top10SpRaw->map(function ($row) use ($currencyRates) {
                $rate = $currencyRates[$row->country] ?? 1;
                $row->cost_usd   = $row->cost * $rate;
                $row->sales7d_usd = $row->sales7d * $rate;
                return $row;
            });

            // Top 10 SB campaigns (2-step)
            $top10SbRaw = AmzAdsCampaignSBPerformanceReport::with('campaign')->select('campaign_id', 'date', 'cost', 'sales', 'country')
                ->where('date', $c_date)
                ->orderByDesc('cost')
                ->limit(10)
                ->get();

            $top10SdRaw = AmzAdsCampaignPerformanceReportSd::select('campaign_id', 'c_date', 'cost', 'sales', 'country')
                ->where('c_date', $c_date)
                ->orderByDesc('cost')
                ->limit(10)
                ->get();

            $top10Sb = $top10SbRaw->map(function ($row) use ($currencyRates) {
                $rate = $currencyRates[$row->country] ?? 1;
                $row->cost_usd  = $row->cost * $rate;
                $row->sales_usd = $row->sales * $rate;
                return $row;
            });

            $top10Sd = $top10SdRaw->map(function ($row) use ($currencyRates) {
                $rate = $currencyRates[$row->country] ?? 1;
                $row->cost_usd  = $row->cost * $rate;
                $row->sales_usd = $row->sales * $rate;
                return $row;
            });

            return [
                'c_date' => $c_date,
                'totals' => [
                    'sp' => [
                        'cost'  => $campaignMetrics->total_cost_usd ?? 0,
                        'sales' => $campaignMetrics->total_sales_usd ?? 0,
                    ],
                    'sb' => [
                        'cost'  => $campSb->total_cost_usd ?? 0,
                        'sales' => $campSb->total_sales_usd ?? 0,
                    ],
                    'sd' => [
                        'cost'  => $campSd->total_cost_usd ?? 0,
                        'sales' => $campSd->total_sales_usd ?? 0,
                    ],
                    'product' => [
                        'cost'  => $productMetrics->total_cost_usd ?? 0,
                        'sales' => $productMetrics->total_sales_usd ?? 0,
                    ]
                ],
                'top_10' => [
                    'sp' => $top10Sp,
                    'sb' => $top10Sb,
                    'sd' => $top10Sd,
                ]
            ];
        });
    }

    public function getForecastDataWithFCF(string $marketTz, array $marketplaceMap): array
    {
        $monthStart = Carbon::now($marketTz)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::now($marketTz)->addMonth()->startOfMonth()->toDateString();

        // Forecast totals
        $forecastTotals = Cache::remember('forecast_totals_month', 3600, function () use ($monthStart, $monthEnd) {
            return [
                'asin' => DB::table('product_forecast_asins')
                    ->whereDate('forecast_month', $monthStart)
                    ->sum('forecast_units'),

                'sku' => DB::table('product_forecasts')
                    ->whereDate('forecast_month', $monthStart)
                    ->sum('forecast_units'),
            ];
        });

        // MTD Sales
        $mtdUnits = $this->getMonthToDateAndYearComparisons($marketTz, $marketplaceMap);
        $totalUnitsTy = data_get($mtdUnits, 'summary.total_units_ty', 0);

        $now = Carbon::now($marketTz);
        $daysPassed = $now->day;
        $totalDays  = $now->daysInMonth;

        $fcfAsin = $forecastTotals['asin'] > 0
            ? ($totalUnitsTy / ($forecastTotals['asin'] * ($daysPassed / $totalDays))) * 100
            : 0;

        $fcfSku = $forecastTotals['sku'] > 0
            ? ($totalUnitsTy / ($forecastTotals['sku'] * ($daysPassed / $totalDays))) * 100
            : 0;

        return [
            'asin_units' => $forecastTotals['asin'],
            'sku_units'  => $forecastTotals['sku'],
            'fcf_asin'   => round($fcfAsin, 2),
            'fcf_sku'    => round($fcfSku, 2),
        ];
    }

    public function getTodayAdsCampaignReportPaginated(
        string $marketTz,
        int $perPage = 25,
        ?string $type = null,
        ?string $country = null,
        ?string $search = null
    ) {
        $today = Carbon::now($marketTz)->toDateString();

        /**
         * Build product aggregation subqueries per type:
         * - campaign_id + country => group_concat(asins), group_concat(product_names)
         *
         * NOTE:
         * Adjust column names if your tables differ.
         * Assumed columns:
         *   amz_ads_products(_sb/_sd): campaign_id, country, asin
         *   product_categorisations: child_asin, child_short_name
         */

        $spProductsAgg = DB::table('amz_ads_products as p')
            ->leftJoin('product_categorisations as pc', 'pc.child_asin', '=', 'p.asin')
            ->selectRaw("
            p.campaign_id,
            p.country,
            GROUP_CONCAT(DISTINCT p.asin ORDER BY p.asin SEPARATOR ', ') as asins,
            GROUP_CONCAT(DISTINCT pc.child_short_name ORDER BY pc.child_short_name SEPARATOR ', ') as product_names
        ")
            ->groupBy('p.campaign_id', 'p.country');

        $sbProductsAgg = DB::table('amz_ads_products_sb as p')
            ->leftJoin('product_categorisations as pc', 'pc.child_asin', '=', 'p.asin')
            ->selectRaw("
            p.campaign_id,
            p.country,
            GROUP_CONCAT(DISTINCT p.asin ORDER BY p.asin SEPARATOR ', ') as asins,
            GROUP_CONCAT(DISTINCT pc.child_short_name ORDER BY pc.child_short_name SEPARATOR ', ') as product_names
        ")
            ->groupBy('p.campaign_id', 'p.country');

        $sdProductsAgg = DB::table('amz_ads_products_sd as p')
            ->leftJoin('product_categorisations as pc', 'pc.child_asin', '=', 'p.asin')
            ->selectRaw("
            p.campaign_id,
            p.country,
            GROUP_CONCAT(DISTINCT p.asin ORDER BY p.asin SEPARATOR ', ') as asins,
            GROUP_CONCAT(DISTINCT pc.child_short_name ORDER BY pc.child_short_name SEPARATOR ', ') as product_names
        ")
            ->groupBy('p.campaign_id', 'p.country');

        // -------- SP --------
        $sp = DB::table('temp_amz_ads_campaign_performance_report as c')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'c.country')
            ->leftJoinSub($spProductsAgg, 'prod', function ($join) {
                $join->on('prod.campaign_id', '=', 'c.campaign_id')
                    ->on('prod.country', '=', 'c.country');
            })
            ->whereDate('c.c_date', $today)
            ->selectRaw("
            ? as report_date,
            'SP' as type,
            c.country as country,
            c.campaign_id as campaign_id,
            COALESCE(prod.asins, '') as asins,
            COALESCE(prod.product_names, '') as product_names,
            COALESCE((c.sales7d * COALESCE(cur.conversion_rate_to_usd, 1)), 0) as sales_usd,
            COALESCE((c.cost * COALESCE(cur.conversion_rate_to_usd, 1)), 0) as spend_usd
        ", [$today]);

        // -------- SB --------
        $sb = DB::table('temp_amz_ads_campaign_performance_reports_sb as sb')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'sb.country')
            ->leftJoinSub($sbProductsAgg, 'prod', function ($join) {
                $join->on('prod.campaign_id', '=', 'sb.campaign_id')
                    ->on('prod.country', '=', 'sb.country');
            })
            ->whereDate('sb.date', $today)
            ->selectRaw("
            ? as report_date,
            'SB' as type,
            sb.country as country,
            sb.campaign_id as campaign_id,
            COALESCE(prod.asins, '') as asins,
            COALESCE(prod.product_names, '') as product_names,
            COALESCE((sb.sales * COALESCE(cur.conversion_rate_to_usd, 1)), 0) as sales_usd,
            COALESCE((sb.cost * COALESCE(cur.conversion_rate_to_usd, 1)), 0) as spend_usd
        ", [$today]);

        // -------- SD --------
        $sd = DB::table('temp_amz_campaign_sd_performance_report as sd')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'sd.country')
            ->leftJoinSub($sdProductsAgg, 'prod', function ($join) {
                $join->on('prod.campaign_id', '=', 'sd.campaign_id')
                    ->on('prod.country', '=', 'sd.country');
            })
            ->whereDate('sd.c_date', $today)
            ->selectRaw("
            ? as report_date,
            'SD' as type,
            sd.country as country,
            sd.campaign_id as campaign_id,
            COALESCE(prod.asins, '') as asins,
            COALESCE(prod.product_names, '') as product_names,
            COALESCE((sd.sales * COALESCE(cur.conversion_rate_to_usd, 1)), 0) as sales_usd,
            COALESCE((sd.cost * COALESCE(cur.conversion_rate_to_usd, 1)), 0) as spend_usd
        ", [$today]);

        // UNION ALL
        $union = $sp->unionAll($sb)->unionAll($sd);

        // Filters + search
        $query = DB::query()
            ->fromSub($union, 't')
            ->when($type, fn($q) => $q->where('t.type', strtoupper($type)))
            ->when($country, fn($q) => $q->where('t.country', strtoupper($country)))
            ->when($search, function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

                $q->where(function ($qq) use ($like) {
                    $qq->where('t.product_names', 'like', $like)
                        ->orWhere('t.asins', 'like', $like)
                        ->orWhereRaw('CAST(t.campaign_id AS CHAR) like ?', [$like]);
                });
            })
            ->orderByDesc('t.sales_usd')
            ->orderByDesc('t.spend_usd');

        return $query->paginate($perPage);
    }
}
