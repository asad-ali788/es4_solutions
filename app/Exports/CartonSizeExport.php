<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CartonSizeExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    protected Collection $items;

    public function collection(): Collection
    {
        $this->items = Product::with(['listings.containerInfo', 'asins'])
            ->get()
            ->flatMap(function ($product) {
                return $product->listings
                    ->where('country', 'US')
                    ->map(function ($listing) use ($product) {
                        $details = $listing->containerInfo;

                        return [
                            'sku'                 => $product->sku,
                            'asin1'               => optional($product->asins)->asin1,
                            'item_size_length_cm' => $details->item_size_length_cm ?? '',
                            'item_size_width_cm'  => $details->item_size_width_cm ?? '',
                            'item_size_height_cm' => $details->item_size_height_cm ?? '',
                            'ctn_size_length_cm'  => $details->ctn_size_length_cm ?? '',
                            'ctn_size_width_cm'   => $details->ctn_size_width_cm ?? '',
                            'ctn_size_height_cm'  => $details->ctn_size_height_cm ?? '',
                            'item_weight_kg'      => $details->item_weight_kg ?? '',
                            'carton_weight_kg'    => $details->carton_weight_kg ?? '',
                            'quantity_per_carton' => $details->quantity_per_carton ?? '',
                        ];
                    });
            });

        return $this->items;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'ASIN',
            'Item Size Length (cm)',
            'Item Size Width (cm)',
            'Item Size Height (cm)',
            'Carton Size Length (cm)',
            'Carton Size Width (cm)',
            'Carton Size Height (cm)',
            'Item Weight (kg)',
            'Carton Weight (kg)',
            'Quantity per Carton',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // SKU
            'B' => 30,
            'C' => 25,
            'D' => 25,
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
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
