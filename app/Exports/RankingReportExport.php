<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class RankingReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithColumnFormatting
{
    protected array $dates;
    protected string $label_two_days_ago;
    protected string $label_three_days_ago;

    public function __construct()
    {
        $marketTz = config('timezone.market');

        $now = now($marketTz);

        $this->dates = [
            'today'           => $now->toDateString(),
            'yesterday'       => $now->copy()->subDay()->toDateString(),
            'd7'              => $now->copy()->subDays(7)->toDateString(),
            'd14'             => $now->copy()->subDays(14)->toDateString(),
            'd30'             => $now->copy()->subDays(30)->toDateString(),
            'two_days_ago'    => $now->copy()->subDays(2)->toDateString(),
            'three_days_ago'  => $now->copy()->subDays(3)->toDateString(),
        ];

        $this->label_two_days_ago   = Carbon::parse($this->dates['two_days_ago'])->format('d M Y');
        $this->label_three_days_ago = Carbon::parse($this->dates['three_days_ago'])->format('d M Y');
    }

    public function collection(): Collection
    {
        // 1. Product metadata
        $products = DB::table('product_asins as pa')
            ->join('amazon_sold_price as asp', 'pa.asin1', '=', 'asp.asin')
            ->leftJoin('list_catalog_categories as lcc', 'pa.asin1', '=', 'lcc.asin')
            ->select(
                'pa.product_id',
                'pa.asin1',
                'asp.marketplace_id',
                'lcc.catalog_categories'
            )
            ->where('asp.marketplace_id', 'ATVPDKIKX0DER')
            ->distinct()
            ->get();

        // 2. Rankings
        // $rankings = DB::table('product_rankings')
        //     ->select('product_id', 'date', 'rank')
        //     ->whereIn('date', $this->dates)
        //     ->get()
        //     ->groupBy('product_id');

        // 2. Rankings + prices
        $rankings = DB::table('product_rankings')
            ->select('product_id', 'date', 'rank', 'current_price')
            ->whereIn('date', $this->dates)
            ->get()
            ->groupBy('product_id');


        // 3. Sales (bulk aggregated per ASIN)
        // 3. Sales (bulk aggregated per ASIN) using $this->dates
        $sales = DB::table('daily_sales as ms')
            ->select(
                'ms.asin',

                // units
                DB::raw("SUM(CASE WHEN sale_date >= '{$this->dates['d7']}' THEN total_units END) AS units_7d"),
                DB::raw("SUM(CASE WHEN sale_date >= '{$this->dates['d30']}' THEN total_units END) AS units_30d"),

                // specific day units
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['yesterday']}' THEN total_units END) AS sale_yesterday"),
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['two_days_ago']}' THEN total_units END) AS sale_two_days_ago"),
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['three_days_ago']}' THEN total_units END) AS sale_three_days_ago"),

                // specific day prices
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['today']}' THEN total_cost END) AS price_today"),
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['yesterday']}' THEN total_cost END) AS price_yesterday"),
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['two_days_ago']}' THEN total_cost END) AS price_two_days_ago"),
                DB::raw("MAX(CASE WHEN sale_date = '{$this->dates['three_days_ago']}' THEN total_cost END) AS price_three_days_ago")
            )
            ->whereIn('sale_date', [
                $this->dates['today'],
                $this->dates['yesterday'],
                $this->dates['two_days_ago'],
                $this->dates['three_days_ago']
            ])
            ->groupBy('ms.asin')
            ->get()
            ->keyBy('asin');

        // 4. Merge
        $result = $products->map(function ($row) use ($rankings, $sales) {

            $snapshots = $rankings[$row->product_id] ?? collect();

            $bsr_today     = optional($snapshots->firstWhere('date', $this->dates['today']))->rank;
            $bsr_yesterday = optional($snapshots->firstWhere('date', $this->dates['yesterday']))->rank;
            $bsr_two_days     = optional($snapshots->firstWhere('date', $this->dates['two_days_ago']))->rank;
            $bsr_three_days   = optional($snapshots->firstWhere('date', $this->dates['three_days_ago']))->rank;
            $bsr_7         = optional($snapshots->firstWhere('date', $this->dates['d7']))->rank;
            $bsr_14        = optional($snapshots->firstWhere('date', $this->dates['d14']))->rank;
            $bsr_30        = optional($snapshots->firstWhere('date', $this->dates['d30']))->rank;

            $bsr_change7   = ($bsr_today !== null && $bsr_7 !== null)  ? $bsr_7 - $bsr_today  : 0;
            $bsr_change30  = ($bsr_today !== null && $bsr_30 !== null) ? $bsr_30 - $bsr_today : 0;

            $ms = $sales[$row->asin1] ?? null;

            $units_7d        = $ms->units_7d ?? 0;
            $units_30d       = $ms->units_30d ?? 0;
            $sale_yesterday      = $ms->sale_yesterday ?? 0;
            $sale_two_days_ago   = $ms->sale_two_days_ago ?? 0;
            $sale_three_days_ago = $ms->sale_three_days_ago ?? 0;

            // $price_today         = $ms->price_today ?? 0;
            // $price_yesterday     = $ms->price_yesterday ?? 0;
            // $price_two_days_ago  = $ms->price_two_days_ago ?? 0;
            // $price_three_days_ago = $ms->price_three_days_ago ?? 0;

            // $price_today         = optional($snapshots->firstWhere('date', $this->dates['today']))->current_price ?? 0;
            $price_today = DB::table('amazon_sold_price')
                ->where('asin', $row->asin1)
                ->where('marketplace_id', $row->marketplace_id)
                ->value('listing_price');

            $price_yesterday     = optional($snapshots->firstWhere('date', $this->dates['yesterday']))->current_price ?? 0;
            $price_two_days_ago  = optional($snapshots->firstWhere('date', $this->dates['two_days_ago']))->current_price ?? 0;
            $price_three_days_ago = optional($snapshots->firstWhere('date', $this->dates['three_days_ago']))->current_price ?? 0;


            $categoryParts = explode(' -> ', $row->catalog_categories ?? '');
            $parent = $categoryParts[0] ?? '';
            $child  = end($categoryParts) ?: '';

            return [
                'ASIN'              => $row->asin1,
                'Marketplace'       => $this->getMarketplaceCode($row->marketplace_id),
                'Parent Category'   => $parent,
                'Child Category'    => $child,

                'BSR Today'         => $bsr_today,
                'BSR 7 Days Ago'    => $bsr_7,
                'BSR 14 Days Ago'   => $bsr_14,
                'BSR 30 Days Ago'   => $bsr_30,

                'BSR Change (7D)'   => $bsr_change7,
                'BSR Change (30D)'  => $bsr_change30,

                'Units Sold 7D'     => $units_7d,
                'Units Sold 30D'    => $units_30d,

                'Price Today'       => $price_today,

                'Review Count'      => '',
                'Review Rating'     => '',

                'BSR Yesterday'     => $bsr_yesterday,
                'Sale Yesterday'    => $sale_yesterday,
                'Price Yesterday'   => $price_yesterday,

                'BSR ' . $this->label_two_days_ago   => $bsr_two_days,
                'Sale ' . $this->label_two_days_ago  => $sale_two_days_ago,
                'Price ' . $this->label_two_days_ago => $price_two_days_ago,

                'BSR ' . $this->label_three_days_ago   => $bsr_three_days,
                'Sale ' . $this->label_three_days_ago  => $sale_three_days_ago,
                'Price ' . $this->label_three_days_ago => $price_three_days_ago,
            ];
        });


        return $result;
    }

    protected function getMarketplaceCode(string $marketplaceId): string
    {
        $mapping = config('marketplaces.marketplace_ids');
        $reverse = array_flip($mapping);

        return $reverse[$marketplaceId] ?? $marketplaceId;
    }


    public function headings(): array
    {
        return [
            'ASIN',
            'Marketplace',
            'Parent Category',
            'Child Category',

            'BSR Today',
            'BSR 7 Days Ago',
            'BSR 14 Days Ago',
            'BSR 30 Days Ago',

            'BSR Change (7D)',
            'BSR Change (30D)',

            'Units Sold 7D',
            'Units Sold 30D',

            'Price Today',

            'Review Count',
            'Review Rating',

            'BSR Yesterday',
            'Sale Yesterday',
            'Price Yesterday',

            'BSR ' . $this->label_two_days_ago,
            'Sale ' . $this->label_two_days_ago,
            'Price ' . $this->label_two_days_ago,

            'BSR ' . $this->label_three_days_ago,
            'Sale ' . $this->label_three_days_ago,
            'Price ' . $this->label_three_days_ago,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header row number
        $row = 1;

        // Color definitions
        $c1   = 'bff0a1';
        $c2   = 'c1daf5';
        $c3    = 'd4c2f0';
        $c4    = 'd9d18f';
        $c5    = 'de5990';
        $c6    = 'c324e3';

        // Map header labels to styling
        $headerStyles = [
            // BSR Today, BSR 7, 14, 30 Days
            'E' => $c1,
            'F' => $c1,
            'G' => $c1,
            'H' => $c1,

            // BSR Change (7D), BSR Change (30D)
            'I' => $c2,
            'J' => $c2,

            // Units Sold 7D, Units Sold 30D
            'K' => $c3,
            'L' => $c3,

            // Yesterday block (BSR, Sale, Price)
            'P' => $c4,
            'Q' => $c4,
            'R' => $c4,

            'S' => $c5,
            'T' => $c5,
            'U' => $c5,

            'V' => $c6,
            'W' => $c6,
            'X' => $c6,
        ];

        // Apply fill for each column header
        foreach ($headerStyles as $column => $color) {
            $sheet->getStyle($column . $row)->applyFromArray([
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['rgb' => $color]
                ],
                'font' => [
                    'bold' => true
                ]
            ]);
        }

        return [];
    }


    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 30,
            'D' => 30,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 18,
            'J' => 18,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 17,
            'O' => 15,
            'P' => 15,
            'Q' => 15,
            'R' => 15,
            'S' => 15,
            'T' => 15,
            'u' => 17,
            'v' => 17,
            'w' => 17,
            'x' => 17,
            'y' => 17,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E'  => NumberFormat::FORMAT_NUMBER,
            'F'  => NumberFormat::FORMAT_NUMBER,
            'G'  => NumberFormat::FORMAT_NUMBER,
            'H'  => NumberFormat::FORMAT_NUMBER,
            'I'  => NumberFormat::FORMAT_NUMBER,
            'J'  => NumberFormat::FORMAT_NUMBER,
            'K'  => NumberFormat::FORMAT_NUMBER,
            'L'  => NumberFormat::FORMAT_NUMBER,
            'M'  => NumberFormat::FORMAT_NUMBER_00,
            'N'  => NumberFormat::FORMAT_NUMBER,
            'O'  => NumberFormat::FORMAT_NUMBER,
            'P'  => NumberFormat::FORMAT_NUMBER,
            'Q'  => NumberFormat::FORMAT_NUMBER,
            'R'  => NumberFormat::FORMAT_NUMBER_00,
            'S'  => NumberFormat::FORMAT_NUMBER,
            'T'  => NumberFormat::FORMAT_NUMBER,
            'U'  => NumberFormat::FORMAT_NUMBER_00,
            'X'  => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
