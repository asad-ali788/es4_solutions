<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\Ai\CampaignPerformanceLite;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class CampaignPerformanceLiteQuery extends BaseSqlTool
{
    public function description(): string|Stringable
    {
        return <<<'TEXT'
            PURPOSE: Read-only MySQL query tool for Amazon campaign performance analysis from `campaign_performance_lite`.
            ALLOWED COLUMNS: campaign_id, campaign_name (Campaign Name), campaign_types (SP, SB, SD), campaign_state (enabled, paused, archived), country (US, CA, MX), report_date (YYYY-MM-DD), total_daily_budget, total_spend, total_sales, acos, purchases7d.
            NOTE: This is a LITE table. It DOES NOT support metrics like `clicks`, `impressions`, `ctr`, `cpc`, or `roas`. Use `UnifiedPerformanceQuery` if the user asks for these metrics.
            
            RULES: 
            - Generate safe SELECT queries. 
            - CRITICAL: NO DATE MIXING. NEVER return data from multiple days in a single result table unless a 'trend', 'history', or 'comparison' is explicitly requested.
            - ALWAYS return the LATEST available data by default by filtering: `WHERE report_date = (SELECT MAX(report_date) FROM campaign_performance_lite)`.
            - If you do not know the latest date, run a query to find the latest value: `SELECT MAX(report_date) FROM campaign_performance_lite`.
            - Filter early. Use SUM() or GROUP BY for trends/totals instead of arbitrary raw rows.
            
            TABLE `amz_ads_products` (P): Bridging table. Join via `campaign_id` to get `asin`.
            TABLE `product_categorisations` (PC): Reference for names and categories.
            JOIN RULE: To filter by product name or category, JOIN `campaign_performance_lite` ON `campaign_performance_lite.campaign_id = amz_ads_products.campaign_id` THEN JOIN `product_categorisations` ON `amz_ads_products.asin = product_categorisations.child_asin`.
            
            PARTIAL MATCHING: When filtering by `campaign_name`, ALWAYS use the `LIKE` operator with wildcards (e.g., `campaign_name LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
            CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA. ONLY return exactly what this tool provides. Do not invent campaigns or metrics.
            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            CampaignPerformanceLite::class,
            \App\Models\ProductCategorisation::class,
            'amz_ads_products',
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query on campaign_performance_lites. This is used for the lightweight chat preview.'),

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
