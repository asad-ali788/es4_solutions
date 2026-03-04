<?php

namespace App\Exports;

use App\Models\SourcingContainerItem;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    WithDrawings,
    WithColumnWidths,
    WithEvents,
    WithCustomValueBinder
};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Events\AfterSheet;

class SourcingExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithDrawings,
    WithColumnWidths,
    WithEvents,
    WithCustomValueBinder
{
    protected $items;

    public function __construct()
    {
        $this->items = SourcingContainerItem::with(['sourcingContainer', 'latestMessage.sender'])->get();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Amazon URL',
            'Image',
            'Short Title',
            'Supplier Price',
            'Amazon Pricing',
            'Qty to Order',
            'Listing Notes'
        ];
    }

    public function map($item): array
    {
        return [
            $item->amazon_url ?? '--',
            '', // Image cell
            $item->short_title ?? '--',
            $item->price ?? '00',
            $item->amz_price ?? '00',
            $item->qty_to_order ?? '--',
            $item->notes ?? '--',
        ];
    }

    public function drawings()
    {
        $drawings = [];
        $storagePath = public_path('storage/');

        foreach ($this->items as $index => $item) {
            $imagePath = $storagePath . $item->image;

            if ($item->image && file_exists($imagePath)) {
                $drawing = new Drawing();
                $drawing->setDescription('Image for ' . $item->sku);
                $drawing->setPath($imagePath);
                $drawing->setHeight(50);
                $drawing->setCoordinates('B' . ($index + 2)); // account for headings
                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 25,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 35,
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $cell->getHyperlink()->setUrl($value);  // Make it clickable
            $cell->setValue('Amazon Link');         // Display text
            $cell->setDataType(DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value); // This will now work correctly
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Bold header row
                $sheet->getStyle('A1:O1')->getFont()->setBold(true);

                $rowCount = count($this->items);
                $hyperlinkStyle = [
                    'font' => [
                        'color'     => ['rgb' => '0000FF'],
                        'underline' => true,
                    ],
                ];
                for ($i = 2; $i <= $rowCount + 1; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(40);
                    $sheet->getStyle("A{$i}")->applyFromArray($hyperlinkStyle);
                    $sheet->getStyle("C{$i}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("H{$i}")->getAlignment()->setWrapText(true);
                }
            },
        ];
    }
}
