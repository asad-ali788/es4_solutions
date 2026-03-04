<?php

namespace App\Exports;

use App\Models\AmzAdsProductPerformanceReport;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdsPerformanceSummaryByAsinExport implements
    FromQuery,
    WithChunkReading,
    WithMapping,
    WithColumnWidths,
    WithHeadings,
    WithStyles
{
    protected string $country;

    public function __construct(string $country = 'US')
    {
        $this->country = $country;
    }

    public function query(): Builder
    {
        // Last 30 days from today, using c_date column
        $fromDate = now()->subDays(30)->toDateString();

        return AmzAdsProductPerformanceReport::query()
            ->selectRaw("
                asin,
                GROUP_CONCAT(DISTINCT sku) AS skus,
                SUM(clicks)        AS total_clicks,
                SUM(cost)          AS total_spend,
                SUM(purchases7d)   AS total_orders,
                SUM(sales7d)       AS total_sales
            ")
            ->where('country', $this->country)
            ->where('c_date', '>=', $fromDate)
            ->groupBy('asin')
            ->orderByDesc('total_sales');
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($row): array
    {
        $acos = $row->total_sales > 0
            ? ($row->total_spend / $row->total_sales) * 100
            : 0;

        return [
            $row->asin,
            $row->skus,
            $row->total_clicks,
            $row->total_spend,
            $row->total_orders,
            $row->total_sales,
            round($acos, 2) . '%',
        ];
    }

    public function headings(): array
    {
        return [
            'ASIN',
            'Product Name',
            'Clicks',
            'Spend ($)',
            'Orders',
            'Sales ($)',
            'ACoS (%)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 30,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color'    => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }
}
