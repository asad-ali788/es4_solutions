<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\BrandAnalyticsWeeklyDataBi;
use App\Models\BrandAnalytics2024Bi;
use App\Models\ProductCategorisation;
use App\Models\KeywordRankReport360Bi;
use App\Models\CompetitorRank360Bi;
use App\Models\TopSearchBi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class BrandAnalyticLiteQuery extends BaseSqlTool
{
    public function description(): string|Stringable
    {
        return <<<'TEXT'
                PURPOSE: Read-only MySQL query tool for Amazon Brand Analytics (ABA), Top Search, and Keyword/Competitor Rankings.

                TABLE SCHEMAS:

                1. brand_analytics_weekly_data_bi (Weekly performance data)
                   - columns: asin, week_number, week_date (YYYY-MM-DD), week_year, impressions, clicks, orders.
                   - Date Filter: Use `week_date = (SELECT MAX(week_date) FROM brand_analytics_weekly_data_bi)` for latest data.

                2. brand_analytics_2024_bis (Detailed historical query performance)
                   - columns: asin, name, search_query, search_query_score, search_query_volume, reporting_date, week, year, impressions_total_count (clicks/purchases also have total/asin counts).
                   - Date Filter: Use `reporting_date = (SELECT MAX(reporting_date) FROM brand_analytics_2024_bis)` for latest.

                3. keyword_rank_report_360_bi (Internal keyword ranking)
                   - columns: asin, keyword, rank_value, report_date, search_volume, match_type.
                   - Date Filter: Use `report_date = (SELECT MAX(report_date) FROM keyword_rank_report_360_bi)`.

                4. competitor_rank_360_bi (Competitor organic position)
                   - columns: asin, keyword, rank_value, report_date.
                   - Date Filter: Use `report_date = (SELECT MAX(report_date) FROM competitor_rank_360_bi)`.

                5. top_search_bis (Global top search term trends)
                   - columns: search_frequency_rank, search_term, top_clicked_product_1_asin, top_clicked_product_2_asin, top_clicked_product_3_asin, week, reporting_date.
                   - Date Filter: Use `reporting_date = (SELECT MAX(reporting_date) FROM top_search_bis)`.

                6. product_categorisations (Product metadata/names)
                   - columns: parent_asin, child_asin, parent_short_name, child_short_name.
                   - JOIN: Match on `asin` to `child_asin` to get `child_short_name` (Product Name).

                TABLE USE CASES:
                - Use `brand_analytics_weekly_data_bi`: For our ASINs' weekly sales performance (Impressions, Clicks, Orders). Best for finding "top products" or sales trends.
                - Use `brand_analytics_2024_bis`: For our products' deep query-level metrics (Search Query, Search Volume, Rankings, etc.). Best for analyzing performance for specific search terms.
                - Use `keyword_rank_report_360_bi`: For checking our organic ranking for specific keywords. Best for monitoring SEO.
                - Use `competitor_rank_360_bi`: For comparing our rank against competitors. Best for competitive benchmarking.
                - Use `top_search_bis`: For broad market trends. Best for identifying what's trending globally on Amazon and who the top clicked competitors are for those terms.
                - Use `product_categorisations`: ALWAYS join this table on `asin = child_asin` to get human-readable product names (`child_short_name`).

                CRITICAL EXECUTION RULES:

                - Latest Data: Always filter by the MOST RECENT date column relevant to that specific table. DO NOT assume all tables use `report_date`. Refer to schemas above!
                - Joins: Always JOIN with `product_categorisations` on `asin = child_asin` to include `child_short_name` as `product_name`.
                - Export Query (export_sql): ALWAYS provide a comprehensive query in `export_sql` for Excel downloads. This query MUST include:
                    a. Product Name (from product_categorisations).
                    b. ASIN.
                    c. All available metrics (Impressions, Clicks, Orders/Purchases).
                    d. Date/Week columns.
                - Sorting: For rankings, `ORDER BY rank_value ASC` (lower is better). For performance, `ORDER BY orders DESC` or `ORDER BY impressions DESC`.

                EXAMPLE QUERIES:

                1. Top performers for latest week (Chat Preview):
                   SELECT b.asin, p.child_short_name as product_name, SUM(b.orders) as total_orders
                   FROM brand_analytics_weekly_data_bi b
                   JOIN product_categorisations p ON b.asin = p.child_asin
                   WHERE b.week_date = (SELECT MAX(week_date) FROM brand_analytics_weekly_data_bi)
                   GROUP BY b.asin, p.child_short_name ORDER BY total_orders DESC LIMIT 10;

                2. Detailed Keyword Performance (Export Ready):
                   SELECT b.asin, p.child_short_name as product_name, b.week_date, b.impressions, b.clicks, b.orders
                   FROM brand_analytics_weekly_data_bi b
                   JOIN product_categorisations p ON b.asin = p.child_asin
                   WHERE b.week_date = (SELECT MAX(week_date) FROM brand_analytics_weekly_data_bi)
                   ORDER BY b.orders DESC;
                TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            BrandAnalyticsWeeklyDataBi::class,
            BrandAnalytics2024Bi::class,
            ProductCategorisation::class,
            KeywordRankReport360Bi::class,
            CompetitorRank360Bi::class,
            TopSearchBi::class,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query on brand_analytics_weekly_data_bi, brand_analytics_2024_bis, keyword_rank_report_360_bi, competitor_rank_360_bi, top_search_bis, or product_categorisations. This is used for the lightweight chat preview.'),

            'export_sql' => $schema
                ->string()
                ->nullable()
                ->description('OPTIONAL. A detailed read-only MySQL SELECT query with all relevant columns for Excel export.'),

            'expect_scalar' => $schema
                ->boolean()
                ->nullable()
                ->description('True if the query returns exactly one scalar value.'),

            'max_rows' => $schema
                ->integer()
                ->min(1)
                ->nullable()
                ->description('Row limit for listed results.'),
        ];
    }
}
