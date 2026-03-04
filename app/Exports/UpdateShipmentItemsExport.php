<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class UpdateShipmentItemsExport implements FromCollection, WithHeadings
{
    protected $excludedRows;

    public function __construct(array $excludedRows)
    {
        $this->excludedRows = $excludedRows;
    }

    public function collection()
    {
        // Convert array to collection and map keys for Excel columns
        return collect($this->excludedRows)->map(function ($row) {
            return [
                'SKU'           => $row['sku'] ?? '',
                'Received Qty'  => $row['received_qty'] ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Received Qty',
        ];
    }
}
