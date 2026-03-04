<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UpdatePurchaseOrderItemsExport implements FromCollection, WithHeadings
{
    protected $excludedRows;

    public function __construct(array $excludedRows)
    {
        $this->excludedRows = $excludedRows;
    }

    public function collection()
    {
        return collect($this->excludedRows)->map(function ($row) {
            return [
                'SKU'           => $row['sku'] ?? '',
                'Received Qty'  => $row['qty'] ?? '',
                'Reason'        => $row['reason'] ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Received Qty',
            'Reason',
        ];
    }
}
