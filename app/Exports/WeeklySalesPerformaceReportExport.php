<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class WeeklySalesPerformaceReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    protected $warehouses;
    protected $asin;
    protected $marketplaceId;
    protected $country;

    protected array $marketplaceToCountry = [
        'A2EUQ1WTGCTBG2' => 'CA',
        'ATVPDKIKX0DER'  => 'US',
        'A1AM78C64UM0Y8' => 'MX',
    ];

    public function __construct($warehouses, $marketplaceId = 'ATVPDKIKX0DER', $asin = null)
    {
        $this->warehouses    = $warehouses;
        $this->marketplaceId = $marketplaceId;
        $this->asin          = $asin;

        // Auto detect country from map
        $this->country = $this->marketplaceToCountry[$marketplaceId] ?? 'US';
    }

    public function collection()
    {
        $warehouseIds = collect($this->warehouses)->pluck('id')->all();

        /**
         * AFN stock per SKU
         */
        $afnSub = DB::table('afn_inventory_data')
            ->where('quantity_available', '>', 0)
            ->select('seller_sku', DB::raw('SUM(quantity_available) as afn_quantity'))
            ->groupBy('seller_sku');

        /**
         * FBA stock per SKU (USA)
         */
        $fbaSub = DB::table('fba_inventory_usa')
            ->where('totalstock', '>', 0)
            ->select('sku', DB::raw('SUM(totalstock) as fba_total_stock'))
            ->groupBy('sku');

        /**
         * Warehouse stock per ASIN (sum of all selected warehouses)
         */
        $warehouseSub = DB::table('product_wh_inventory as wh')
            ->join('products as p', 'wh.product_id', '=', 'p.id')
            ->join('product_asins as pa', 'p.id', '=', 'pa.product_id')
            ->where('wh.available_quantity', '>', 0)
            ->when(!empty($warehouseIds), function ($q) use ($warehouseIds) {
                $q->whereIn('wh.warehouse_id', $warehouseIds);
            })
            ->select('pa.asin1', DB::raw('SUM(wh.available_quantity) as whs_stock'))
            ->groupBy('pa.asin1');

        /**
         * In-Transit per ASIN (Route Stock)
         */
        $inTransitSub = DB::table('product_asins as pa')
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            ->join('inbound_shipment_details_sps as inbound', 'inbound.sku', '=', 'p.sku')
            ->select(
                'pa.asin1',
                DB::raw(
                    'SUM(GREATEST(COALESCE(inbound.qty_ship,0) - COALESCE(inbound.qty_received,0), 0)) as in_transit_qty'
                )
            )
            ->groupBy('pa.asin1');

        /**
         * Marketplace ASINs (US / CA / MX depending on marketplaceId)
         */
        $marketplaceAsinsSub = DB::table('amazon_sold_price')
            ->where('marketplace_id', $this->marketplaceId)
            ->select('asin')
            ->groupBy('asin');

        /**
         * SP-only ASINs from ads table (primary filter)
         */
        $spAsinsSub = DB::table('amz_ads_products')
            ->select('asin')
            ->groupBy('asin');

        /**
         * Agent names per ASIN (comma separated, distinct)
         */
        $agentSub = DB::table('user_assigned_asins as ua')
            ->join('users as u', 'u.id', '=', 'ua.user_id')
            ->select(
                'ua.asin',
                DB::raw(
                    "GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') as agent_names"
                )
            )
            ->groupBy('ua.asin');

        /**
         * Weekly sales W1 / W2 based on dates
         */
        $today         = Carbon::today();
        $thisWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        $w1StartDate = $thisWeekStart->copy()->subWeek()->toDateString();
        $w2StartDate = $thisWeekStart->copy()->subWeeks(2)->toDateString();

        // W1: last full week
        $w1Sub = DB::table('weekly_sales')
            ->whereDate('sale_date', $w1StartDate)
            ->select(
                'asin',
                DB::raw('SUM(total_units) as w1_sold'),
                DB::raw('SUM(total_cost)  as w1_ads_spend')
            )
            ->groupBy('asin');

        // W2: week before last
        $w2Sub = DB::table('weekly_sales')
            ->whereDate('sale_date', $w2StartDate)
            ->select(
                'asin',
                DB::raw('SUM(total_units) as w2_sold'),
                DB::raw('SUM(total_cost)  as w2_ads_spend')
            )
            ->groupBy('asin');

        /**
         * Main query
         */
        $query = DB::table('product_asins as pa')
            // Filter by marketplace ASINs (US / CA / MX)
            ->joinSub($marketplaceAsinsSub, 'mkt', function ($join) {
                $join->on('mkt.asin', '=', 'pa.asin1');
            })
            // Filter by SP-only ASINs
            ->joinSub($spAsinsSub, 'sp', function ($join) {
                $join->on('sp.asin', '=', 'pa.asin1');
            })
            ->join('products as p', 'pa.product_id', '=', 'p.id')
            // Agent names pre-aggregated
            ->leftJoinSub($agentSub, 'agents', function ($join) {
                $join->on('agents.asin', '=', 'pa.asin1');
            })
            ->leftJoinSub($afnSub, 'afn', function ($join) {
                $join->on('afn.seller_sku', '=', 'p.sku');
            })
            ->leftJoinSub($fbaSub, 'fba', function ($join) {
                $join->on('fba.sku', '=', 'p.sku');
            })
            ->leftJoinSub($warehouseSub, 'whs', function ($join) {
                $join->on('whs.asin1', '=', 'pa.asin1');
            })
            ->leftJoinSub($inTransitSub, 'in_transit', function ($join) {
                $join->on('in_transit.asin1', '=', 'pa.asin1');
            })
            ->leftJoinSub($w1Sub, 'w1', function ($join) {
                $join->on('w1.asin', '=', 'pa.asin1');
            })
            ->leftJoinSub($w2Sub, 'w2', function ($join) {
                $join->on('w2.asin', '=', 'pa.asin1');
            });

        /**
         * Select columns in the exact order of headings:
         *
         * ASIN | Country | Agent Name | Amz Stock | WHs Stock | Route Stock
         * W2 Sold | W1 Sold | Difference
         * W2 Ads Spend (SP only) | W1 Ads Spend (SP only Dollar)
         */
        $query->select([
            'pa.asin1 as asin',
            DB::raw("'" . $this->country . "' as country"),
            DB::raw("COALESCE(agents.agent_names, 'N/A') as agent_name"),
            DB::raw(
                'COALESCE(SUM(afn.afn_quantity),0) + COALESCE(SUM(fba.fba_total_stock),0) as amz_stock'
            ),
            DB::raw('MAX(COALESCE(whs.whs_stock,0)) as whs_stock'),
            DB::raw('MAX(COALESCE(in_transit.in_transit_qty,0)) as route_stock'),
            DB::raw('MAX(COALESCE(w2.w2_sold,0)) as w2_sold'),
            DB::raw('MAX(COALESCE(w1.w1_sold,0)) as w1_sold'),
            DB::raw(
                '(MAX(COALESCE(w1.w1_sold,0)) - MAX(COALESCE(w2.w2_sold,0))) as diff_sold'
            ),
            DB::raw('MAX(COALESCE(w2.w2_ads_spend,0)) as w2_ads_spend'),
            DB::raw('MAX(COALESCE(w1.w1_ads_spend,0)) as w1_ads_spend'),
        ])->groupBy('pa.asin1', 'agents.agent_names');

        // Optional ASIN filter input
        if ($this->asin) {
            $query->where('pa.asin1', 'like', '%' . $this->asin . '%');
        }

        return $query->get();
    }
    public function headings(): array
    {
        // SAME LOGIC you used in collection()
        $today         = Carbon::today();
        $thisWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        // The “start dates” you already rely on
        $w1StartDate = $thisWeekStart->copy()->subWeek();
        $w2StartDate = $thisWeekStart->copy()->subWeeks(2);

        // END dates = +6 days (full ISO week)
        $w1EndDate = $w1StartDate->copy()->addDays(6);
        $w2EndDate = $w2StartDate->copy()->addDays(6);

        // Convert to:  "Nov 10 - Nov 16"
        $w1Range = $w1StartDate->format('M d') . ' - ' . $w1EndDate->format('M d');
        $w2Range = $w2StartDate->format('M d') . ' - ' . $w2EndDate->format('M d');

        return [
            'ASIN',
            'Country',
            'Agent Name',
            'Amz Stock',
            'WHs Stock',
            'Route Stock',
            "W2 Sold ({$w2Range})",
            "W1 Sold ({$w1Range})",
            'Difference',
            'W2 Ads Spend (SP only)',
            'W1 Ads Spend (SP only Dollar)',
        ];
    }


    public function styles(Worksheet $sheet)
    {
        $headers = $this->headings();

        foreach ($headers as $index => $title) {
            $col = Coordinate::stringFromColumnIndex($index + 1);

            if (str_starts_with($title, 'ASIN') || str_starts_with($title, 'Country') || str_starts_with($title, 'Agent Name')) {
                $color = 'D9E1F2'; // blue
            } elseif (str_starts_with($title, 'Amz Stock') || str_starts_with($title, 'WHs Stock') || str_starts_with($title, 'Route Stock')) {
                $color = 'E4DFEC'; // light purple
            } elseif (str_starts_with($title, 'W2 Sold')) {
                $color = 'FFF2CC'; // light yellow
            } elseif (str_starts_with($title, 'W1 Sold')) {
                $color = 'C6EFCE'; // light green
            } elseif (str_starts_with($title, 'Difference')) {
                $color = 'FCE4D6'; // light orange
            } elseif (str_starts_with($title, 'W2 Ads Spend') || str_starts_with($title, 'W1 Ads Spend')) {
                $color = 'D9E1F2'; // blue-ish
            } else {
                $color = 'D9E1F2';
            }

            $sheet->getStyle("{$col}1")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'fill'      => [
                    'fillType' => Fill::FILL_SOLID,
                    'color'    => ['rgb' => $color],
                ],
                'borders'   => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['rgb' => 'A6A6A6'],
                    ],
                ],
            ]);
        }

        return [];
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $headings = $this->headings();

                // Find "Difference" column index dynamically
                $diffColIdx = array_search('Difference', $headings, true) + 1; // 1-based
                $diffCol    = Coordinate::stringFromColumnIndex($diffColIdx);

                // Data range for Difference (row 2..last row)
                $highestRow = $sheet->getHighestRow();
                if ($highestRow < 2) {
                    return;
                }

                $cellRange = "{$diffCol}2:{$diffCol}{$highestRow}";

                // Positive (GREEN BG, WHITE TEXT)
                $positive = new Conditional();
                $positive->setConditionType(Conditional::CONDITION_CELLIS);
                $positive->setOperatorType(Conditional::OPERATOR_GREATERTHAN);
                $positive->addCondition('0');
                $positive->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('00E500'); // green
                $positive->getStyle()->getFont()
                    ->getColor()->setARGB('FFFFFF'); // white text

                // Negative (RED BG, WHITE TEXT)
                $negative = new Conditional();
                $negative->setConditionType(Conditional::CONDITION_CELLIS);
                $negative->setOperatorType(Conditional::OPERATOR_LESSTHAN);
                $negative->addCondition('0');
                $negative->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E50000'); // red
                $negative->getStyle()->getFont()
                    ->getColor()->setARGB('FFFFFF'); // white text

                $conditionalStyles   = $sheet->getStyle($cellRange)->getConditionalStyles();
                $conditionalStyles[] = $positive;
                $conditionalStyles[] = $negative;

                $sheet->getStyle($cellRange)->setConditionalStyles($conditionalStyles);
            },
        ];
    }
}
