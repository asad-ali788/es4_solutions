<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\ProductCategorisation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class InventoryLiteQuery extends BaseSqlTool
{
    public function description(): string|Stringable
    {
        return <<<'TEXT'
            PURPOSE: Read-only MySQL query tool for ASIN inventory and stock data mapping across all warehouses.
            
            TABLE `asin_inventory_summary_bi`: id, asin, fba_available, fba_inbound_working, fba_inbound_shipped, fba_inbound_receiving, fc_reserved, awd_available, awd_inbound, apa_warehouse_available, flex_warehouse_available, shipout_warehouse_inventory, tactical_warehouse_inventory, last_synced_at
            
            TABLE `product_categorisations`: id, parent_short_name, child_short_name, parent_asin, child_asin, marketplace, seasonal_type
            
            JOIN RULE: To get product names, JOIN `asin_inventory_summary_bi` ON `asin_inventory_summary_bi.asin = product_categorisations.child_asin`.
            
            RULES: 
            - Generate safe SELECT queries. 
            - Always prefer returning standard integer metrics directly from the DB.
            - Filter early. Use SUM() or GROUP BY for aggregates/totals instead of arbitrary raw rows if asked for totals.
            PARTIAL MATCHING: When searching for product names (`child_short_name`), ALWAYS use the `LIKE` operator with wildcards (e.g., `product_categorisations.child_short_name LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
            CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA. ONLY return exactly what this tool provides. Do not invent products or metrics.
            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            'asin_inventory_summary_bi',
            ProductCategorisation::class,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query on asin_inventory_summary_bi or product_categorisations. This is used for the lightweight chat preview.'),

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
