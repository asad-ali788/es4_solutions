<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class ShipmentItemExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithColumnWidths, WithCustomStartCell

{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function collection()
    {
        return $this->items->map(function ($item) {
            return [
                'SKU'               => optional($item->product)->sku,
                'Ordered Quantity'  => $item->quantity_ordered,
                'Received Quantity' => $item->quantity_received,
                'Warehouse'         => optional(optional($item->shipment)->warehouse)->warehouse_name,
                'Unit Cost'         => $item->unit_cost,
                'Total Cost'        => $item->total_cost,
                'Status'            => $item->status,
            ];
        });
    }
    public function startCell(): string
    {
        return 'A2'; // This is to Excel to start from cell A2
    }

    public function columnWidths(): array
    {
        return [
            'B' => 20,
            'C' => 20,
            'D' => 30,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $shipmentName = $this->items->first()?->shipment?->shipment_name ?? 'Shipment';

                // Merge cells for title
                $event->sheet->mergeCells('A1:G1');
                $event->sheet->setCellValue('A1', 'Shipment Name: ' . $shipmentName);

                // Style the title
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28a745'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Style for headings
                $event->sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Status-based background color map
                $statusColors = [
                    'pending'  => 'ffe181', // light yellow (warning)
                    'received' => '9ed6ac', // light green (success)
                    'damaged'  => 'e25562', // light red (danger)
                    'short'    => '3f80e4', // light blue (primary)
                ];

                // Start from row 3 (data starts here)
                $rowIndex = 3;
                foreach ($this->items as $item) {
                    $status = strtolower($item->status);
                    if (isset($statusColors[$status])) {
                        $cell = 'G' . $rowIndex;
                        $event->sheet->getStyle($cell)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $this->hexColor($statusColors[$status])],
                            ],
                        ]);
                    }
                    $rowIndex++;
                }
            }
        ];
    }

    protected function hexColor($colorName)
    {
        // You can keep named colors or ensure only hex is passed
        return $colorName;
    }


    public function headings(): array
    {
        return [
            'SKU',
            'Ordered Quantity',
            'Received Quantity',
            'Warehouse',
            'Unit Cost',
            'Total Cost',
            'Status',
        ];
    }
}
