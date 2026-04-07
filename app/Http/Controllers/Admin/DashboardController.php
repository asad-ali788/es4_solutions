<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailySales;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Currency;
use App\Models\HourlyProductSales;
use App\Services\Dashboard\DashboardService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        try {
            // Get current and previous day for comparison
            $marketTz  = config('timezone.market');
            $today     = Carbon::now($marketTz)->toDateString();

            // Marketplace mapping for country detection
            $marketplaceMap = [
                'USA' => ['Amazon.com'],
                'CA'  => ['Amazon.ca'],
                'MX'  => ['Amazon.com.mx'],
            ];

            // Hourly sales stats for today and yesterday
            $hourlySales = Cache::remember('hourly_sales_today', 300, function () {
                return $this->getHourlySalesStats();
            });
            $hourlyToday      = $hourlySales['revenue'];
            $hourlyUnitsToday = $hourlySales['units'];

            // Last update
            $lastHourlyUpdate = HourlyProductSales::whereDate('sale_hour', $today)
                ->latest('updated_at')
                ->value('updated_at') ?? HourlyProductSales::whereDate('sale_hour', $today)
                ->latest('created_at')
                ->value('created_at');

            $now   = Carbon::now($marketTz);
            $range = request('range', 'today'); // today | yesterday
            $dayMarket = $range === 'yesterday'
                ? $now->copy()->subDay()
                : $now->copy();

            $startMarket = $dayMarket->copy()->startOfDay();
            $endMarket   = $dayMarket->copy()->endOfDay();

            $dailySnapshot = HourlyProductSales::query()
                ->select([
                    DB::raw('sale_hour as snapshot_time'),
                    DB::raw('SUM(total_units) as total_units'),
                ])
                ->whereBetween('sale_hour', [$startMarket, $endMarket])
                ->groupBy('sale_hour')
                ->orderBy('sale_hour', 'desc')
                ->get();

            // Get the Yesterdays sales units and the month to date sales and units
            $yesterdaySaleSummary = $this->getYesterdaysSummary($marketTz);
            // attach percentage changes
            $hourlyToday['change']      = $this->calculateChange($hourlyToday['value'], $yesterdaySaleSummary['summary']['total_revenue_usd']);
            $hourlyUnitsToday['change'] = $this->calculateChange($hourlyUnitsToday['value'], $yesterdaySaleSummary['summary']['total_units']);

            // ```````````````Forecast Data `````````//
            $forecast = $this->dashboardService->getForecastDataWithFCF($marketTz, $marketplaceMap);

            return view('pages.admin.dashboard.dashboard', [
                'error'                => false,
                'user'                 => $user,
                'yesterdaySaleSummary' => $yesterdaySaleSummary,
                'marketplaceMap'       => $marketplaceMap,
                'dailySnapshot'        => $dailySnapshot,
                'hourly'               => [
                    'units'          => $hourlyUnitsToday['value'],
                    'units_change'   => $hourlyUnitsToday['change'],
                    'units_meta'     => $this->formatMeta($hourlyUnitsToday['change']),
                    'revenue'        => $hourlyToday['value'],
                    'revenue_change' => $hourlyToday['change'],
                    'revenue_meta'   => $this->formatMeta($hourlyToday['change']),
                    'last_updated'   => $lastHourlyUpdate,
                ],
                'dates'      => [
                    'today'     => Carbon::now($marketTz),
                    'yesterday' => Carbon::now($marketTz)->subDay(),
                ],
                'forecast' => $forecast,
            ]);
        } catch (\Throwable $e) {
            Log::error('Dashboard Load Failed: ' . $e->getMessage());

            return view('pages.admin.dashboard.dashboard', [
                'error' => true,
                'user'  => $user,
            ])->with('error', 'Something went wrong while loading the dashboard.');
        }
    }
    private function getYesterdaysSummary(string $marketTz): array
    {
        $yesterday = Carbon::now($marketTz)->subDay();
        $cacheKey = 'sales_summary_yesterday_only';
        return Cache::tags('dashboard')->remember($cacheKey, 3600, function () use ($yesterday) {
            $currencyRates = Currency::pluck('conversion_rate_to_usd', 'currency_code');

            $countryData   = [];
            $summaryTotals = [
                'total_units'       => 0,
                'total_revenue_usd' => 0,
            ];
            // Yesterday’s actual sales
            $sales = DailySales::where('sale_date', $yesterday->toDateString())
                ->select('marketplace_id', 'currency', 'total_units', 'total_revenue', 'sale_date')
                ->get();

            foreach (config('marketplaces.marketplace_names', []) as $country => $marketplaceIds) {
                $currentSales = $sales->whereIn('marketplace_id', $marketplaceIds);
                if ($currentSales->isEmpty()) {
                    continue;
                }

                $totalUnits   = $currentSales->sum('total_units');
                $totalRevenue = $currentSales->sum('total_revenue');
                // If "record exists but it's basically empty", skip it
                if ($totalUnits <= 0 && $totalRevenue <= 0) {
                    continue;
                }
                $currency     = $currentSales->first()?->currency ?? 'USD';
                $rate         = $currencyRates[$currency] ?? 1;
                $revenueUsd   = round($totalRevenue * $rate, 2);
                //also skip if conversion made it effectively zero
                if ($totalUnits <= 0 && $revenueUsd <= 0) {
                    continue;
                }
                $countryData[] = [
                    'country'       => $country,
                    'currency'      => $currency,
                    'total_units'   => $totalUnits,
                    'total_revenue' => $totalRevenue,
                    'revenue_usd'   => $revenueUsd,
                    'sale_date'     => $currentSales->first()?->sale_date,
                ];

                $summaryTotals['total_units']       += $totalUnits;
                $summaryTotals['total_revenue_usd'] += $revenueUsd;
            }

            return [
                'summary' => [
                    'total_units'       => $summaryTotals['total_units'],
                    'total_revenue_usd' => $summaryTotals['total_revenue_usd'],
                    'sale_date'         => $sales->first()?->sale_date ?? 'N/A',
                ],
                'by_country' => $countryData,
            ];
        });
    }

    private function getHourlySalesStats(): array
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');
        $now      = Carbon::now($marketTz);

        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $row = HourlyProductSales::query()
            ->join(
                'currencies as fx',
                'fx.currency_code',
                '=',
                'hourly_product_sales.currency'
            )
            ->whereBetween('hourly_product_sales.sale_hour', [$start, $end])
            ->selectRaw('
            COALESCE(SUM(hourly_product_sales.total_units), 0) as units,
            COALESCE(
                SUM(hourly_product_sales.item_price * fx.conversion_rate_to_usd),
                0
            ) as revenue_usd
        ')->first();

        return [
            'units' => [
                'value' => (int) ($row->units ?? 0),
            ],
            'revenue' => [
                'value' => round((float) ($row->revenue_usd ?? 0), 2),
            ],
        ];
    }

    private function calculateChange($current, $previous): int
    {
        return $previous == 0 ? 0 : round((($current - $previous) / $previous) * 100);
    }

    private function formatMeta(int $change): array
    {
        $isUp = $change >= 0;

        return [
            'is_up'       => $isUp,
            'icon'        => $isUp ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down',
            'badge_class' => $isUp ? 'badge-soft-success text-success' : 'badge-soft-danger text-danger',
            'symbol'      => $isUp ? '+' : '-',
        ];
    }

    /**
     * Clear the cache in the dashboard to get latest updates.
     */
    public function clearCache()
    {
        $keys = [
            'currency_rates_usd',
            'sales_report_today',
            'sales_report_yesterday',
            'ads_charts_daily',
            'ads_charts_weekly',
            'ads_charts_monthly',
            'top_selling_products',
            'daily_sales',
            'hourly_sales_today',
            'sales_summary_yesterday_and_lastYear',
            'daily_sales_ytm',
            'sales_summary_month_to_date_and_year_comparisons',
            'monthly_sales_last_year',
            'monthly_sales_last_month',
            'monthly_sales_last_year_last_month',
            'sales_summary_yesterday_only',
            'forecast_totals_month',
        ];
        try {
            foreach ($keys as $k) {
                Cache::forget($k);
            }
            Cache::tags(['dashboard', 'weather'])->flush();
            return back()->with('success', 'Dashboard cache refreshed!');
        } catch (\Throwable $e) {
            Log::warning('Dashboard cache clear failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to refresh Dashboard.');
        }
    }

    public function monthToDateDailyView(Request $request)
    {
        try {
            $marketTz = config('timezone.market');

            $selectedMonth = $request->input('month', now($marketTz)->format('Y-m'));
            $perPage = (int) $request->input('per_page', 25);

            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth, $marketTz)
                ->startOfMonth();

            $monthEnd = $monthStart->isSameMonth(Carbon::now($marketTz))
                ? Carbon::now($marketTz)
                : $monthStart->copy()->endOfMonth();

            $dailyTotals = $this->getDailyTotals(
                $monthStart->toDateString(),
                $monthEnd->toDateString()
            );

            $data = $this->getMonthToDateDailySummary(
                $monthStart,
                $monthEnd
            );

            return view('pages.admin.dashboard.mtd-daily-summary', [
                'summary'        => $data['summary'],
                'byCountry'      => $data['by_country'],
                'dailyTotals'    => $dailyTotals,
                'totalUnitsMtd'  => $data['total_units_mtd'],
                'selectedMonth'  => $selectedMonth,
            ]);
        } catch (\Throwable $e) {

            Log::error('MTD Daily View failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load Month-to-Date Daily Sales.');
        }
    }

    private function getMonthToDateDailySummary(
        Carbon $monthStart,
        Carbon $monthEnd
    ): array {

        $marketTz = config('timezone.market', 'Asia/Kolkata');

        $today = Carbon::now($marketTz);

        $effectiveEnd = $monthStart->isSameMonth($today)
            ? $today
            : $monthEnd;

        $cacheKey = 'sales_summary_mtd_daily_' . $monthStart->format('Y-m');

        return Cache::tags('dashboard')->remember($cacheKey, 3600, function () use (
            $monthStart,
            $effectiveEnd
        ) {

            $currencyRates = Currency::pluck('conversion_rate_to_usd', 'currency_code');

            // 🔹 Initialize
            $byCountry        = [];
            $totalUnitsByDate = [];

            // 🔹 Pull all sales once
            $sales = DailySales::whereBetween('sale_date', [
                $monthStart->toDateString(),
                $effectiveEnd->toDateString()
            ])
                ->select(
                    'marketplace_id',
                    'currency',
                    'total_units',
                    'total_revenue',
                    'sale_date'
                )
                ->get();

            foreach (config('marketplaces.marketplace_names', []) as $country => $marketplaceIds) {

                $countrySales = $sales->whereIn('marketplace_id', (array) $marketplaceIds);

                if ($countrySales->isEmpty()) {
                    continue;
                }

                // 🔹 Group by date
                $dailyGrouped = $countrySales->groupBy('sale_date');

                foreach ($dailyGrouped as $saleDate => $rows) {

                    $currency = $rows->first()?->currency ?? 'USD';
                    $rate     = $currencyRates[$currency] ?? 1;

                    $units   = (int) $rows->sum('total_units');
                    $revenue = (float) $rows->sum('total_revenue');

                    $byCountry[$country][] = [
                        'sale_date'     => $saleDate,
                        'total_units'   => $units,
                        'total_revenue' => $revenue,
                        'revenue_usd'   => round($revenue * $rate, 2),
                    ];

                    // 🔹 Track total units per day
                    $totalUnitsByDate[$saleDate] =
                        ($totalUnitsByDate[$saleDate] ?? 0) + $units;
                }
            }

            return [
                'summary' => [
                    'start_date' => $monthStart->toDateString(),
                    'end_date'   => $effectiveEnd->toDateString(),
                ],
                'by_country'      => $byCountry,
                'total_units_mtd' => $totalUnitsByDate,
            ];
        });
    }

    public function getDailyTotals(string $startDate, string $endDate)
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth()->toDateString();
        $endDate   = $endDate   ?: Carbon::now()->toDateString();

        $cacheKey = "ads_daily_totals_{$startDate}_{$endDate}";

        return Cache::tags(['dashboard', 'ads_overview'])->remember(
            $cacheKey,
            3600,
            function () use ($startDate, $endDate) {

                /**
                 * Step 1: All report dates
                 */
                $dates = DB::table('campaign_recommendations')
                    ->select('report_week')
                    ->distinct()
                    ->whereBetween('report_week', [$startDate, $endDate]);

                /**
                 * Step 2: SP Auto
                 */
                $spAuto = DB::table('campaign_recommendations as cr')
                    ->join('amz_campaigns as ac', 'ac.campaign_id', '=', 'cr.campaign_id')
                    ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
                    ->select(
                        'cr.report_week',
                        DB::raw('SUM(cr.total_spend * COALESCE(cur.conversion_rate_to_usd,1)) AS spend_usd'),
                        DB::raw('SUM(cr.total_sales * COALESCE(cur.conversion_rate_to_usd,1)) AS sales_usd'),
                        DB::raw('SUM(cr.purchases7d) AS units')
                    )
                    ->where('cr.campaign_types', 'SP')
                    ->where('ac.targeting_type', 'AUTO')
                    ->whereIn('cr.country', ['US', 'CA', 'MX'])
                    ->whereBetween('cr.report_week', [$startDate, $endDate])
                    ->groupBy('cr.report_week');

                /**
                 * Step 3: SP Manual
                 */
                $spManual = DB::table('campaign_recommendations as cr')
                    ->join('amz_campaigns as ac', 'ac.campaign_id', '=', 'cr.campaign_id')
                    ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
                    ->select(
                        'cr.report_week',
                        DB::raw('SUM(cr.total_spend * COALESCE(cur.conversion_rate_to_usd,1)) AS spend_usd'),
                        DB::raw('SUM(cr.total_sales * COALESCE(cur.conversion_rate_to_usd,1)) AS sales_usd'),
                        DB::raw('SUM(cr.purchases7d) AS units')
                    )
                    ->where('cr.campaign_types', 'SP')
                    ->where('ac.targeting_type', 'MANUAL')
                    ->whereIn('cr.country', ['US', 'CA', 'MX'])
                    ->whereBetween('cr.report_week', [$startDate, $endDate])
                    ->groupBy('cr.report_week');

                /**
                 * Step 4: SB
                 */
                $sb = DB::table('campaign_recommendations as cr')
                    ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
                    ->select(
                        'cr.report_week',
                        DB::raw('SUM(cr.total_spend * COALESCE(cur.conversion_rate_to_usd,1)) AS spend_usd'),
                        DB::raw('SUM(cr.total_sales * COALESCE(cur.conversion_rate_to_usd,1)) AS sales_usd'),
                        DB::raw('SUM(cr.purchases7d) AS units')
                    )
                    ->where('cr.campaign_types', 'SB')
                    ->whereIn('cr.country', ['US', 'CA', 'MX'])
                    ->whereBetween('cr.report_week', [$startDate, $endDate])
                    ->groupBy('cr.report_week');

                /**
                 * Step 5: SD
                 */
                $sd = DB::table('campaign_recommendations as cr')
                    ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
                    ->select(
                        'cr.report_week',
                        DB::raw('SUM(cr.total_spend * COALESCE(cur.conversion_rate_to_usd,1)) AS spend_usd'),
                        DB::raw('SUM(cr.total_sales * COALESCE(cur.conversion_rate_to_usd,1)) AS sales_usd'),
                        DB::raw('SUM(cr.purchases7d) AS units')
                    )
                    ->where('cr.campaign_types', 'SD')
                    ->whereIn('cr.country', ['US', 'CA', 'MX'])
                    ->whereBetween('cr.report_week', [$startDate, $endDate])
                    ->groupBy('cr.report_week');

                /**
                 * Step 6: Final join
                 */
                return DB::query()
                    ->fromSub($dates, 'dates')
                    ->leftJoinSub($spAuto, 'sp', 'sp.report_week', '=', 'dates.report_week')
                    ->leftJoinSub($spManual, 'spm', 'spm.report_week', '=', 'dates.report_week')
                    ->leftJoinSub($sb, 'sb', 'sb.report_week', '=', 'dates.report_week')
                    ->leftJoinSub($sd, 'sd', 'sd.report_week', '=', 'dates.report_week')
                    ->select(
                        'dates.report_week',
                        DB::raw('
                        COALESCE(sp.spend_usd,0)
                      + COALESCE(spm.spend_usd,0)
                      + COALESCE(sb.spend_usd,0)
                      + COALESCE(sd.spend_usd,0) AS total_spend
                    '),
                        DB::raw('
                        COALESCE(sp.sales_usd,0)
                      + COALESCE(spm.sales_usd,0)
                      + COALESCE(sb.sales_usd,0)
                      + COALESCE(sd.sales_usd,0) AS total_sales
                    '),
                        DB::raw('
                        COALESCE(sp.units,0)
                      + COALESCE(spm.units,0)
                      + COALESCE(sb.units,0)
                      + COALESCE(sd.units,0) AS total_units
                    ')
                    )
                    ->orderBy('dates.report_week')
                    ->get();
            }
        );
    }

    public function flushMtdDailyCache()
    {
        try {
            Cache::tags(['dashboard'])->flush();
            Cache::tags(['ads_overview'])->flush();

            return back()->with('success', 'Mtd Daily cache cleared.');
        } catch (\Throwable $e) {
            Log::warning('Dashboard cache flush failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to clear Dashboard cache.');
        }
    }

    public function detailedTodaysSalesSummery(Request $request)
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'type'     => ['nullable', 'string', 'in:SP,SB,SD,sp,sb,sd'],
            'country'  => ['nullable', 'string', 'size:2'],
            'search'   => ['nullable', 'string', 'max:200'],
            'tz'       => ['nullable', 'string', 'max:64'],
        ]);

        $marketTz  = config('timezone.market');
        $rows = $this->dashboardService->getTodayAdsCampaignReportPaginated(
            marketTz: $marketTz,
            perPage: (int) ($validated['per_page'] ?? 25),
            type: $validated['type'] ?? null,
            country: $validated['country'] ?? null,
            search: $validated['search'] ?? null
        );

        // If you want JSON for Livewire/SPA usage:
        if ($request->wantsJson()) {
            return response()->json($rows);
        }
        // Otherwise return a blade view
        return view('pages.admin.dashboard.todays-sales-summery', [
            'rows'     => $rows,
            'marketTz'     => $marketTz,
        ]);
    }


    public function snapshotTodaysSalesSummery(Request $request)
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');

        // Market day -> UTC bounds (DB stored in UTC)
        $todayStartUtc = Carbon::now($marketTz)->startOfDay()->utc()->toDateTimeString();
        $todayEndUtc   = Carbon::now($marketTz)->endOfDay()->utc()->toDateTimeString();

        $type = $request->filled('type')
            ? strtoupper($request->string('type')->toString())
            : null;

        // 2-hour bucket start (UTC)
        $bucketStartExpr = "FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(s.snapshot_time) / 7200) * 7200)";

        $base = DB::table('amz_ads_campaign_performance_snapshots as s')
            ->whereBetween('s.snapshot_time', [$todayStartUtc, $todayEndUtc]);

        if ($type) {
            $base->where('s.campaign_types', $type);
        }

        $grouped = $base
            ->selectRaw("
            DATE(s.snapshot_time) as report_date,
            s.campaign_types as type,
            s.country,
            $bucketStartExpr as bucket_start,
            SUM(s.total_sales) as sales_usd,
            SUM(s.total_spend) as spend_usd,
            CASE
                WHEN SUM(s.total_sales) > 0
                THEN ROUND((SUM(s.total_spend) / SUM(s.total_sales)) * 100, 2)
                ELSE 0
            END as acos
        ")
            ->groupByRaw("
            DATE(s.snapshot_time),
            s.campaign_types,
            s.country,
            $bucketStartExpr
        ");

        $rows = DB::query()
            ->fromSub($grouped, 'g')
            ->selectRaw("
            g.report_date,
            g.type,
            g.country,
            g.bucket_start,
            g.sales_usd,
            g.spend_usd,
            g.acos
        ")
            ->orderByDesc('g.bucket_start')
            ->orderBy('g.type')
            ->orderBy('g.country')
            ->get();

        $rows->transform(function ($row) use ($marketTz) {
            // bucket_start is UTC from DB
            $startPst = \Carbon\Carbon::parse($row->bucket_start, 'UTC')
                ->setTimezone($marketTz);

            // 2-hour bucket end (adjust if needed)
            $endPst = $startPst->copy()->addHours(2)->subMinute();

            // PST date
            $row->report_date_pst = $startPst->format('d M Y');

            // PST time bucket
            $row->time_bucket_pst =
                $startPst->format('h:i A') . ' - ' . $endPst->format('h:i A');

            return $row;
        });


        return view('pages.admin.dashboard.snapshot-todays-sales-summery', compact('rows', 'marketTz'));
    }
}
