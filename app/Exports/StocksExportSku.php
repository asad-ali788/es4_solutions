<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StocksExportSku implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $warehouses;
    protected $sku;

    public function __construct($warehouses, $sku = null)
    {
        $this->warehouses = $warehouses;
        $this->sku = $sku;
    }

    public function collection()
    {
        // --- Subqueries for aggregation ---
        $afnSub = DB::table('afn_inventory_data')
            ->select('seller_sku', DB::raw('SUM(quantity_available) as afn_quantity'))
            ->groupBy('seller_sku');

        $fbaSub = DB::table('fba_inventory_usa')
            ->select('sku', DB::raw('SUM(totalstock) as fba_totalstock'))
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

        // --- Base Query ---
        $query = Product::query()
            ->leftJoin('product_asins', 'products.id', '=', 'product_asins.product_id')
            ->leftJoin('product_categorisations as pc', function($join) {
                $join->on('pc.child_asin', '=', 'product_asins.asin1')
                     ->whereNull('pc.deleted_at');
            })
            ->leftJoinSub($afnSub, 'afn', fn($join) => $join->on('products.sku', '=', 'afn.seller_sku'))
            ->leftJoinSub($fbaSub, 'fba', fn($join) => $join->on('products.sku', '=', 'fba.sku'))
            ->leftJoinSub($inboundSub, 'inbound', fn($join) => $join->on('products.sku', '=', 'inbound.sku'))
            ->select([
                'products.sku',
                'product_asins.asin1 as asin',
                'pc.child_short_name as product_name',
                DB::raw('COALESCE(afn.afn_quantity, 0) as afn_quantity'),
                DB::raw('COALESCE(fba.fba_totalstock, 0) as fba_totalstock'),
                DB::raw('COALESCE(inbound.inbound_qty, 0) as inbound_qty'),
            ]);

        // --- Warehouse aggregation ---
        foreach ($this->warehouses as $wh) {
            $whSub = DB::table('product_wh_inventory')
                ->select(
                    'product_id',
                    DB::raw('SUM(available_quantity) as available')
                )
                ->where('warehouse_id', $wh->id)
                ->groupBy('product_id');

            $alias = 'wh_' . $wh->id;

            $query->leftJoinSub($whSub, $alias, function ($join) use ($alias) {
                $join->on('products.id', '=', $alias . '.product_id');
            });

            $query->addSelect([
                DB::raw("COALESCE({$alias}.available, 0) as wh_{$wh->id}_stock")
            ]);
        }

        if ($this->sku) {
            $query->where(function($q) {
                $q->where('products.sku', 'like', '%' . $this->sku . '%')
                  ->orWhere('product_asins.asin1', 'like', '%' . $this->sku . '%')
                  ->orWhere('pc.child_short_name', 'like', '%' . $this->sku . '%');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = ['SKU', 'ASIN', 'Product Name', 'AFN Quantity', 'FBA Total Stock', 'Inbound Qty'];

        foreach ($this->warehouses as $wh) {
            $headings[] = $wh->warehouse_name;
        }

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $totalColumns = count($this->headings());
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

        // Header styling
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'D9E1F2']],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'A6A6A6'],
                ],
            ],
        ]);

        $sheet->getStyle('A')->getFont()->setBold(true);

        return [];
    }
}
