<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CombinedAdsPerformanceExport implements WithMultipleSheets
{
    protected $periods = [];
    protected $country;

    public function __construct(array $periods = [], $country = 'US')
    {
        $this->periods = $periods; // e.g., ['last7days', 'last4weeks', 'last3months']
        $this->country = $country;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->periods as $period) {

            // SP sheet
            $sheets[] = new \App\Exports\AdsPerformanceSummaryExport(
                $period['start'], 
                $period['end'], 
                $this->country, 
                $period['grouping']
            );

            // SD sheet
            $sheets[] = new \App\Exports\AdsPerformanceSummaryExportSd(
                $period['start'], 
                $period['end'], 
                $this->country, 
                $period['grouping']
            );
        }

        return $sheets;
    }
}
