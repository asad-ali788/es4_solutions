<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\DailySales;
use App\Models\ProductCategorisation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class TopSellingProductsLiteQuery extends BaseSqlTool
{
    public function description(): string|Stringable
    {
        return <<<'TEXT'
            PURPOSE: Read-only MySQL query tool for top selling products and sales analysis using tables `daily_sales` and `product_categorisations`.
            
            TABLE `daily_sales`: id, sku, asin, product_listings_id, marketplace_id (Amazon.com, Amazon.ca, Amazon.com.mx), sale_date, sale_datetime, total_units, total_revenue, total_cost, total_profit, currency
            TABLE `product_categorisations`: id, parent_short_name, child_short_name, parent_asin, child_asin, marketplace, seasonal_type
            
            JOIN RULE: To get product names, JOIN `daily_sales` ON `daily_sales.asin = product_categorisations.child_asin`.
            
            RULES: 
            - Generate safe SELECT queries. 
            - ALWAYS return the LATEST available data by default unless a time-series/trend is requested using sale_date.
            - If you do not know the latest date, run a query to find the latest value: `SELECT MAX(sale_date) FROM daily_sales`.
            - `total_revenue` is total sales value. `total_units` is the count of items sold.
            - Filter early. Use SUM() or GROUP BY for trends/totals instead of arbitrary raw rows.
            PARTIAL MATCHING: When searching for product names (`child_short_name` or `parent_short_name`), ALWAYS use the `LIKE` operator with wildcards (e.g., `product_categorisations.child_short_name LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
            CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA. ONLY return exactly what this tool provides. Do not invent products or metrics.
            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            DailySales::class,
            ProductCategorisation::class,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query on daily_sales or product_categorisations. This is used for the lightweight chat preview.'),

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
