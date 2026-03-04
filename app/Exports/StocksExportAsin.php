<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StocksExportAsin implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $warehouses;
    protected $asin;

    public function __construct($warehouses, $asin = null)
    {
        $this->warehouses = $warehouses;
        $this->asin = $asin;
    }

    public function collection()
    {
        $warehouses = $this->warehouses;

        // --- AFN per SKU (only SKUs with stock) ---
        $afnSub = DB::table('afn_inventory_data')
            ->where('quantity_available', '>', 0)
            ->select('seller_sku', DB::raw('SUM(quantity_available) as afn_quantity'))
            ->groupBy('seller_sku');

        // --- FBA per SKU (only SKUs with stock) ---
        $fbaSub = DB::table('fba_inventory_usa')
            ->where('totalstock', '>', 0)
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
                DB::raw('GREATEST(SUM(COALESCE(inbound.qty_ship,0)) - SUM(COALESCE(inbound.qty_received,0)),0) as inbound_qty_per_sku')
            )
            ->groupBy('inbound.sku');

        // --- Inbound per ASIN (only SKUs that exist in inbound) ---
        $inboundAsinSub = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->joinSub($inboundSkuSub, 'inbound', 'inbound.sku', '=', 'p.sku')
            ->select('pa.asin1', DB::raw('MAX(inbound.inbound_qty_per_sku) as inbound_qty'))
            ->groupBy('pa.asin1');

        // --- Warehouses per ASIN (only SKUs with stock) ---
        $warehouseSubs = [];
        foreach ($warehouses as $wh) {
            $warehouseSubs[$wh->id] = DB::table('product_wh_inventory as wh')
                ->join('products as p', 'wh.product_id', '=', 'p.id')
                ->join('product_asins as pa', 'p.id', '=', 'pa.product_id')
                ->where('wh.available_quantity', '>', 0)
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

        // join warehouses dynamically
        foreach ($warehouses as $wh) {
            $query->leftJoinSub(
                $warehouseSubs[$wh->id],
                "wh_{$wh->id}",
                fn($join) => $join->on("wh_{$wh->id}.asin1", '=', 'pa.asin1')
            );
        }

        // --- Select final columns ---
        $selects = [
            'pa.asin1',
            DB::raw('SUM(COALESCE(afn.afn_quantity,0)) as afn_quantity'),
            DB::raw('SUM(COALESCE(fba.fba_total_stock,0)) as fba_total_stock'),
            DB::raw('MAX(COALESCE(inbound.inbound_qty,0)) as inbound_qty'),
        ];

        foreach ($warehouses as $wh) {
            $selects[] = DB::raw("MAX(COALESCE(wh_{$wh->id}.total,0)) as wh_{$wh->id}_available");
        }

        $query->select($selects)
            ->groupBy('pa.asin1');

        // Apply ASIN filter if given
        if ($this->asin) {
            $query->where('pa.asin1', 'like', "%{$this->asin}%");
        }

        return $query->get();
    }


    public function headings(): array
    {
        $headings = ['ASIN', 'AFN Quantity', 'FBA Total Stock', 'Inbound Qty'];
        foreach ($this->warehouses as $wh) {
            $headings[] = $wh->warehouse_name;
        }
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $totalColumns = count($this->headings());
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

        // Header row style
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'D9E1F2']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'A6A6A6']]],
        ]);

        return [];
    }
}
