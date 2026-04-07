<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Models\HourlyProductSales;
use App\Models\ProductCategorisation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class HourlyProductSalesLiteQuery extends BaseSqlTool
{
    public function description(): string
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');
        $nowPst = \Carbon\Carbon::now($marketTz);
        $todayPst = $nowPst->toDateString();
        $yesterdayPst = $nowPst->copy()->subDay()->toDateString();
        $nowTimestamp = $nowPst->toDateTimeString();

        return <<<TEXT
            PURPOSE: Read-only MySQL query tool for hourly product sales snapshots.
            
            REFERENCE TIME (PST):
            - Today (PST): {$todayPst}
            - Yesterday (PST): {$yesterdayPst}
            
            TABLE `hourly_product_sales` (hps):
            - `asin`, `sku`: Product identifiers.
            - `sale_hour`: TIMESTAMP (stored in PST).
            - `total_units`: Units sold in that hour. (REVENUE: `SUM(item_price * COALESCE(cur.conversion_rate_to_usd, 1)) as revenue_usd`).
            
            STRICT QUERY RULE:
            - ALWAYS use the following structure for hourly reports. DO NOT use DATE_FORMAT.
            `select sale_hour as snapshot_time, SUM(total_units) as total_units from hourly_product_sales where sale_hour between ? and ? and deleted_at is null group by sale_hour order by sale_hour desc`
            
            TIMEZONE & DATE BOUNDS (PST):
            - Current PDT/PST Date is: {$todayPst}
            - Today (PST) Bounds: '{$todayPst} 00:00:00' and '{$todayPst} 23:59:59'
            - Yesterday (PST) Bounds: '{$yesterdayPst} 00:00:00' and '{$yesterdayPst} 23:59:59'
            
            SQL EXAMPLE (MANDATORY STRUCTURE):
            "select sale_hour as snapshot_time, SUM(total_units) as total_units from hourly_product_sales where sale_hour between '{$todayPst} 00:00:00' and '{$todayPst} 23:59:59' and deleted_at is null group by sale_hour order by sale_hour desc"
            
            CRITICAL RULE: USE THE PROVIDED TEMPLATE. DO NOT use DATE_FORMAT or GROUP BY hour strings.
            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            HourlyProductSales::class,
            ProductCategorisation::class,
            'currencies',
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description('REQUIRED. A single read-only MySQL SELECT query on hourly_product_sales, product_categorisations, or currencies. This is used for the lightweight chat preview.'),

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
