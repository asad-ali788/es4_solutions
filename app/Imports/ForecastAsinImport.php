<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ForecastAsinImport implements ToArray, WithHeadingRow
{
    public function array(array $rows)
    {
        return $rows;
    }
}
