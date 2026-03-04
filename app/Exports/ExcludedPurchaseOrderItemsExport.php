<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExcludedPurchaseOrderItemsExport implements FromCollection, WithHeadings
{
    protected $excluded;

    public function __construct(array $excluded)
    {
        $this->excluded = $excluded;
    }

    public function collection()
    {
        return new Collection($this->excluded);
    }

    public function headings(): array
    {
        return ['sku', 'qty'];
    }
}
