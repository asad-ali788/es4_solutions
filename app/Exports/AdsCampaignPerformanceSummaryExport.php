<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use Carbon\Carbon;
use App\Models\AmzAdsCampaignPerformanceReport;
use App\Models\AmzAdsCampaignPerformanceReportSd;
use App\Models\AmzAdsCampaignSBPerformanceReport;

class AdsCampaignPerformanceSummaryExport implements FromCollection, WithHeadings, WithStyles, WithCharts, WithColumnWidths
{
    protected $startDate;
    protected $endDate;
    protected $country;
    protected $grouping;
    protected $rowCount;

    public function __construct($startDate = null, $endDate = null, $country = 'US', $grouping = 'day')
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->country   = $country;
        $this->grouping  = $grouping;
    }

    public function collection(): Collection
    {
        $applyFilters = function ($query, $dateColumn) {
            // $query->where('country', $this->country);
            if ($this->startDate) {
                $query->where($dateColumn, '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->where($dateColumn, '<=', $this->endDate);
            }
        };

        // Period expressions as strings
        $periodExprSP = match ($this->grouping) {
            'week'  => "YEARWEEK(c_date, 1)",
            'month' => "DATE_FORMAT(c_date, '%Y-%m')",
            default => "c_date",
        };

        $periodExprSD = $periodExprSP;
        $periodExprSB = match ($this->grouping) {
            'week'  => "YEARWEEK(date, 1)",
            'month' => "DATE_FORMAT(date, '%Y-%m')",
            default => "date",
        };

        // --- Build SP query ---
        $spQuery = AmzAdsCampaignPerformanceReport::query()
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'amz_ads_campaign_performance_report.country')
            ->selectRaw("$periodExprSP as period,
                SUM(cost * COALESCE(cur.conversion_rate_to_usd, 1)) as total_spend,
                SUM(sales7d * COALESCE(cur.conversion_rate_to_usd, 1)) as total_sales,
                SUM(purchases7d) as orders
            ")
            ->when(true, fn($q) => $applyFilters($q, 'c_date'))
            ->groupBy('period');


        // --- Build SD query ---
        $sdQuery = AmzAdsCampaignPerformanceReportSd::query()
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'amz_ads_campaign_performance_report_sd.country')
            ->selectRaw("$periodExprSD as period,
                SUM(cost * COALESCE(cur.conversion_rate_to_usd, 1)) as total_spend,
                SUM(sales * COALESCE(cur.conversion_rate_to_usd, 1)) as total_sales,
                SUM(purchases) as orders
            ")
            ->when(true, fn($q) => $applyFilters($q, 'c_date'))
            ->groupBy('period');


        // --- Build SB query ---
        $sbQuery = AmzAdsCampaignSBPerformanceReport::query()
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'amz_ads_campaign_performance_reports_sb.country')
            ->selectRaw("$periodExprSB as period,
                SUM(cost * COALESCE(cur.conversion_rate_to_usd, 1)) as total_spend,
                SUM(sales * COALESCE(cur.conversion_rate_to_usd, 1)) as total_sales,
                SUM(purchases) as orders
            ")
            ->when(true, fn($q) => $applyFilters($q, 'date'))
            ->groupBy('period');


        // --- Union all queries ---
        $rows = collect($spQuery->unionAll($sdQuery)->unionAll($sbQuery)->get());

        // --- Aggregate by period ---
        $rows = $rows->groupBy('period')->map(function ($group) {
            $total_spend = $group->sum('total_spend');
            $total_sales = $group->sum('total_sales');
            $orders      = $group->sum('orders');
            $period      = $group->first()->period;

            // Format period
            if ($this->grouping === 'week') {
                $year = substr($period, 0, 4);
                $week = substr($period, 4);
                $startOfWeek = Carbon::now()->setISODate($year, (int)$week)->startOfWeek();
                $endOfWeek   = Carbon::now()->setISODate($year, (int)$week)->endOfWeek();
                $period = $startOfWeek->format('j M') . ' - ' . $endOfWeek->format('j M');
            } elseif ($this->grouping === 'month') {
                $period = Carbon::parse($period . '-01')->format('F Y');
            } else {
                $period = Carbon::parse($period)->format('Y-m-d');
            }

            return [
                'period'      => $period,
                'total_spend' => $total_spend,
                'total_sales' => $total_sales,
                'orders'      => $orders,
                'acos'        => round(calculateACOS($total_spend, $total_sales), 2) . '%',
            ];
        })->sortKeys();

        $this->rowCount = $rows->count();

        // --- Totals row ---
        $totals = [
            'period'      => 'Total',
            'total_spend' => $rows->sum('total_spend'),
            'total_sales' => $rows->sum('total_sales'),
            'orders'      => $rows->sum('orders'),
            'acos'        => round(calculateACOS($rows->sum('total_spend'), $rows->sum('total_sales')), 2) . '%',
        ];

        return $rows->push($totals);
    }


    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15];
    }

    public function headings(): array
    {
        return [ucfirst($this->grouping === 'day' ? 'Date' : $this->grouping), 'Total Spend ($)', 'Total Sales ($)', 'Orders', 'ACoS'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$sheet->getHighestRow()}:E{$sheet->getHighestRow()}")->getFont()->setBold(true);
        return [];
    }

    public function charts()
    {
        // Exclude the totals row
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$A$2:$A$' . $this->rowCount, null, $this->rowCount - 1)];
        $series1    = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Worksheet!$B$2:$B$' . $this->rowCount, null, $this->rowCount - 1);
        $series2    = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Worksheet!$C$2:$C$' . $this->rowCount, null, $this->rowCount - 1);

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, 1),
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$B$1', null, 1), new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$C$1', null, 1)],
            $categories,
            [$series1, $series2]
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $chart = new Chart('campaign_performance_chart', new Title('Campaign Performance'), new Legend(Legend::POSITION_BOTTOM, null, false), $plotArea);
        $chart->setXAxisLabel(new Title(''))->setYAxisLabel(new Title(''))->setTopLeftPosition('A10')->setBottomRightPosition('G25');

        return [$chart];
    }
}
