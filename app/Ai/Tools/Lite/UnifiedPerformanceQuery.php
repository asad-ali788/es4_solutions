<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\Ai\KeywordCampaignPerformanceLite;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class UnifiedPerformanceQuery extends BaseSqlTool
{
    public function description(): string|Stringable
    {
        return <<<'TEXT'
            PURPOSE: Read-only MySQL query tool for detailed keyword + campaign performance data from `keyword_campaign_performance_lites`.
            ALLOWED COLUMNS: campaign_id, campaign_name, campaign_type, campaign_state, keyword_text, keyword_state, keyword_bid, keyword_match_type, product_name, asin, country, report_date, total_spend, total_sales, acos, roas, purchases, clicks, impressions, ctr, cpc, conversion_rate, daily_budget, estimated_monthly_budget, product_price, product_rating, product_review_count
            RULES: 
            - Generate safe SELECT queries. 
            - CRITICAL: NO DATE MIXING. NEVER return data from multiple days in a single result table unless a 'trend', 'history', or 'comparison' is explicitly requested.
            - ALWAYS return the LATEST available data by default by filtering: `WHERE report_date = (SELECT MAX(report_date) FROM keyword_campaign_performance_lites)`.
            - If you do not know the latest date, run a query to find the latest value: `SELECT MAX(report_date) FROM keyword_campaign_performance_lites`.
            - Filter early. Use SUM() or GROUP BY for trends/totals instead of raw rows.
            
            TABLE `product_categorisations`: parent_asin, child_asin, parent_short_name, child_short_name, category, brand.
            JOIN RULE: To filter by category or get additional product mapping, JOIN `keyword_campaign_performance_lites` ON `keyword_campaign_performance_lites.asin = product_categorisations.child_asin`.
            
            PARTIAL MATCHING: When filtering by `product_name`, `campaign_name`, or `keyword_text`, ALWAYS use the `LIKE` operator with wildcards (e.g., `product_name LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
            CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA. ONLY return exactly what this tool provides. Do not invent campaigns, keywords, or metrics.
            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            KeywordCampaignPerformanceLite::class,
            \App\Models\ProductCategorisation::class,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query. This is used for the lightweight chat preview.'),

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
