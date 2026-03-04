<?php

namespace App\Jobs\Forecast;

use App\Models\DailyAdsProductPerformance;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDailySkuAsinMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $date) {}

    public function handle(): void
    {
        $date = Carbon::parse($this->date)->toDateString();
        $now  = now();

        Log::info("Daily aggregation started for {$date}");

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

                -- DAILY sales
                LEFT JOIN (
                    SELECT 
                        sku,
                        asin,
                        SUM(total_units)   AS sold,
                        SUM(total_revenue) AS revenue
                    FROM daily_sales
                    WHERE sale_date = ?
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
                        WHERE r.c_date = ?
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
                        WHERE r.date = ?
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
                        WHERE r.c_date = ?
                        GROUP BY c.sku, c.asin
                    ) t
                    GROUP BY sku, asin
                ) a
                    ON a.asin = pa.asin1
                   AND a.sku  = p.sku
            ", [
                $date, // daily_sales
                $date, // SP
                $date, // SB
                $date, // SD
            ]);

            if ($rows) {
                $data = array_map(fn($row) => [
                    'sku'        => $row->sku,
                    'asin'       => $row->asin,
                    'sale_date'      => $date, // daily stored here
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

                DailyAdsProductPerformance::upsert(
                    $data,
                    ['sku', 'asin', 'sale_date'],
                    ['sold', 'revenue', 'ad_spend', 'ad_sales', 'ad_units', 'acos', 'tacos', 'updated_at']
                );
            }

            Log::info("Daily aggregation completed for {$date}");
        } catch (Exception $e) {
            Log::error("Daily aggregation failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
