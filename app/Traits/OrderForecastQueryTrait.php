<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait OrderForecastQueryTrait
{
    public function getAsinMonthlyForecast(
        string $month,
        ?string $search = null,
        ?string $forecastFilter = null,
        bool $paginate = true,
        int $perPage = 15
    ) {
        $monthStart    = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd      = $monthStart->copy()->addMonth();
        $lastYearMonth = $monthStart->copy()->subYear()->startOfMonth();

        $daysPassed = min(now()->day, $monthStart->daysInMonth);
        $totalDays  = $monthStart->daysInMonth;

        // dd($daysPassed, $totalDays);

        /** Subqueries */
        $lastYearSales = DB::table('monthly_ads_product_performances')
            ->select('asin', DB::raw('SUM(sold) as last_year_sold'))
            ->where('month', $lastYearMonth->format('Y-m-d'))
            ->groupBy('asin');

        $currentMonthSales = DB::table('daily_sales')
            ->select('asin', DB::raw('SUM(total_units) as current_month_sold'))
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->groupBy('asin');

        /** Base Query */
        $query = DB::table('product_forecast_asins as fa')
            ->select(
                'fa.product_asin as asin',
                DB::raw('COALESCE(ly.last_year_sold, 0) as last_year_sold'),
                DB::raw('COALESCE(cm.current_month_sold, 0) as current_month_sold'),
                DB::raw('fa.forecast_units'),
                // Calculate FCF % in SQL
                DB::raw("
                    CASE 
                        WHEN fa.forecast_units * ({$daysPassed}/{$totalDays}) > 0 
                        THEN ROUND(
                            (COALESCE(cm.current_month_sold, 0) 
                            / (fa.forecast_units * ({$daysPassed}/{$totalDays}))) * 100,
                            2
                        )
                        ELSE 0
                    END AS fcf_percent
                ")

            )
            ->leftJoinSub(
                $lastYearSales,
                'ly',
                fn($join) => $join->on('ly.asin', '=', 'fa.product_asin')
            )
            ->leftJoinSub(
                $currentMonthSales,
                'cm',
                fn($join) => $join->on('cm.asin', '=', 'fa.product_asin')
            )
            ->where('fa.forecast_month', $monthStart->format('Y-m-01'))
            ->where('fa.forecast_units', '>', 0)
            ->when($search, fn($q) => $q->where('fa.product_asin', 'like', "%{$search}%"))
            ->orderBy('fa.product_asin');

        /** Filter by FCF % */
        if ($forecastFilter === 'gt100') {
            $query->having('fcf_percent', '>', 100);
        }

        if ($forecastFilter === '50to100') {
            $query->havingBetween('fcf_percent', [50, 100]);
        }

        if ($forecastFilter === 'lt50') {
            $query->having('fcf_percent', '<', 50);
        }

        /** Get Records */
        $records = $paginate ? $query->paginate($perPage) : $query->get();

        return [
            'records'    => $records,
            'monthStart' => $monthStart,
        ];
    }
}
