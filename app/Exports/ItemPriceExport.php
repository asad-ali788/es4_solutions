<?php

namespace App\Exports;

use App\Models\AmazonSoldPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemPriceExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return AmazonSoldPrice::select('asin', 'seller_sku', 'listing_price', 'landed_price')
            ->where('marketplace_id', 'ATVPDKIKX0DER')
            ->get();
    }

    public function headings(): array
    {
        return ['ASIN', 'Seller SKU', 'Listing Price', 'Landed Price'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // ASIN
            'B' => 35, // Seller SKU
            'C' => 15, // Listing Price
            'D' => 15, // Landed Price
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color'      => ['rgb' => 'D9E1F2'], // soft blue, change to any hex code
            ],
            'font' => [
                'bold' => true, // make heading bold if needed
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        return [];
    }
}
