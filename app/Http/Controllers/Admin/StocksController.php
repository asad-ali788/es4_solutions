<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\StocksEnum;
use App\Exports\StocksExportAsin;
use App\Exports\StocksExportSku;
use App\Http\Controllers\Controller;
use App\Models\AfnInventoryData;
use App\Models\FbaInventoryUsa;
use App\Models\InboundShipmentDetailsSp;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductAsins;
use App\Models\ProductWhInventory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StocksController extends Controller
{
    public function skuStocks(Request $request)
    {
        $this->authorize(StocksEnum::StocksSku);
        $warehouses = Warehouse::all();

        // --- Subqueries for main data ----
        $afnSub = AfnInventoryData::select('seller_sku', DB::raw('SUM(quantity_available) as afn_quantity'))
            ->groupBy('seller_sku');

        $fbaSub = FbaInventoryUsa::select('sku', DB::raw('SUM(totalstock) as fba_totalstock'))
            ->groupBy('sku');

        $inboundSub = DB::table('inbound_shipment_details_sps as inbound')
            ->leftJoin('inbound_shipment_sps as sps', 'inbound.ship_id', '=', 'sps.shipment_id')
            ->where(function ($q) {
                $q->whereNull('sps.ship_status')
                    ->orWhere('sps.ship_status', '!=', 'CLOSED');
            })
            ->select(
                'inbound.sku',
                DB::raw('GREATEST(SUM(COALESCE(inbound.qty_ship,0)) - SUM(COALESCE(inbound.qty_received,0)), 0) as inbound_qty')
            )
            ->groupBy('inbound.sku');

        // --- Base Product Query ---
        $stocksQuery = Product::query()
            ->leftJoinSub($afnSub, 'afn', fn($join) => $join->on('products.sku', '=', 'afn.seller_sku'))
            ->leftJoinSub($fbaSub, 'fba', fn($join) => $join->on('products.sku', '=', 'fba.sku'))
            ->leftJoinSub($inboundSub, 'inbound', fn($join) => $join->on('products.sku', '=', 'inbound.sku'))
            ->select([
                'products.sku',
                DB::raw('COALESCE(afn.afn_quantity, 0) as afn_quantity'),
                DB::raw('COALESCE(fba.fba_totalstock, 0) as fba_totalstock'),
                DB::raw('COALESCE(inbound.inbound_qty, 0) as inbound_qty'),
            ]);

        // --- Warehouse stock + last updated per warehouse ---
        $warehouseLastUpdated = [];

        foreach ($warehouses as $wh) {
            $whSub = DB::table('product_wh_inventory')
                ->select(
                    'product_id',
                    DB::raw('SUM(available_quantity) as available'),
                    DB::raw('MAX(updated_at) as last_updated')
                )
                ->where('warehouse_id', $wh->id)
                ->groupBy('product_id');

            $alias = 'wh_' . $wh->id;

            $stocksQuery->leftJoinSub($whSub, $alias, function ($join) use ($alias) {
                $join->on('products.id', '=', $alias . '.product_id');
            });

            $stocksQuery->addSelect([
                DB::raw("COALESCE({$alias}.available, 0) as wh_{$wh->id}_stock"),
            ]);

            // Get per-warehouse last updated globally (for header)
            $warehouseLastUpdated[$wh->id] = ProductWhInventory::where('warehouse_id', $wh->id)->max('updated_at');
        }

        if ($request->filled('search')) {
            $stocksQuery->where('products.sku', 'like', '%' . $request->search . '%');
        }

        $stocks = $stocksQuery->paginate($request->get('per_page', 25));

        // --- Global last updated timestamps for other tables ---
        $lastUpdated = [
            'afn'        => AfnInventoryData::max('updated_at'),
            'fba'        => FbaInventoryUsa::max('updated_at'),
            'inbound'    => InboundShipmentDetailsSp::max('updated_at'),
            'warehouses' => $warehouseLastUpdated
        ];
        return view('pages.admin.stocks.sku', compact('stocks', 'warehouses', 'lastUpdated'));
    }

    public function exportSku(Request $request)
    {
        $this->authorize(StocksEnum::StocksSkuExport);
        $warehouses = Warehouse::get();

        return Excel::download(
            new StocksExportSku($warehouses, $request->search),
            'stocks_export_sku_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function asinStocks(Request $request)
    {
        $this->authorize(StocksEnum::StocksAsin);

        $warehouses = Warehouse::all();
        $asinSearch = $request->input('search');

        // --- AFN per SKU ---
        $afnSub = DB::table('afn_inventory_data')
            ->where('quantity_available', '>', 0) // only SKUs with stock
            ->select('seller_sku', DB::raw('SUM(quantity_available) as afn_quantity'))
            ->groupBy('seller_sku');

        // --- FBA per SKU ---
        $fbaSub = DB::table('fba_inventory_usa')
            ->where('totalstock', '>', 0) // only SKUs with stock
            ->select('sku', DB::raw('SUM(totalstock) as fba_total_stock'))
            ->groupBy('sku');

        // --- Inbound per SKU (exclude CLOSED shipments) ---
        $inboundSkuSub = DB::table('inbound_shipment_details_sps as inbound')
            ->leftJoin('inbound_shipment_sps as sps', 'inbound.ship_id', '=', 'sps.shipment_id')
            ->where(function ($q) {
                $q->whereNull('sps.ship_status')
                    ->orWhere('sps.ship_status', '!=', 'CLOSED');
            })
            ->select(
                'inbound.sku',
                DB::raw('GREATEST(SUM(COALESCE(inbound.qty_ship,0)) - SUM(COALESCE(inbound.qty_received,0)), 0) as inbound_qty_per_sku')
            )
            ->groupBy('inbound.sku');

        // --- Inbound per ASIN (only SKUs that have inbound) ---
        $inboundAsinSub = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->joinSub($inboundSkuSub, 'inbound', 'inbound.sku', '=', 'p.sku')
            ->select('pa.asin1', DB::raw('SUM(inbound.inbound_qty_per_sku) as inbound_qty'))
            ->groupBy('pa.asin1');

        // --- Warehouses per ASIN (only SKUs with stock) ---
        $warehouseSubs = [];
        foreach ($warehouses as $wh) {
            $warehouseSubs[$wh->id] = DB::table('product_wh_inventory as wh')
                ->join('products as p', 'wh.product_id', '=', 'p.id')
                ->join('product_asins as pa', 'p.id', '=', 'pa.product_id')
                ->where('wh.available_quantity', '>', 0) // only SKUs with stock
                ->where('wh.warehouse_id', $wh->id)
                ->select('pa.asin1', DB::raw('SUM(wh.available_quantity) as total'))
                ->groupBy('pa.asin1');
        }

        // --- Main query ---
        $query = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->leftJoinSub($afnSub, 'afn', fn($join) => $join->on('afn.seller_sku', '=', 'p.sku'))
            ->leftJoinSub($fbaSub, 'fba', fn($join) => $join->on('fba.sku', '=', 'p.sku'))
            ->leftJoinSub($inboundAsinSub, 'inbound', fn($join) => $join->on('inbound.asin1', '=', 'pa.asin1'));

        // Join warehouse subqueries dynamically
        foreach ($warehouses as $wh) {
            $query->leftJoinSub(
                $warehouseSubs[$wh->id],
                "wh_{$wh->id}",
                fn($join) => $join->on("wh_{$wh->id}.asin1", '=', 'pa.asin1')
            );
        }

        // --- Select aggregated columns ---
        $selects = [
            'pa.asin1',
            DB::raw('SUM(COALESCE(afn.afn_quantity,0)) as afn_quantity'),
            DB::raw('SUM(COALESCE(fba.fba_total_stock,0)) as fba_total_stock'),
            DB::raw('MAX(COALESCE(inbound.inbound_qty,0)) as inbound_qty')
        ];

        foreach ($warehouses as $wh) {
            $selects[] = DB::raw("MAX(COALESCE(wh_{$wh->id}.total,0)) as wh_{$wh->id}_available");
        }

        $query->select($selects)
            ->groupBy('pa.asin1');

        // --- ASIN search ---
        if (!empty($asinSearch)) {
            $query->where('pa.asin1', 'like', "%{$asinSearch}%");
        }

        $stocks = $query->paginate($request->get('per_page', 25));

        return view('pages.admin.stocks.asin', compact('stocks', 'warehouses', 'asinSearch'));
    }

    public function exportAsin(Request $request)
    {
        $this->authorize(StocksEnum::StocksAsinExport);

        $warehouses = Warehouse::get();

        return Excel::download(
            new StocksExportAsin($warehouses, $request->search),
            'stocks_export_asin_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}
