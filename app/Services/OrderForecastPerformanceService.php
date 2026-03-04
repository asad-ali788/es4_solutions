<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderForecastPerformanceService
{
    public function getData(Request $request): array
    {
        $marketTz = config('timezone.market');

        $month       = $request->query('month', now($marketTz)->format('Y-m'));
        $search      = trim($request->query('search', ''));
        $perPage     = (int) $request->query('per_page', 25);
        $fcf_filter  = $request->query('fcf_filter');
        $acos_filter = $request->query('acos_filter');
        $page        = $request->query('page'); // include for cache key

        $monthStart = Carbon::createFromFormat('Y-m', $month, $marketTz)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();
        $monthLabel = $monthStart->format('F Y');

        $now        = Carbon::now($marketTz);
        $daysPassed = min($now->day, $monthStart->daysInMonth);
        $totalDays  = $monthStart->daysInMonth;

        $cacheKey = "forecast_perf:{$month}:{$search}:{$perPage}:{$fcf_filter}:{$acos_filter}:{$page}";

        $records = Cache::tags(['forecast_perf'])->remember(
            $cacheKey,
            now()->addMinutes(30),
            fn() => $this->queryBaseData(
                $monthStart,
                $monthEnd,
                $search,
                $perPage,
                $daysPassed,
                $totalDays,
                $fcf_filter,
                $acos_filter
            )
        );

        // Attach agent names
        $records = $this->attachAgents($records);

        // Compute FC SR
        $records->getCollection()->transform(function ($row) use ($monthStart) {
            $stock = ($row->amazon_stock ?? 0)
                + ($row->warehouse_stock ?? 0)
                + ($row->route_stock ?? 0);

            $remaining    = $stock;
            $coverageDays = 0;

            $forecasts = DB::table('product_forecast_asins')
                ->select('forecast_month', 'forecast_units')
                ->where('product_asin', $row->product_asin)
                ->where('forecast_month', '>=', $monthStart->format('Y-m-01'))
                ->orderBy('forecast_month')
                ->limit(12)
                ->get();

            foreach ($forecasts as $fc) {
                if ($remaining <= 0) {
                    break;
                }

                $daysInMonth     = Carbon::parse($fc->forecast_month)->daysInMonth;
                $monthlyForecast = (float) ($fc->forecast_units ?? 0);

                // Safety: if forecast is 0, skip to avoid division by zero
                if ($monthlyForecast <= 0) {
                    continue;
                }

                if ($remaining >= $monthlyForecast) {
                    $remaining    -= $monthlyForecast;
                    $coverageDays += $daysInMonth;
                } else {
                    $coverageDays += ($remaining / $monthlyForecast) * $daysInMonth;
                    break;
                }
            }

            $row->afn3pl_fc_sr = round($coverageDays, 1);

            return $row;
        });

        return [
            'records'    => $records,
            'monthLabel' => $monthLabel,
            'month'      => $month,
        ];
    }

    public function queryBaseData(
        Carbon $monthStart,
        Carbon $monthEnd,
        string $search,
        int $perPage,
        int $daysPassed,
        int $totalDays,
        ?string $forecastFilter = null,
        ?string $acosFilter = null
    ) {
        // DAILY SALES
        $dailySalesSub = DB::table('daily_sales')
            ->select(
                'asin',
                DB::raw('SUM(total_units) as month_sold'),
                DB::raw("
                    SUM(
                        CASE
                            WHEN sale_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
                            THEN total_units
                            ELSE 0
                        END
                    ) as last_7_days_sold
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN sale_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND CURDATE()
                            THEN total_units
                            ELSE 0
                        END
                    ) as last_14_days_sold
                ")
            )
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->groupBy('asin');

        // ADS
        // $adsSub = DB::table('monthly_ads_product_performances')
        //     ->select(
        //         'asin',
        //         DB::raw('SUM(ad_spend) as ad_spend'),
        //         DB::raw('SUM(ad_sales) as ad_sales'),
        //         DB::raw('SUM(ad_units) as ad_units'),
        //         DB::raw('SUM(acos) as acos')
        //     )
        //     ->where('month', $monthStart->format('Y-m-01'))
        //     ->groupBy('asin');

        $adsSub = DB::table('daily_ads_product_performances')
            ->select(
                'asin',
                DB::raw('SUM(ad_spend) as ad_spend'),
                DB::raw('SUM(ad_sales) as ad_sales'),
                DB::raw('SUM(ad_units) as ad_units'),
                DB::raw("
                    CASE
                        WHEN SUM(ad_sales) > 0
                        THEN ROUND((SUM(ad_spend) / SUM(ad_sales)) * 100, 2)
                        ELSE 0
                    END as acos
                ")
            )
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->groupBy('asin');

        // SB ADS
        $sbAdsSub = DB::table('amz_ads_products_sb as p')
            ->join('amz_ads_campaign_performance_reports_sb as cr', 'p.campaign_id', '=', 'cr.campaign_id')
            ->select(
                'p.asin',
                DB::raw('SUM(cr.sales) as sb_ad_sales'),
                DB::raw('SUM(cr.purchases) as sb_ad_units'),
                DB::raw('SUM(cr.cost) as sb_ad_spend'),
                DB::raw("
                    CASE
                        WHEN SUM(cr.sales) > 0
                        THEN ROUND((SUM(cr.cost) / SUM(cr.sales)) * 100, 2)
                        ELSE 0
                    END as sb_acos
                ")
            )
            ->whereBetween('cr.date', [$monthStart, $monthEnd])
            ->groupBy('p.asin');

        /**
         * ✅ FIX: Don’t restrict finalized forecast to created_at within the selected month.
         * Use the latest finalized forecast up to monthEnd (or overall latest).
         */
        $latestFinalizedForecastId = DB::table('order_forecasts')
            ->where('status', 'finalized')
            ->where('created_at', '<=', $monthEnd)
            ->orderByDesc('created_at')
            ->value('id');

        // Optional: if still null, fallback to latest finalized overall (in case created_at is weird)
        if (!$latestFinalizedForecastId) {
            $latestFinalizedForecastId = DB::table('order_forecasts')
                ->where('status', 'finalized')
                ->orderByDesc('created_at')
                ->value('id');
        }

        return DB::table('product_forecast_asins as fa')
            ->leftJoinSub($dailySalesSub, 'ds', 'ds.asin', '=', 'fa.product_asin')
            ->leftJoinSub($adsSub, 'ad', 'ad.asin', '=', 'fa.product_asin')
            ->leftJoinSub($sbAdsSub, 'sb', 'sb.asin', '=', 'fa.product_asin')

            // Snapshot join: only apply order_forecast_id filter when we actually have an ID
            ->leftJoin('order_forecast_snapshot_asins as s', function ($join) use ($latestFinalizedForecastId) {
                $join->on('s.product_asin', '=', 'fa.product_asin');

                if ($latestFinalizedForecastId) {
                    $join->where('s.order_forecast_id', $latestFinalizedForecastId);
                }
            })

            ->leftJoin('product_categorisations as pc', function ($join) {
                $join->on('pc.child_asin', '=', 'fa.product_asin')
                    ->whereNull('pc.deleted_at');
            })

            ->select(
                'fa.product_asin',
                'fa.forecast_units',
                DB::raw('pc.child_short_name as product_name'),

                DB::raw('COALESCE(ds.month_sold,0) as month_sold'),
                DB::raw('COALESCE(ds.last_7_days_sold,0) as last_7_days_sold'),
                DB::raw('COALESCE(ds.last_14_days_sold,0) as last_14_days_sold'),

                DB::raw("ROUND(fa.forecast_units / {$totalDays},2) as daily_forecast"),
                DB::raw("ROUND(COALESCE(ds.month_sold,0) / NULLIF({$daysPassed},0),2) as daily_rate_of_sale"),

                DB::raw("ROUND((COALESCE(ds.month_sold,0)/NULLIF({$daysPassed},0)) * {$totalDays},0) as full_month_projection"),
                DB::raw("ROUND(((COALESCE(ds.month_sold,0)/NULLIF({$daysPassed},0)) * {$totalDays}) - fa.forecast_units,0) as full_month_delta"),

                DB::raw("ROUND((COALESCE(ds.month_sold,0) / NULLIF(fa.forecast_units * ({$daysPassed}/{$totalDays}),0)) * 100,2) as fcf_full_month"),
                DB::raw("ROUND((COALESCE(ds.last_7_days_sold,0) / NULLIF((fa.forecast_units / {$totalDays})*7,0)) * 100,2) as fcf_7_days"),

                DB::raw("
                    ROUND(
                        (COALESCE(s.amazon_stock,0)
                        + COALESCE(s.warehouse_stock,0)
                        + COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(s.routes,'$.in_transit')) AS UNSIGNED),0))
                        / NULLIF(COALESCE(ds.month_sold,0)/{$daysPassed},0),
                    2) as afn3pl
                "),

                's.product_title',
                's.amazon_stock',
                's.warehouse_stock',
                DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(s.routes,'$.in_transit')) AS UNSIGNED) as route_stock"),

                DB::raw('COALESCE(sb.sb_ad_spend,0) + COALESCE(ad.ad_spend,0) as ad_spend'),
                DB::raw('COALESCE(sb.sb_ad_sales,0) + COALESCE(ad.ad_sales,0) as ad_sales'),
                DB::raw('COALESCE(sb.sb_ad_units,0) + COALESCE(ad.ad_units,0) as total_ads_units'),
                DB::raw("
                    CASE
                        WHEN (COALESCE(ad.ad_sales,0) + COALESCE(sb.sb_ad_sales,0)) > 0
                        THEN ROUND(
                            (COALESCE(ad.ad_spend,0) + COALESCE(sb.sb_ad_spend,0))
                            / (COALESCE(ad.ad_sales,0) + COALESCE(sb.sb_ad_sales,0)) * 100,
                            2
                        )
                        ELSE 0
                    END as acos
                ")
            )

            ->where('fa.forecast_month', $monthStart->format('Y-m-01'))
            ->where('fa.forecast_units', '>', 0)

            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('fa.product_asin', 'LIKE', "%{$search}%")
                        ->orWhere('s.product_title', 'LIKE', "%{$search}%")
                        ->orWhere('pc.child_short_name', 'LIKE', "%{$search}%");
                });
            })

            ->when($forecastFilter, function ($q) use ($forecastFilter) {
                if ($forecastFilter === 'gt100') $q->having('fcf_full_month', '>', 100);
                if ($forecastFilter === '50to60') $q->havingBetween('fcf_full_month', [50, 60]);
                if ($forecastFilter === '60to70') $q->havingBetween('fcf_full_month', [60, 70]);
                if ($forecastFilter === '70to80') $q->havingBetween('fcf_full_month', [70, 80]);
                if ($forecastFilter === '80to90') $q->havingBetween('fcf_full_month', [80, 90]);
                if ($forecastFilter === 'lt50') $q->having('fcf_full_month', '<', 50);
            })

            ->when($acosFilter, function ($q) use ($acosFilter) {
                if ($acosFilter === 'lt30') $q->having('acos', '<', 30);
                if ($acosFilter === '30to40') $q->havingBetween('acos', [30, 40]);
                if ($acosFilter === 'gt40') $q->having('acos', '>', 40);
            })

            ->orderBy('fa.product_asin')
            ->paginate($perPage);
    }

    public function attachAgents($records)
    {
        $agentMap = $this->getAgentMap();

        $records->getCollection()->transform(function ($row) use ($agentMap) {
            $row->agent_name = $agentMap[$row->product_asin] ?? null;
            return $row;
        });

        return $records;
    }

    public function getAgentMap(): array
    {
        return Cache::tags(['forecast_perf'])->remember(
            'forecast_perf:agent_map',
            now()->addHours(6),
            function () {
                return DB::table('user_assigned_asins as ua')
                    ->join('users as u', 'u.id', '=', 'ua.user_id')
                    ->whereNull('ua.deleted_at')
                    ->pluck('u.name', 'ua.asin')
                    ->toArray();
            }
        );
    }
}
