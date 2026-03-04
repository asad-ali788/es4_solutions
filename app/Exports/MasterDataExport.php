<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterDataExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    public function collection(): Collection
    {
        return collect(
            Product::with(['listings' => function ($q) {
                $q->where('country', 'US');
            }], 'asins')
            ->whereHas('listings', function ($q) {
                $q->where('country', 'US');
            })
            ->get()
            ->flatMap(function ($product) {
                return $product->listings->map(function ($listing) use ($product) {
                    return [
                        'sku'               => $product->sku,
                        'short_title'       => $product->short_title,
                        'asin1'             => optional($product->asins)->asin1,
                        'title_amazon'      => $listing->title_amazon,
                        'bullet_point_1'    => $listing->bullet_point_1,
                        'bullet_point_2'    => $listing->bullet_point_2,
                        'bullet_point_3'    => $listing->bullet_point_3,
                        'bullet_point_4'    => $listing->bullet_point_4,
                        'bullet_point_5'    => $listing->bullet_point_5,
                        'description'       => $listing->description,
                        'country'           => $listing->country,
                        'product_category'  => $listing->product_category,
                    ];
                });
            })
        );
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Short Title',
            'Asin',
            'Title Amazon',
            'Bullet Point 1',
            'Bullet Point 2',
            'Bullet Point 3',
            'Bullet Point 4',
            'Bullet Point 5',
            'Description',
            'Country',
            'Product Category',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40, // SKU
            'B' => 30, // Short Title
            'C' => 20, // Translator
            'D' => 60, // Title Amazon
            'E' => 50,
            'F' => 50,
            'G' => 50,
            'H' => 50,
            'I' => 50,
            'J' => 60, // Description
            'K' => 15,
            'L' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(45);
        }
        // Wrap text for all columns
        $sheet->getStyle('A1:Z' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:L1')->applyFromArray([
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
