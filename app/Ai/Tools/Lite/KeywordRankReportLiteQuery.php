<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\KeywordRankReport360Bi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class KeywordRankReportLiteQuery extends BaseSqlTool
{
    public function description(): string|Stringable
    {
        return <<<'TEXT'
            PURPOSE: Read-only MySQL query tool for keyword rank analysis using table `keyword_rank_report_360_bi`.
            ALLOWED COLUMNS: id, product, child, asin, keyword, match_type, search_volume, report_date, rank_value, created_at, updated_at
            RULES: 
            - Generate safe SELECT queries. 
            - ALWAYS return the LATEST available data by default unless a time-series/trend is requested using report_date.
            - If you do not know the latest date, run a query to find the latest value: `SELECT MAX(report_date) FROM keyword_rank_report_360_bi`.
            - To get the LATEST rank for an ASIN/Keyword once the date is known, use `WHERE report_date = 'YYYY-MM-DD'` or `ORDER BY report_date DESC LIMIT 1`.
            - Filter early. Use SUM() or GROUP BY for trends/totals instead of arbitrary raw rows.
            - NOTE: `search_volume` is updated weekly, so it remains constant for the entire week. Do not include it in every summary response unless detailed analysis is requested.
            
            TABLE `product_categorisations`: parent_asin, child_asin, parent_short_name, child_short_name, category, brand.
            JOIN RULE: To filter by category or get additional product mapping, JOIN `keyword_rank_report_360_bi` ON `keyword_rank_report_360_bi.asin = product_categorisations.child_asin`.
            
            PARTIAL MATCHING: When filtering by `keyword` or `product`, ALWAYS use the `LIKE` operator with wildcards (e.g., `keyword LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
            CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA. ONLY return exactly what this tool provides.

            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            KeywordRankReport360Bi::class,
            \App\Models\ProductCategorisation::class,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query on keyword_rank_report_360_bi. This is used for the lightweight chat preview.'),

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
