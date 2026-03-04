<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdsKeywordPerformanceReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $country;

    public function __construct($country = 'US')
    {
        $this->country = $country;
    }

    public function collection()
    {
        $startDate = now()->subDays(6)->toDateString();
        $endDate   = now()->subDays(1)->toDateString();

        // SP
        $spKeywords = DB::table('amz_ads_keyword_performance_report as r')
            ->where('r.country', $this->country)
            ->whereBetween('r.c_date', [$startDate, $endDate])
            ->where('r.cost', '>', 0)
            ->selectRaw('
                r.keyword_id,
                r.match_type,
                MAX(r.keyword_text) as keyword_text,
                COALESCE(SUM(r.clicks), 0) as clicks,
                COALESCE(SUM(r.cost), 0) as spend,
                COALESCE(SUM(r.purchases7d), 0) as orders,
                COALESCE(SUM(r.sales7d), 0) as sales
            ')
            ->groupBy('r.keyword_id', 'r.match_type')
            ->get();

        // SB
        $sbKeywords = DB::table('amz_ads_keyword_performance_report_sb as r')
            ->where('r.country', $this->country)
            ->whereBetween('r.c_date', [$startDate, $endDate])
            ->where('r.cost', '>', 0)
            ->selectRaw('
                r.keyword_id,
                r.match_type,
                MAX(r.keyword_text) as keyword_text,
                COALESCE(SUM(r.clicks), 0) as clicks,
                COALESCE(SUM(r.cost), 0) as spend,
                COALESCE(SUM(r.purchases1d), 0) as orders,
                COALESCE(SUM(r.sales1d), 0) as sales
            ')
            ->groupBy('r.keyword_id', 'r.match_type')
            ->get();

        // merge and aggregate
        $combined = $spKeywords->concat($sbKeywords);

        $merged = $combined
            ->groupBy('keyword_id')
            ->map(function ($group, $keywordId) {
                $clicks = $group->sum(fn($r) => (int) ($r->clicks ?? 0));
                $spend  = $group->sum(fn($r) => (float) ($r->spend ?? 0));
                $orders = $group->sum(fn($r) => (int) ($r->orders ?? 0));
                $sales  = $group->sum(fn($r) => (float) ($r->sales ?? 0));

                $keywordText = $group->pluck('keyword_text')->filter()->first() ?? '';

                $matchTypes = $group
                    ->pluck('match_type')
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return (object) [
                    'keyword_id'   => $keywordId,
                    'keyword_text' => $keywordText,
                    'match_type'   => $matchTypes ?: '',
                    'clicks'       => $clicks,
                    'spend'        => $spend,
                    'orders'       => $orders,
                    'sales'        => $sales,
                ];
            })
            ->values()
            ->sortByDesc(fn($r) => $r->sales)
            ->values();

        // prepare rows
        $rows = $merged->map(function ($keyword) {
            // get raw ACoS from your helper
            $acosRaw = calculateACOSExcel((float) ($keyword->spend ?? 0), (float) ($keyword->sales ?? 0));
            // if helper returns percentage like 5 for 5%, convert to fraction 0.05 for Excel percentage format
            $acosFraction = ($acosRaw > 1) ? ($acosRaw / 100.0) : $acosRaw;

            return [
                'keyword_id'   => $keyword->keyword_id,
                'keyword_text' => $keyword->keyword_text ?? '',
                'match_type'   => $keyword->match_type ?? '',
                'clicks'       => (int) ($keyword->clicks ?? 0),
                'spend'        => (float) ($keyword->spend ?? 0),   // numeric
                'orders'       => (int) ($keyword->orders ?? 0),
                'sales'        => (float) ($keyword->sales ?? 0),   // numeric
                'acos'         => round($acosFraction, 4),         // fraction for percentage format
            ];
        })->values();

        // final guard: make sure numeric fields are never null/empty
        $rows = $rows->map(function ($row) {
            return [
                'keyword_id'   => $row['keyword_id'] ?? '',
                'keyword_text' => $row['keyword_text'] ?? '',
                'match_type'   => $row['match_type'] ?? '',
                'clicks'       => (int) ($row['clicks'] ?? 0),
                'spend'        => (float) ($row['spend'] ?? 0.0),
                'orders'       => (int) ($row['orders'] ?? 0),
                'sales'        => (float) ($row['sales'] ?? 0.0),
                'acos'         => (float) ($row['acos'] ?? 0.0),
            ];
        });

        // totals row (ensure numeric)
        $totals = [
            'keyword_id'   => 'Total',
            'keyword_text' => '',
            'match_type'   => '',
            'clicks'       => $rows->sum('clicks'),
            'spend'        => $rows->sum('spend'),
            'orders'       => $rows->sum('orders'),
            'sales'        => $rows->sum('sales'),
            'acos'         => round(calculateACOSExcel($rows->sum('spend'), $rows->sum('sales')) / 100.0, 4),
        ];

        $rows = $rows->push($totals)->map(fn($r) => collect($r)->toArray());

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Keyword ID',
            'Keyword Text',
            'Match Type',
            'Clicks',
            'Spend ($)',
            'Orders',
            'Sales ($)',
            'ACoS (%)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 40,
            'C' => 20,
            'D' => 15,
            'E' => 18,
            'F' => 15,
            'G' => 18,
            'H' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A{$lastRow}:H{$lastRow}")->getFont()->setBold(true);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            // leave A as text to allow the 'Total' label
            'A' => '@',
            'D' => NumberFormat::FORMAT_NUMBER,        // clicks -> integer
            'E' => NumberFormat::FORMAT_NUMBER_00,     // spend -> 2 decimals
            'F' => NumberFormat::FORMAT_NUMBER,        // orders -> integer
            'G' => NumberFormat::FORMAT_NUMBER_00,     // sales -> 2 decimals
            'H' => NumberFormat::FORMAT_PERCENTAGE_00, // ACoS -> fraction (0.05 = 5%)
        ];
    }
}
