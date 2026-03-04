<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Warehouse;
use Carbon\Carbon;

class StockRunDownReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $warehouses;

    public function __construct()
    {
        $this->warehouses = Warehouse::all();
    }

    /**
     * Main data collection for Excel export.
     *
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $today = now()->toDateString();
        $fourWeeksAgo = now()->subDays(28)->toDateString();
        $thirtyDaysAgo = now()->subDays(30)->toDateString();

        $warehouses = Warehouse::whereIn('warehouse_name', ['ShipOut', 'Tactical', 'AWD'])->get();
        $warehouseIds = $warehouses->pluck('id')->all();

        // --- AFN per SKU ---
        $afnSub = DB::table('afn_inventory_data')
            ->where('quantity_available', '>', 0)
            ->select('seller_sku', DB::raw('SUM(quantity_available) as afn_quantity'))
            ->groupBy('seller_sku');

        // --- Inbound per SKU ---
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

        // --- Inbound per ASIN ---
        $inboundAsinSub = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->joinSub($inboundSkuSub, 'inbound', 'inbound.sku', '=', 'p.sku')
            ->select('pa.asin1', DB::raw('SUM(inbound.inbound_qty_per_sku) as inbound_qty'))
            ->groupBy('pa.asin1');

        // --- In-Transit per ASIN ---
        $inTransitSub = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->join('inbound_shipment_details_sps as inbound', 'inbound.sku', '=', 'p.sku')
            ->select('pa.asin1', DB::raw('SUM(GREATEST(COALESCE(inbound.qty_ship,0) - COALESCE(inbound.qty_received,0), 0)) as in_transit_qty'))
            ->groupBy('pa.asin1');

        // --- Weekly Sales (Last 4 Weeks) ---
        $weeklySalesSub = DB::table('weekly_sales')
            ->whereBetween('sale_date', [$fourWeeksAgo, $today])
            ->select('asin', DB::raw('SUM(total_units) as last_4_week_sold'))
            ->groupBy('asin');

        // --- Last 30 Days Sales ---
        $last30DaysSales = DB::table('daily_sales')
            ->whereBetween('sale_date', [$thirtyDaysAgo, $today])
            ->select('asin', DB::raw('SUM(total_units) as total_units'))
            ->groupBy('asin')
            ->pluck('total_units', 'asin');

        // --- Last Sale Date ---
        $lastSales = DB::table('daily_sales')
            ->select('asin', DB::raw('MAX(sale_date) as last_sale_date'))
            ->groupBy('asin')
            ->pluck('last_sale_date', 'asin');

        // --- Warehouse Stock (All warehouses in single query) ---
        $warehouseStockAll = DB::table('product_wh_inventory as wh')
            ->join('products as p', 'wh.product_id', '=', 'p.id')
            ->join('product_asins as pa', 'p.id', '=', 'pa.product_id')
            ->whereIn('wh.warehouse_id', $warehouseIds)
            ->where('wh.available_quantity', '>', 0)
            ->select('pa.asin1', 'wh.warehouse_id', DB::raw('SUM(wh.available_quantity) as total'))
            ->groupBy('pa.asin1', 'wh.warehouse_id')
            ->get()
            ->groupBy('asin1');

        // --- Main Query ---
        $query = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->leftJoinSub($afnSub, 'afn', fn($join) => $join->on('afn.seller_sku', '=', 'p.sku'))
            ->leftJoinSub($inboundAsinSub, 'inbound', fn($join) => $join->on('inbound.asin1', '=', 'pa.asin1'))
            ->leftJoinSub($inTransitSub, 'transit', fn($join) => $join->on('transit.asin1', '=', 'pa.asin1'))
            ->leftJoin('list_catalog_categories as lcc', 'lcc.asin', '=', 'pa.asin1')
            ->leftJoin('user_assigned_asins as uaa', 'uaa.asin', '=', 'pa.asin1')
            ->leftJoin('users as u', 'u.id', '=', 'uaa.user_id')
            ->leftJoinSub($weeklySalesSub, 'ws', 'ws.asin', '=', 'pa.asin1')
            // ->where('pa.asin1', 'B01KIFISX2')    //Testing Asin
            ->select([
                'pa.asin1',
                DB::raw("'' as item_name"),
                DB::raw('MAX(lcc.catalog_categories) as category'),
                DB::raw('MAX(u.name) as agent'),
                DB::raw('p.status as product_status'),
                DB::raw('SUM(COALESCE(afn.afn_quantity,0)) as amz_stock'),
                DB::raw('MAX(COALESCE(inbound.inbound_qty,0)) as inbound_stock'),
                DB::raw('MAX(COALESCE(transit.in_transit_qty,0)) as in_transit_stock'),
                DB::raw('MAX(COALESCE(ws.last_4_week_sold,0)) as last_4_week_sold'),
            ])
            ->groupBy('pa.asin1', 'p.status');

        $rows = $query->get();
        $futureMonths = $this->getFutureMonths(3);

        // --- Post Processing ---
        $results = $rows->map(function ($row) use ($last30DaysSales, $futureMonths, $warehouses, $warehouseStockAll, $lastSales) {

            $asin = $row->asin1;

            // --- Total Warehouse Stock ---
            $warehouseStock = collect($warehouseStockAll[$asin] ?? [])->sum('total');

            // --- Total Stock ---
            $totalStock = $row->amz_stock + $warehouseStock + $row->inbound_stock + $row->in_transit_stock;
            $soldLast30 = $last30DaysSales[$asin] ?? 0;
            $avgDaily = $row->last_4_week_sold / 28;
            $daysLeft = $avgDaily > 0 ? round($totalStock / $avgDaily, 2) : 0;
            $stockEndDate = $daysLeft > 0 ? now()->addDays(ceil($daysLeft))->toDateString() : null;

            // --- Product Status ---
            $lastSale = $lastSales[$asin] ?? null;
            if (!$lastSale) {
                $productStatus = 'New';
            } else {
                $daysSinceLastSale = Carbon::parse($lastSale)->diffInDays(now());

                if ($daysSinceLastSale > 365) {
                    $productStatus = 'Normal (Under one year)'; // more than a year since last sale
                } else {
                    $productStatus = 'Normal'; // less than a year
                }
            }

            // --- Split Category ---
            $categories = array_filter(array_map('trim', explode('->', $row->category ?? '')));
            $parentCategory = $categories[0] ?? '';
            $childCategory = end($categories) ?? '';

            // --- Stock Usage ---
            $previousStock = $totalStock;
            $stockUsage = [];
            foreach ($futureMonths as $month) {
                $newStock = max($previousStock - $soldLast30, 0);
                $stockUsage['stock_usage_' . str_replace(' ', '_', $month)] =
                    ($previousStock || $soldLast30) ? "{$previousStock}-{$soldLast30}={$newStock}" : '';
                $previousStock = $newStock;
            }

            return array_merge([
                'asin'               => $asin,
                'item_name'          => $row->item_name,
                'parent_category'    => $parentCategory,
                'child_category'     => $childCategory,
                'agent'              => $row->agent,
                'product_status'     => $productStatus,
                'amz_stock'          => $row->amz_stock,
                'warehouse_stock'    => $warehouseStock,
                'inbound_stock'      => $row->inbound_stock,
                'stock_on_order'     => 0,
                'last_4_week_sold'   => $row->last_4_week_sold,
                'avg_daily_sales'    => round($avgDaily, 2),
                'days_of_stock_left' => $daysLeft,
                'stock_end_date'     => $stockEndDate ? Carbon::parse($stockEndDate)->format('jS M Y') : null,
            ], $stockUsage);
        });

        return $results;
    }


    /**
     * @param int $numMonths
     * @return array
     */
    protected function getFutureMonths($numMonths = 3)
    {
        $months = [];
        $current = now()->startOfMonth();
        for ($i = 0; $i < $numMonths; $i++) {
            $months[] = $current->format('M Y');
            $current->addMonth();
        }
        return $months;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = [
            'ASIN',
            'Item Name',
            'Parent Category',
            'Child Category',
            'Agent',
            'Product Status',
            'Amz Stock',
            'Warehouse Stock',
            'Inbound Stock',
            'Stock on Order',
            'Last 4 Week Sold',
            'Avg Daily Sales',
            'Days of Stock Left',
            'Stock End Date',
        ];


        foreach ($this->getFutureMonths(3) as $month) {
            $headings[] = 'Stock Usage ' . $month;
        }

        return $headings;
    }

    /**
     * @param Worksheet $sheet
     * @return void
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // ASIN
            'B' => 30,  // Item Name
            'C' => 50,  // Parent Category
            'D' => 20,  // Child Category
            'E' => 25,  // Agent
            'F' => 15,  // Product Status
            'G' => 15,  // Amz Stock
            'H' => 15,  // Warehouse Stock
            'I' => 15,  // Inbound Stock
            'J' => 20,  // Stock on Order
            'K' => 20,  // Last 4 Week Sold
            'L' => 15,  // Avg Daily Sales
            'M' => 15,  // Days of Stock Left
            'N' => 15,  // Stock End Date
            'O' => 25,  // Stock Usage Month 1
            'P' => 25,  // Stock Usage Month 2
            'Q' => 25,  // Stock Usage Month 3
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        $formats = [
            'F' => NumberFormat::FORMAT_TEXT,  // Product Status
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];

        $col = 'O';
        foreach ($this->getFutureMonths(3) as $month) {
            $formats[$col] = NumberFormat::FORMAT_NUMBER;
            $col++;
        }

        return $formats;
    }
}
