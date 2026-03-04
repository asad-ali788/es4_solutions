<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class AsinForecastTemplateExport implements FromCollection, WithHeadings
{
    protected $asinList;

    public function __construct(array $asinList = [])
    {
        // Pass your ASINs or leave empty
        $this->asinList = $asinList;
    }

    public function collection()
    {
        $rows = [];

        foreach ($this->asinList as $asin) {
            $rows[] = array_merge([$asin], array_fill(0, 12, '')); // 12 empty columns for months
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $startMonth = Carbon::now(); // Current month
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $months[] = $startMonth->copy()->addMonths($i)->format('Y-m');
        }

        return array_merge(['ASIN'], $months);
    }
}
