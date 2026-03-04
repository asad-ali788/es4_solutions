<?php

namespace App\Exports;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WarehouseStockExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected ?array $warehouseIds;
    protected Collection $warehouses;
    protected array $rows = [];
    protected Collection $allInventories; // sku => product

    /**
     * @param array|null $warehouseIds Optional array of warehouse IDs to export. If null => all warehouses.
     */
    public function __construct(array $warehouseIds = null)
    {
        $this->warehouseIds = $warehouseIds;
        $this->warehouses = $this->getWarehouses();
        $this->allInventories = collect();
    }

    protected function getWarehouses(): Collection
    {
        $query = Warehouse::with(['inventories.product'])->latest();
        if (!empty($this->warehouseIds)) {
            $query->whereIn('id', $this->warehouseIds);
        }
        return $query->get();
    }

    /**
     * Headings: SKU, FNSKU + dynamic warehouse names
     */
    public function headings(): array
    {
        $names = $this->warehouses->map(fn($w) => $w->warehouse_name)->toArray();
        return array_merge(['SKU', 'FNSKU'], $names);
    }

    /**
     * Build rows: [sku, fnsku, qty_for_warehouse1, qty_for_warehouse2, ...]
     */
    public function collection()
    {
        // gather unique SKUs and keep product reference
        $allInventories = collect();
        foreach ($this->warehouses as $warehouse) {
            foreach ($warehouse->inventories as $inventory) {
                $sku = $inventory->product->sku ?? null;
                if ($sku) {
                    // store the product model for this sku (last seen)
                    $allInventories->put($sku, $inventory->product);
                }
            }
        }
        $this->allInventories = $allInventories;

        $uniqueSkus = $allInventories->keys()->values();

        // key inventories by sku for each warehouse for O(1) lookup
        $warehouseInventoryBySku = [];
        foreach ($this->warehouses as $warehouse) {
            $warehouseInventoryBySku[$warehouse->id] = $warehouse->inventories
                ->filter(fn($inv) => !empty($inv->product->sku))
                ->keyBy(fn($inv) => $inv->product->sku);
        }

        $rows = [];
        $wareCount = $this->warehouses->count();
        // totals for warehouse numeric columns
        $totals = array_fill(0, $wareCount, 0.0);

        foreach ($uniqueSkus as $sku) {
            $product = $allInventories->get($sku);
            $fnsku = $product->fnsku ?? null;

            $row = [$sku, $fnsku];
            $i = 0;
            foreach ($this->warehouses as $warehouse) {
                $found = $warehouseInventoryBySku[$warehouse->id]->get($sku);
                $qty = $found ? ($found->available_quantity ?? $found->quantity ?? null) : null;

                $num = null;
                if (is_numeric($qty)) {
                    $num = (float) $qty;
                    $totals[$i] += $num;
                }

                $row[] = $num;
                $i++;
            }
            $rows[] = $row;
        }

        // append totals row: ['Total', '', total_for_wh1, total_for_wh2, ...]
        $totalRow = ['Total', ''];
        foreach ($totals as $t) {
            // keep numeric 0 instead of empty for clarity — change to '' if you prefer blank cells
            $totalRow[] = $t === 0.0 ? 0 : $t;
        }
        $rows[] = $totalRow;

        // save for AfterSheet styling/formatting
        $this->rows = $rows;

        return new Collection($rows);
    }

    /**
     * Apply header style (user-provided) and format numeric columns and totals row.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // number of columns = 2 (SKU,FNSKU) + warehouses
                $totalCols = $this->warehouses->count() + 2;
                $lastCol = $this->numToColumnLetter($totalCols);
                $lastRow = count($this->rows) + 1; // +1 for header row

                // Header style (from your snippet)
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

                // Apply header style to row 1 (A1:LastCol1)
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);

                // Make totals row bold (lastRow)
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->getFont()->setBold(true);

                // Format warehouse columns (C .. LastCol) as numbers with no decimals
                if ($this->warehouses->count() > 0) {
                    $firstNumCol = $this->numToColumnLetter(3); // C (1=A,2=B,3=C)
                    $sheet->getStyle("{$firstNumCol}2:{$lastCol}{$lastRow}")->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                // Optional: header row height
                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }

    /**
     * Convert 1-based index to column letter: 1 -> A, 2 -> B, ..., 27 -> AA
     */
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
