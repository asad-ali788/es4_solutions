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
use Carbon\Carbon;

class salesMonthlyReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected array $monthsFormatted = [];
    protected array $monthsKeys = [];
    protected array $allMarketplaceIds = [];

    public function __construct()
    {
        $marketTz = config('timezone.market');
        $this->allMarketplaceIds = array_values(config('marketplaces.marketplace_names', []));

        // Last 12 completed months - latest first
        // 1 month ago, 2 months ago, ..., 12 months ago
        for ($i = 1; $i <= 12; $i++) {
            $monthDate = Carbon::now($marketTz)->subMonths($i);

            // Excel heading: "Nov 2025 Sold"
            $this->monthsFormatted[] = $monthDate->format('M Y') . ' Sold';

            // SQL key: "2025-11"
            $this->monthsKeys[] = $monthDate->format('Y-m');
        }
    }

    public function collection()
    {
        // Build dynamic SUM(CASE ...) per month (pivot columns)
        $monthSelects = [];
        foreach ($this->monthsKeys as $key) {
            $monthSelects[] =
                "COALESCE(SUM(CASE WHEN DATE_FORMAT(m.sale_date, '%Y-%m') = '{$key}' THEN m.total_units ELSE 0 END), 0) AS `{$key}`";
        }
        $monthSelectSql = implode(",\n                ", $monthSelects);

        // TURN ARRAY INTO SQL LIST: ('Amazon.com','Amazon.ca','Amazon.com.mx')
        $marketplaceList = collect($this->allMarketplaceIds)
            ->map(function ($id) {
                return DB::getPdo()->quote($id); // adds quotes & escapes safely
            })
            ->implode(', ');

        $query = "
            SELECT
                a.asin AS `ASIN`,
                SUBSTRING_INDEX(lcc.catalog_categories, ' -> ', 1) AS `Parent Category`,
                '' AS `Brand / Supplier`,

                -- Avg Daily Sales (Last 4 weeks)
                COALESCE(
                    ROUND(
                        SUM(
                            CASE 
                                WHEN m.sale_date >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) 
                                THEN m.total_units 
                                ELSE 0 
                            END
                        ) / 4,
                        2
                    ),
                    0
                ) AS `Avg Daily Sales (Last 4W)`,

                {$monthSelectSql}
            FROM (
                -- DISTINCT ASIN list to avoid double counting from product_asins duplicates
                SELECT DISTINCT asin1 AS asin
                FROM product_asins
            ) a
            LEFT JOIN monthly_sales m 
                ON a.asin = m.asin
               AND m.marketplace_id IN ({$marketplaceList})
               AND m.sale_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            LEFT JOIN list_catalog_categories lcc 
                ON a.asin = lcc.asin
            GROUP BY 
                a.asin,
                `Parent Category`
            ORDER BY a.asin
        ";

        // Debug if needed:
        // dd($query);

        return collect(DB::select($query));
    }

    public function headings(): array
    {
        return array_merge(
            ['ASIN', 'Parent Category', 'Brand / Supplier', 'Avg Daily Sales (Last 4W)'],
            $this->monthsFormatted
        );
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Bold header row
        ];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 20, // ASIN
            'B' => 30, // Parent Category
            'C' => 25, // Brand / Supplier
            'D' => 25, // Avg Daily Sales (Last 4W)
        ];

        // Monthly columns start at column E (index 5)
        $colIndex = 5;
        foreach ($this->monthsFormatted as $_) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $widths[$letter] = 15;
            $colIndex++;
        }

        return $widths;
    }

    public function columnFormats(): array
    {
        $formats = [
            'D' => NumberFormat::FORMAT_NUMBER_00, // Avg Daily Sales (Last 4W)
        ];

        // Monthly columns start at column E (index 5)
        $colIndex = 5;
        foreach ($this->monthsFormatted as $_) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $formats[$letter] = NumberFormat::FORMAT_NUMBER;
            $colIndex++;
        }

        return $formats;
    }
}
