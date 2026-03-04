<?php

namespace App\Services\Seller;

use Illuminate\Support\Facades\DB;

class StockService
{
    public function buildStockSummary($productIds, $skus, $asin): array
    {
        $stockSummary = [
            'afn_quantity' => 0,
            'fba_total_stock' => 0,
            'total_qty_shipped' => 0,
            'total_qty_received' => 0,
            'wh_available' => 0,
            'tactical_wh_available' => 0,
            'afd_wh_available' => 0,
            'countries' => [],
        ];

        $stockSummary['fba_total_stock'] = (int) DB::table('fba_inventory_usa')
            ->whereIn('sku', $skus)
            ->sum('totalstock');

        $stockSummary['countries'] = DB::table('fba_inventory_usa')
            ->whereIn('sku', $skus)
            ->distinct()
            ->pluck('country')
            ->toArray();

        $shipmentTotals = DB::table('inbound_shipment_details_sps')
            ->whereIn('sku', $skus)
            ->select(DB::raw('SUM(qty_ship) as shipped'), DB::raw('SUM(qty_received) as received'))
            ->first();

        $stockSummary['total_qty_shipped'] = (int) ($shipmentTotals->shipped ?? 0);
        $stockSummary['total_qty_received'] = (int) ($shipmentTotals->received ?? 0);

        $stockSummary['afn_quantity'] = (int) DB::table('afn_inventory_data')
            ->whereIn('seller_sku', $skus)
            ->where('asin', $asin)
            ->sum('quantity_available');

        $whTotals = DB::table('product_wh_inventory')
            ->join('products', 'product_wh_inventory.product_id', '=', 'products.id')
            ->whereIn('products.id', $productIds)
            ->select('warehouse_id', DB::raw('SUM(available_quantity) as total'))
            ->groupBy('warehouse_id')
            ->get();

        foreach ($whTotals as $row) {
            $stockSummary['wh_available'] += (int) $row->total;
            if ($row->warehouse_id == 2) $stockSummary['tactical_wh_available'] += (int) $row->total;
            if ($row->warehouse_id == 3) $stockSummary['afd_wh_available'] += (int) $row->total;
        }

        return $stockSummary;
    }
}
