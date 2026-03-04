<?php

namespace App\Jobs\Forecast;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MonthlyAdsProductPerformance;
use Carbon\Carbon;
use Exception;

class ProcessMonthlySkuAsinMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $month) {}

    public function handle(): void
    {
        $startDate = Carbon::parse("{$this->month}-01")->startOfMonth()->toDateString();
        $endDate   = Carbon::parse("{$this->month}-01")->addMonth()->toDateString();
        $now       = now();

        Log::info("ProcessMonthlySkuAsinMetrics started for month {$this->month}");

        try {

            $rows = DB::select("
                SELECT 
                    p.sku,
                    pa.asin1 AS asin,

                    COALESCE(s.sold, 0)    AS sold,
                    COALESCE(s.revenue, 0) AS revenue,

                    COALESCE(a.ad_spend, 0) AS ad_spend,
                    COALESCE(a.ad_sales, 0) AS ad_sales,
                    COALESCE(a.ad_units, 0) AS ad_units,
                    COALESCE(a.acos, 0)     AS acos,

                    CASE 
                        WHEN COALESCE(s.revenue, 0) > 0
                        THEN ROUND((a.ad_spend / s.revenue) * 100, 2)
                        ELSE 0
                    END AS tacos

                FROM product_asins pa
                JOIN products p 
                    ON p.id = pa.product_id

                -- sales
                LEFT JOIN (
                    SELECT 
                        sku,
                        asin,
                        SUM(total_units)   AS sold,
                        SUM(total_revenue) AS revenue
                    FROM monthly_sales
                    WHERE sale_date >= ?
                      AND sale_date < ?
                    GROUP BY sku, asin
                ) s
                    ON s.asin = pa.asin1
                   AND s.sku  = p.sku

                -- ads (SP + SB + SD)
                LEFT JOIN (
                    SELECT
                        sku,
                        asin,
                        SUM(cost)        AS ad_spend,
                        SUM(sales)       AS ad_sales,
                        SUM(purchases)   AS ad_units,
                        CASE 
                            WHEN SUM(sales) > 0
                            THEN ROUND((SUM(cost) / SUM(sales)) * 100, 2)
                            ELSE 0
                        END AS acos
                    FROM (
                        -- SP
                        SELECT
                            c.sku,
                            c.asin,
                            SUM(r.cost)        AS cost,
                            SUM(r.sales1d)     AS sales,
                            SUM(r.purchases1d) AS purchases
                        FROM amz_ads_products c
                        JOIN amz_ads_campaign_performance_report r
                            ON r.campaign_id = c.campaign_id
                        WHERE r.c_date >= ?
                          AND r.c_date < ?
                        GROUP BY c.sku, c.asin

                        UNION ALL

                        -- SB
                        SELECT
                            c.sku,
                            c.asin,
                            SUM(r.cost),
                            SUM(r.sales),
                            SUM(r.purchases)
                        FROM amz_ads_products_sb c
                        JOIN amz_ads_campaign_performance_reports_sb r
                            ON r.campaign_id = c.campaign_id
                        WHERE r.date >= ?
                          AND r.date < ?
                        GROUP BY c.sku, c.asin

                        UNION ALL

                        -- SD
                        SELECT
                            c.sku,
                            c.asin,
                            SUM(r.cost),
                            SUM(r.sales),
                            SUM(r.purchases)
                        FROM amz_ads_products_sd c
                        JOIN amz_ads_campaign_performance_report_sd r
                            ON r.campaign_id = c.campaign_id
                        WHERE r.c_date >= ?
                          AND r.c_date < ?
                        GROUP BY c.sku, c.asin
                    ) t
                    GROUP BY sku, asin
                ) a
                    ON a.asin = pa.asin1
                   AND a.sku  = p.sku
            ", [
                // sales
                $startDate,
                $endDate,

                // SP
                $startDate,
                $endDate,

                // SB
                $startDate,
                $endDate,

                // SD
                $startDate,
                $endDate,
            ]);

            if ($rows) {
                $bulkData = array_map(fn($row) => [
                    'sku'        => $row->sku,
                    'asin'       => $row->asin,
                    'month'      => $startDate,
                    'sold'       => (int) $row->sold,
                    'revenue'    => (float) $row->revenue,
                    'ad_spend'   => (float) $row->ad_spend,
                    'ad_sales'   => (float) $row->ad_sales,
                    'ad_units'   => (int) $row->ad_units,
                    'acos'       => min((float) $row->acos, 999999.99),
                    'tacos'      => min((float) $row->tacos, 999999.99),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rows);

                MonthlyAdsProductPerformance::upsert(
                    $bulkData,
                    ['sku', 'asin', 'month'],
                    ['sold', 'revenue', 'ad_spend', 'ad_sales', 'ad_units', 'acos', 'tacos', 'updated_at']
                );
            }

            Log::info("Monthly aggregation completed: {$this->month}");
        } catch (Exception $e) {
            Log::error("Monthly aggregation failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
