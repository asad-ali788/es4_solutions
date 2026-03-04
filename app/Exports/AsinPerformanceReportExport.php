<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\OrderForecastPerformanceService;

class AsinPerformanceReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithColumnWidths,
    WithStyles
{
    protected Carbon $monthStart;
    protected string $monthLabel;
    protected OrderForecastPerformanceService $service;
    protected int $daysPassed;
    protected int $totalDays;
    protected array $agentMap = [];
    protected array $filters;

    public function __construct(
        OrderForecastPerformanceService $service,
        Carbon $monthStart,
        array $filters = []
    ) {
        $marketTz = config('timezone.market');

        $this->service    = $service;
        $this->monthStart = $monthStart;
        $this->filters    = $filters;

        $this->monthLabel = $this->monthStart->format('F Y');

        $now = Carbon::now($marketTz);
        $this->totalDays  = $this->monthStart->daysInMonth;
        $this->daysPassed = min($now->day, $this->totalDays);

        $this->agentMap = DB::table('user_assigned_asins as ua')
            ->join('users as u', 'u.id', '=', 'ua.user_id')
            ->pluck('u.name', 'ua.asin')
            ->toArray();
    }

    /**
     * Use service but bypass cache
     */
    public function collection()
    {
        $records = $this->service->queryBaseData(
            $this->monthStart,
            $this->monthStart->copy()->endOfMonth(),
            $this->filters['search'] ?? '',
            PHP_INT_MAX, // export ALL filtered rows
            $this->daysPassed,
            $this->totalDays,
            $this->filters['fcf_filter'] ?? null,
            $this->filters['acos_filter'] ?? null
        );

        $monthStart = $this->monthStart;

        $records->getCollection()->transform(function ($row) use ($monthStart) {

            $stock = ($row->amazon_stock ?? 0)
                + ($row->warehouse_stock ?? 0)
                + ($row->route_stock ?? 0);

            $remaining = $stock;
            $coverageDays = 0;

            $forecasts = DB::table('product_forecast_asins')
                ->select('forecast_month', 'forecast_units')
                ->where('product_asin', $row->product_asin)
                ->where('forecast_month', '>=', $monthStart->format('Y-m-01'))
                ->orderBy('forecast_month')
                ->limit(12)
                ->get();

            foreach ($forecasts as $fc) {
                if ($remaining <= 0) {
                    break;
                }

                $daysInMonth = Carbon::parse($fc->forecast_month)->daysInMonth;
                $monthlyForecast = $fc->forecast_units;

                if ($remaining >= $monthlyForecast) {
                    $remaining -= $monthlyForecast;
                    $coverageDays += $daysInMonth;
                } else {
                    $coverageDays += ($remaining / $monthlyForecast) * $daysInMonth;
                    break;
                }
            }

            $row->afn3pl_fc_sr = round($coverageDays, 1);

            return $row;
        });

        return $records->getCollection();
    }



    public function map($row): array
    {
        return [
            $row->product_name ?? '-',
            $row->product_asin,
            '',
            $this->agentMap[$row->product_asin] ?? '',
            'North America',

            $row->forecast_units,
            $row->month_sold,
            $row->full_month_projection,
            $row->full_month_delta,
            $row->daily_forecast,
            $row->daily_rate_of_sale,

            $row->fcf_full_month,
            $row->last_7_days_sold ?? 0,
            $row->last_14_days_sold ?? 0,
            $row->fcf_7_days,

            $row->amazon_stock ?? 0,
            $row->warehouse_stock ?? 0,
            $row->route_stock ?? 0,

            $row->ad_spend ?? 0,
            $row->ad_sales ?? 0,
            $row->acos ?? 0,
            $row->total_ads_units ?? 0,

            round($row->afn3pl, 2),
            $row->afn3pl_fc_sr !== null && $row->afn3pl_fc_sr > 180
                ? '180+ days'
                : ($row->afn3pl_fc_sr !== null ? round($row->afn3pl_fc_sr) . ' days' : '-')
        ];
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'ASIN',
            'Short Title',
            'Agent Name',
            'Country',
            $this->monthLabel . ' Forecast',
            $this->monthLabel . ' Sold',
            'Full Month Sales Projection',
            'Full Month Unit Delta',
            'Daily Forecast',
            'Daily Rate of Sale',
            'FCF % (Full Month)',
            'Last 7 Days Sold',
            'Last 14 Days Sold',
            'FCF % (7 Days)',
            'Amz Stock',
            'WH Stock',
            'Route Stock',
            'Total Ads Spend',
            'Total Sales',
            'ACoS %',
            'Total Unit Sold (Ads)',
            'AFN 3PL Route__HV SR',
            'AFN 3PL Route__FC SR',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32, // Product Name
            'B' => 16, // ASIN
            'C' => 20, // Agent
            'D' => 18, // Country
            'E' => 22,
            'F' => 20,
            'G' => 26,
            'H' => 22,
            'I' => 16,
            'J' => 20,
            'K' => 20,
            'L' => 18,
            'M' => 18,
            'N' => 18,
            'O' => 14,
            'P' => 14,
            'Q' => 14,
            'R' => 18,
            'S' => 14,
            'T' => 14,
            'U' => 18,
            'V' => 20,
            'W' => 20,
            'X' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'H' => ['fill' => $this->fill('#EDEDED')],
            'I' => ['fill' => $this->fill('#EDEDED')],
            'J' => ['fill' => $this->fill('#EDEDED')],
            'K' => ['fill' => $this->fill('#EDEDED')],

            'L' => ['fill' => $this->fill('#E4D9F5')],
            'O' => ['fill' => $this->fill('#E4D9F5')],

            'M' => ['fill' => $this->fill('#DDEBF7')],
            'N' => ['fill' => $this->fill('#DDEBF7')],

            'P' => ['fill' => $this->fill('#FFF2CC')],
            'Q' => ['fill' => $this->fill('#FFF2CC')],
            'R' => ['fill' => $this->fill('#FFF2CC')],

            'S' => ['fill' => $this->fill('#C6EFCE')],
            'T' => ['fill' => $this->fill('#C6EFCE')],

            'U' => ['fill' => $this->fill('#E4D9F5')],
            'V' => ['fill' => $this->fill('#C6EFCE')],
        ];
    }

    private function fill(string $hex): array
    {
        return [
            'fillType' => 'solid',
            'startColor' => ['argb' => 'FF' . strtoupper(ltrim($hex, '#'))],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,

            'L' => NumberFormat::FORMAT_PERCENTAGE_00,

            'M' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_NUMBER,

            'O' => NumberFormat::FORMAT_PERCENTAGE_00,

            'P' => NumberFormat::FORMAT_NUMBER,
            'Q' => NumberFormat::FORMAT_NUMBER,
            'R' => NumberFormat::FORMAT_NUMBER,

            'S' => NumberFormat::FORMAT_NUMBER,
            'T' => NumberFormat::FORMAT_NUMBER,

            'U' => NumberFormat::FORMAT_PERCENTAGE_00,

            'V' => NumberFormat::FORMAT_NUMBER,
            'W' => NumberFormat::FORMAT_NUMBER,
        ];
    }
}
