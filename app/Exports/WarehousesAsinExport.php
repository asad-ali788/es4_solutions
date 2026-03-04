<?php

namespace App\Exports;

use App\Models\Warehouse;
use App\Models\ProductWhInventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WarehousesAsinExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected Collection $warehouses;
    protected array $rows = [];

    public function __construct()
    {
        // preload all warehouses
        $this->warehouses = Warehouse::all();
    }

    public function headings(): array
    {
        $names = $this->warehouses->pluck('warehouse_name')->toArray();
        return array_merge(['ASIN'], $names);
    }

    public function collection()
    {
        // load all inventories with product + asins
        $inventories = ProductWhInventory::with(['product.asins'])
            ->get();

        // group all inventories by asin1
        $grouped = $inventories->groupBy(function ($inv) {
            return $inv->product?->asins?->asin1 ?? 'UNKNOWN';
        });

        $rows = [];
        $wareCount = $this->warehouses->count();
        $totals = array_fill(0, $wareCount, 0.0);

        foreach ($grouped as $asin => $asinInventories) {
            $row = [$asin];
            $i = 0;

            foreach ($this->warehouses as $warehouse) {
                // sum all products’ stocks in this warehouse for this ASIN
                $sum = $asinInventories
                    ->where('warehouse_id', $warehouse->id)
                    ->sum(fn($inv) => $inv->available_quantity ?? $inv->quantity ?? 0);

                $row[] = $sum;
                $totals[$i] += $sum;
                $i++;
            }

            $rows[] = $row;
        }

        // append totals row
        $totalRow = ['Total'];
        foreach ($totals as $t) {
            $totalRow[] = $t;
        }
        $rows[] = $totalRow;

        $this->rows = $rows;
        return new Collection($rows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalCols = $this->warehouses->count() + 1; // 1 for ASIN col
                $lastCol = $this->numToColumnLetter($totalCols);
                $lastRow = count($this->rows) + 1; // include header

                // style header
                $headerStyle = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'D9E1F2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);

                // bold totals row
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")
                    ->getFont()->setBold(true);

                // format warehouse numbers
                if ($this->warehouses->count() > 0) {
                    $firstNumCol = $this->numToColumnLetter(2); // B
                    $sheet->getStyle("{$firstNumCol}2:{$lastCol}{$lastRow}")
                        ->getNumberFormat()->setFormatCode('#,##0');
                }
            },
        ];
    }

    protected function numToColumnLetter(int $num): string
    {
        $letters = '';
        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $num = (int) floor(($num - $mod) / 26);
        }
        return $letters;
    }
}
