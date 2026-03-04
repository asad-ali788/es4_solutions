<?php

namespace App\Exports;

use App\Models\AmzAdsProductPerformanceReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AdsPerformanceSummaryExport implements FromCollection, WithHeadings, WithStyles, WithCharts, WithColumnWidths
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
        $query = AmzAdsProductPerformanceReport::where('country', $this->country);

        if ($this->startDate) {
            $query->whereDate('c_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('c_date', '<=', $this->endDate);
        }

        // Grouping logic without switch case
        if ($this->grouping === 'week') {
            $query->selectRaw("YEARWEEK(c_date, 1) as period,
                           SUM(cost) as total_spend,
                           SUM(sales7d) as total_sales,
                           SUM(purchases7d) as orders")
                ->groupBy('period')
                ->orderBy('period');
        } elseif ($this->grouping === 'month') {
            $query->selectRaw("DATE_FORMAT(c_date, '%Y-%m') as period,
                           SUM(cost) as total_spend,
                           SUM(sales7d) as total_sales,
                           SUM(purchases7d) as orders")
                ->groupBy('period')
                ->orderBy('period');
        } else { // day
            $query->selectRaw("c_date as period,
                           SUM(cost) as total_spend,
                           SUM(sales7d) as total_sales,
                           SUM(purchases7d) as orders")
                ->groupBy('period')
                ->orderBy('period');
        }

        $rows = $query->get()->map(function ($row, $index) {
            $acos = calculateACOS($row->total_spend, $row->total_sales);

            if ($this->grouping === 'week') {
                $year = substr($row->period, 0, 4);
                $week = substr($row->period, 4);

                $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
                $endOfWeek   = Carbon::now()->setISODate($year, $week)->endOfWeek();

                $label = $startOfWeek->format('j M') . ' - ' . $endOfWeek->format('j M');
            } elseif ($this->grouping === 'month') {
                $label = Carbon::parse($row->period . '-01')->format('F Y');
            } else {
                $label = Carbon::parse($row->period)->format('Y-m-d');
            }
            return [
                'period'       => $label,
                'total_spend'  => $row->total_spend,
                'total_sales'  => $row->total_sales,
                'orders'       => $row->orders,
                'acos'         => round($acos, 2) . '%',
            ];
        });
        // store row count for chart ranges (excluding the totals row we’ll push later)
        $this->rowCount = $rows->count();

        // Totals row
        $totalSpend = $rows->sum('total_spend');
        $totalSales = $rows->sum('total_sales');

        $totals = [
            'period'      => 'Total',
            'total_spend' => $totalSpend,
            'total_sales' => $totalSales,
            'orders'      => $rows->sum('orders'),
            'acos'        => round(calculateACOS($totalSpend, $totalSales), 2) . '%',
        ];

        return $rows->push($totals);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }

    public function headings(): array
    {
        return [
            ucfirst($this->grouping === 'day' ? 'Date' : $this->grouping),
            'Total Spend ($)',
            'Total Sales ($)',
            'Orders',
            'ACoS',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9E1F2']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);

        return [];
    }
    public function charts()
    {
        // Categories (X-axis labels: Dates/Periods)
        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                'Worksheet!$A$2:$A$' . ($this->rowCount + 1), // Period labels
                null,
                $this->rowCount
            )
        ];

        // Series data
        $series1 = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            'Worksheet!$B$2:$B$' . ($this->rowCount + 1),
            null,
            $this->rowCount
        );

        $series2 = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            'Worksheet!$C$2:$C$' . ($this->rowCount + 1),
            null,
            $this->rowCount
        );

        // Grouped (clustered) columns
        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count([$series1, $series2]) - 1),
            [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$B$1', null, 1),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$C$1', null, 1)
            ],
            $categories, // X-axis labels
            [$series1, $series2]
        );

        // Make sure bars are vertical (column chart)
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);

        $chart = new Chart(
            'performance_chart',
            new Title('Ads Performance'),
            new Legend(Legend::POSITION_BOTTOM, null, false),
            $plotArea
        );

        // Remove axis titles so only data shows
        $chart->setXAxisLabel(new Title(''));
        $chart->setYAxisLabel(new Title(''));

        $chart->setTopLeftPosition('A10');
        $chart->setBottomRightPosition('G25');

        return [$chart];
    }
}
