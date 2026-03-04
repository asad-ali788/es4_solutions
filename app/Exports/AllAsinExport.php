<?php

namespace App\Exports;

use App\Models\ProductAsins;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AllAsinExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ProductAsins::query()
            ->select('asin1')
            ->orderBy('asin1')
            ->distinct()
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['ASIN'];
    }
}
