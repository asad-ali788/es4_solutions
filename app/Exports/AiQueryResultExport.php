<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AiQueryResultExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection($this->data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        if (empty($this->data)) {
            return [];
        }

        // Use keys from the first row as headings
        $firstRow = reset($this->data);
        
        return array_map(function($key) {
            // Convert snake_case or camelCase to Human Readable Title
            return ucwords(str_replace(['_', '-'], ' ', (string)$key));
        }, array_keys($firstRow));
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold with a light gray background
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E7D32'] // Green for AI/Excel feel
                ],
            ],
        ];
    }
}
