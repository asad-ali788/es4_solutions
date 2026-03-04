<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LibraryImagesExport implements FromCollection, WithHeadings, WithColumnWidths, WithMapping, WithStyles
{
    protected Collection $items;

    public function collection(): Collection
    {
        $this->items = Product::with(['listings.additionalDetail', 'asins'])
            ->get()
            ->flatMap(function ($product) {
                return $product->listings
                    ->where('country', 'US')
                    ->map(function ($listing) use ($product) {
                        $details = $listing->additionalDetail;
                        return [
                            'sku'    => $product->sku,
                            'asin1'  => optional($product->asins)->asin1,
                            'image1' => $details->image1 ?? '',
                            'image2' => $details->image2 ?? '',
                            'image3' => $details->image3 ?? '',
                            'image4' => $details->image4 ?? '',
                            'image5' => $details->image5 ?? '',
                            'image6' => $details->image6 ?? '',
                        ];
                    });
            });

        return $this->items;
    }

    public function map($row): array
    {
        $makeLink = fn($url) => $url ? '=HYPERLINK("' . $url . '", "' . $url . '")' : '';

        return [
            $row['sku'],
            $row['asin1'],
            $makeLink($row['image1']),
            $makeLink($row['image2']),
            $makeLink($row['image3']),
            $makeLink($row['image4']),
            $makeLink($row['image5']),
            $makeLink($row['image6']),
        ];
    }

    public function headings(): array
    {
        return ['SKU', 'ASIN', 'Image 1', 'Image 2', 'Image 3', 'Image 4', 'Image 5', 'Image 6'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 30,
            'C' => 60,
            'D' => 60,
            'E' => 60,
            'F' => 60,
            'G' => 60,
            'H' => 60,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
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
