<?php

namespace App\Exports;

use App\Traits\OrderForecastQueryTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class OrderForecastAsinMonthlyExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnWidths
{
    use OrderForecastQueryTrait;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $month  = $this->request->query('month', now()->format('Y-m'));
        $search = $this->request->query('search');
        $forecastFilter = $this->request->query('forecast_filter');

        return $this->getAsinMonthlyForecast(
            month: $month,
            search: $search,
            forecastFilter: $forecastFilter,
            paginate: false 
        )['records'];
    }

    public function headings(): array
    {
        return [
            'ASIN',
            'Last Year Sold',
            'Current Month Sold',
            'Forecast Units',
            'FC Fulfillment %',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // ASIN
            'B' => 12, // Last Year Sold
            'C' => 18, // Current Month Sold
            'D' => 14, // Forecast Units
            'E' => 15, // FC Fulfillment %
        ];
    }

    public function map($row): array
    {
        return [
            $row->asin,
            $row->last_year_sold,
            $row->current_month_sold,
            $row->forecast_units,
            $row->fcf_percent,
        ];
    }
}
