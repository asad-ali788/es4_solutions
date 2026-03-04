<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrderForecastSnapshotsCombinedExport implements WithMultipleSheets
{
    protected int $forecastId;

    public function __construct(int $forecastId)
    {
        $this->forecastId = $forecastId;
    }

    public function sheets(): array
    {
        return [
            'SKU Snapshots' => new OrderForecastSnapshotExport($this->forecastId),
            'ASIN Snapshots' => new OrderForecastSnapshotAsinsExport($this->forecastId),
        ];
    }
}
