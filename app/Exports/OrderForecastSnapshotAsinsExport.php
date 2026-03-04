<?php

namespace App\Exports;

use App\Models\OrderForecastSnapshotAsins;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class OrderForecastSnapshotAsinsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnWidths,
    WithTitle,
    WithStyles,
    WithColumnFormatting
{
    private int $forecastId;
    private array $headers = [];
    private array $months12 = [];
    private array $last3Months = [];

    public function __construct(int $forecastId)
    {
        $this->forecastId = $forecastId;

        $first = OrderForecastSnapshotAsins::where('order_forecast_id', $forecastId)->first();
        $row   = $first->toArray();

        /** -------------------------
         * Base headers
         * -------------------------
         */
        $this->headers = [
            'ASIN',
            'Price',
            'Country',
            'Amazon Stock',
            'Warehouse Stock',
            'Route: In Transit',
            'Shipment In Transit',
            'YTD',
        ];

        /** -------------------------
         * Sales last 3 months (Oct–Nov–Dec 2025)
         * -------------------------
         */
        $firstForecastMonth = $row['sales_by_month_last_12_months'][0]['key']; // 2026-01

        $this->last3Months = [
            Carbon::parse($firstForecastMonth)->subMonths(3)->format('Y-m'),
            Carbon::parse($firstForecastMonth)->subMonths(2)->format('Y-m'),
            Carbon::parse($firstForecastMonth)->subMonths(1)->format('Y-m'),
        ];

        foreach ($this->last3Months as $month) {
            $this->headers[] = "Sales 3M: {$month}";
        }

        /** -------------------------
         * 12 months forecast headers
         * -------------------------
         */
        foreach ($row['sales_by_month_last_12_months'] as $m) {
            $label = $m['label'];
            $key   = $m['key'];

            $this->months12[] = $key;

            $this->headers[] = "Sales: {$label}";
            $this->headers[] = "SYS FC: {$label}";
            // $this->headers[] = "AI FC: {$label}";
            // $this->headers[] = "Sales Input: {$label}";
        }

        /** -------------------------
         * Ending column
         * -------------------------
         */
        $this->headers[] = 'Order Amount';
    }

    public function collection()
    {
        return OrderForecastSnapshotAsins::where('order_forecast_id', $this->forecastId)->get();
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function map($row): array
    {
        $data = [];

        /** -------------------------
         * Fixed columns
         * -------------------------
         */
        $data[] = $row->product_asin;
        $data[] = $row->product_price;
        $data[] = $row->country;
        $data[] = $row->amazon_stock;
        $data[] = $row->warehouse_stock;
        $data[] = $row->routes['in_transit'] ?? 0;
        $data[] = count($row->shipment_in_transit ?? []);
        $data[] = $row->ytd_sales;

        /** -------------------------
         * Sales 3M (aligned with headers)
         * -------------------------
         */
        $sales3M = $row->sales_by_month_last_3_months ?? [];

        foreach ($this->last3Months as $month) {
            $data[] = $sales3M[$month] ?? 0;
        }

        /** -------------------------
         * AI forecast lookup
         * -------------------------
         */
        // $aiForecast = collect(
        //     $row->ai_recommendation_data_by_month_12_months['forecast'] ?? []
        // )->keyBy('month');

        // $salesInput = $row->sold_values_by_month ?? [];

        /** -------------------------
         * 12 months mapping
         * -------------------------
         */
        foreach ($row->sales_by_month_last_12_months ?? [] as $m) {
            $key = $m['key'];

            $data[] = $m['sold'] ?? 0;                 // Sales
            $data[] = $m['sys_sold'] ?? 0;             // SYS FC
            // $data[] = $aiForecast[$key]['ai'] ?? 0;    // AI FC
            // $data[] = $salesInput[$key] ?? null;       // Sales Input
        }

        /** -------------------------
         * Ending column
         * -------------------------
         */
        $data[] = $row->order_amount ?? 0;

        return $data;
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach (range('A', 'Z') as $c) $widths[$c] = 18;
        foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'] as $c)
            $widths[$c] = 18;
        foreach (['BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH'] as $c)
            $widths[$c] = 18;

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        $highestColumn = $sheet->getHighestColumn();

        $styles["A1:{$highestColumn}1"] = [
            'font' => ['bold' => true],
            'fill' => $this->fill('#E7E6E6'),
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
            'borders' => ['bottom' => ['borderStyle' => 'thin']],
        ];

        foreach ($this->headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);

            if (str_starts_with($header, 'Sales:'))       $styles[$column] = ['fill' => $this->fill('#C6EFCE')];
            if (str_starts_with($header, 'SYS FC:'))      $styles[$column] = ['fill' => $this->fill('#DDEBF7')];
            // if (str_starts_with($header, 'AI FC:'))       $styles[$column] = ['fill' => $this->fill('#FFF2CC')];
            // if (str_starts_with($header, 'Sales Input:')) $styles[$column] = ['fill' => $this->fill('#FCE4D6')];
        }

        return $styles;
    }

    private function fill(string $hex): array
    {
        return [
            'fillType' => 'solid',
            'startColor' => ['argb' => 'FF' . strtoupper(ltrim($hex, '#'))],
        ];
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);

            if ($header === 'Price') {
                $formats[$column] = NumberFormat::FORMAT_NUMBER_00;
            } elseif (str_starts_with($header, 'Sales')) {
                $formats[$column] = NumberFormat::FORMAT_NUMBER;
            } else {
                $formats[$column] = NumberFormat::FORMAT_NUMBER;
            }
        }

        return $formats;
    }

    public function title(): string
    {
        return 'ASIN Forecast';
    }
}
