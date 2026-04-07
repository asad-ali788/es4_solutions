<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

/**
 * CampaignKeywordRecommendationsQuery - Direct SQL tool for campaign_keyword_recommendations_lite (keyword recommendations).
 */
final class CampaignKeywordRecommendationsQuery extends BaseSqlTool
{
    public function description(): Stringable|string
    {
        return <<<'TEXT'
                PURPOSE: Query AWS keyword recommendations from `campaign_keyword_recommendations_lite`.
                ALLOWED COLUMNS: id, asin, campaign_id, campaign_name, keyword, match_type, bid_suggestion_start, bid_suggestion_mid, bid_suggestion_end, current_bid, created_at, updated_at
                RULES: Generate safe, read-only MySQL SELECT queries. Use aggregate if computing totals. Do not mutate data.
                      Dont show campaign_id to the user
                
                TABLE `product_categorisations`: parent_asin, child_asin, parent_short_name, child_short_name, category, brand.
                JOIN RULE: To filter by category or get additional product mapping, JOIN `campaign_keyword_recommendations_lite` ON `campaign_keyword_recommendations_lite.asin = product_categorisations.child_asin`.
                
                PARTIAL MATCHING: When filtering by `keyword` or `campaign_name`, ALWAYS use the `LIKE` operator with wildcards (e.g., `campaign_name LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
                CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA. ONLY return exactly what this tool provides. Do not invent keywords, metrics, or recommendations.
                TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            'campaign_keyword_recommendations_lite',
            'product_categorisations',
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A valid MySQL SELECT query using only allowed columns from campaign_keyword_recommendations_lite. This is used for the lightweight chat preview.'),

            'export_sql' => $schema
                ->string()
                ->nullable()
                ->description('OPTIONAL. A detailed read-only MySQL SELECT query with all relevant columns for Excel export.'),

            'expect_scalar' => $schema
                ->boolean()
                ->description('True if expecting a single scalar value (e.g., SUM, COUNT).')
                ->nullable(),
            'max_rows' => $schema
                ->integer()
                ->min(1)
                ->description('Maximum rows to return.')
                ->nullable(),
        ];
    }
}
