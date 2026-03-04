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

class salesDailyReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected array $last10Days = [];
    protected array $last10DaysSqlKeys = [];
    protected $allMarketplaceIds;

    public function __construct()
    {
        $marketTz          = config('timezone.market');
        $this->allMarketplaceIds = array_values(config('marketplaces.marketplace_names', []));

        // Build latest-first arrays for headings and SQL
        for ($i = 1; $i < 10; $i++) {
            $date = Carbon::now($marketTz)->subDays($i);
            $this->last10Days[] = $date->format('d M Y'); // Heading: e.g., "17 Nov 2025"
            $this->last10DaysSqlKeys[] = $date->format('Y-m-d'); // SQL key
        }
    }

    public function collection()
    {
        // Build dynamic SQL for daily aggregation
        $datesSelect = [];
        foreach ($this->last10DaysSqlKeys as $date) {
            $datesSelect[] = "COALESCE(ds.`{$date}`, 0) AS `{$date}`";
        }
        $datesSelectSql = implode(", ", $datesSelect);

        // Build subquery for daily sales totals per ASIN
        $dailySubqueryParts = [];
        foreach ($this->last10DaysSqlKeys as $date) {
            $dailySubqueryParts[] =
                "SUM(CASE WHEN DATE(d.sale_date) = '{$date}' THEN d.total_units ELSE 0 END) AS `{$date}`";
        }
        $dailySubquerySql = implode(", ", $dailySubqueryParts);

        // TURN ARRAY INTO SQL LIST: ('Amazon.com','Amazon.ca','Amazon.com.mx')
        $marketplaceList = collect($this->allMarketplaceIds)
            ->map(function ($id) {
                return DB::getPdo()->quote($id);  // adds quotes & escapes safely
            })
            ->implode(', ');
        $query = "
        SELECT
            DISTINCT(pa.asin1) AS ASIN,
            SUBSTRING_INDEX(lcc.catalog_categories, ' -> ', 1) AS `Main Category`,
            '' AS `Brand / Supplier`,
            {$datesSelectSql}
        FROM product_asins pa
        LEFT JOIN (
            SELECT asin, {$dailySubquerySql}
            FROM daily_sales d
            WHERE marketplace_id IN ({$marketplaceList})
              AND d.sale_date >= DATE_SUB(CURDATE(), INTERVAL 10 DAY)
            GROUP BY asin
        ) ds ON pa.asin1 = ds.asin
        LEFT JOIN list_catalog_categories lcc ON pa.asin1 = lcc.asin
        ORDER BY pa.asin1
    ";
        // Optional: dd($query); to inspect the final SQL

        return collect(DB::select($query));
    }



    public function headings(): array
    {
        return array_merge(
            ['ASIN', 'Main Category', 'Brand / Supplier'],
            $this->last10Days
        );
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 20,
            'B' => 30,
            'C' => 25,
        ];

        $colIndex = 4; // Start of daily columns
        foreach ($this->last10Days as $_) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $widths[$letter] = 15;
            $colIndex++;
        }

        return $widths;
    }

    public function columnFormats(): array
    {
        $formats = [];
        $colIndex = 4; // Start of daily columns
        foreach ($this->last10Days as $_) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $formats[$letter] = NumberFormat::FORMAT_NUMBER;
            $colIndex++;
        }

        return $formats;
    }
}
