<?php

namespace App\Ai\Tools;

use App\Services\Ai\AiChatBotServices;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

final class WarehouseStockDetails implements Tool
{
    public function description(): Stringable|string
    {
        return implode(' ', [
            'Fetch warehouse stock details from internal database tables product_wh_inventory and warehouses.',
            'Supports filters by asin (comma-separated for multiple), sku (comma-separated for multiple), and warehouse_name.',
            'For multiple SKUs or ASINs, pass them as comma-separated values (e.g., "SKU1,SKU2,SKU3" or "ASIN1,ASIN2").',
            'Returns strict stock columns: AFN, FBA, Inbound, ShipOut, Tactical, AWD, plus stock availability status.',
            'Optional stock_bucket filter supports AFN, FBA, INBOUND, SHIPOUT, TACTICAL, AWD.',
            'Use this tool when user asks stock available or not, warehouse stock breakdown, or stock by SKU/ASIN.',
            'Optimized for bulk queries - prefer passing multiple SKUs/ASINs in one call instead of multiple calls.',
        ]);
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            /** @var AiChatBotServices $service */
            $service = app(AiChatBotServices::class);

            $asin = isset($request['asin']) && is_string($request['asin'])
                ? trim($request['asin'])
                : null;

            $sku = isset($request['sku']) && is_string($request['sku'])
                ? trim($request['sku'])
                : null;

            $warehouseName = isset($request['warehouse_name']) && is_string($request['warehouse_name'])
                ? trim($request['warehouse_name'])
                : null;

            $stockBucket = isset($request['stock_bucket']) && is_string($request['stock_bucket'])
                ? trim($request['stock_bucket'])
                : null;

            $limit = isset($request['limit']) ? (int) $request['limit'] : 50;

            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 100) {
                $limit = 100;
            }

            $result = $service->warehouseStockDetails(
                asin: $asin,
                sku: $sku,
                warehouseName: $warehouseName,
                stockBucket: $stockBucket,
                limit: $limit,
            );

            return json_encode([
                'items' => $result['items'] ?? [],
                'sku_summary' => $result['sku_summary'] ?? [],
                'stock_summary' => $result['stock_summary'] ?? [],
                'stock_columns' => $result['stock_columns'] ?? ['AFN', 'FBA', 'Inbound', 'ShipOut', 'Tactical', 'AWD'],
                'meta' => [
                    'tool' => 'warehouse_stock_details',
                    'filters' => $result['filters'] ?? [
                        'asin' => $asin,
                        'sku' => $sku,
                        'warehouse_name' => $warehouseName,
                        'stock_bucket' => $stockBucket,
                        'limit' => $limit,
                    ],
                    'source_tables' => ['product_wh_inventory', 'warehouses', 'products', 'product_asins'],
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return json_encode([
                'error' => $e->getMessage(),
                'items' => [],
                'sku_summary' => [],
                'stock_summary' => [],
                'stock_columns' => ['AFN', 'FBA', 'Inbound', 'ShipOut', 'Tactical', 'AWD'],
                'meta' => [
                    'tool' => 'warehouse_stock_details',
                ],
            ], JSON_THROW_ON_ERROR);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asin' => $schema
                ->string()
                ->description('Optional ASIN filter. For single ASIN uses partial match. For multiple ASINs pass comma-separated (e.g., "B08X1,B08X2"). Matches against asin1/asin2/asin3.')
                ->nullable(),

            'sku' => $schema
                ->string()
                ->description('Optional SKU filter. For single SKU uses partial match. For multiple SKUs pass comma-separated (e.g., "SKU-001,SKU-002,SKU-003").')
                ->nullable(),

            'warehouse_name' => $schema
                ->string()
                ->description('Optional warehouse name filter (partial match).')
                ->nullable(),

            'stock_bucket' => $schema
                ->string()
                ->description('Optional strict stock bucket filter: AFN, FBA, INBOUND, SHIPOUT, TACTICAL, AWD.')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum rows to return (1..100). Defaults to 50.')
                ->nullable(),
        ];
    }
}
