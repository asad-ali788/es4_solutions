<?php

namespace App\Exports;

use App\Traits\HasFilteredAdsPerformance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CampaignPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    use HasFilteredAdsPerformance;

    protected $filters;

    public function __construct(array $filters = [], string $mode = 'full')
    {
        $this->filters = $filters;
        $this->mode = $mode;
    }

    public function collection()
    {
        if ($this->mode === 'download') {
            return $this->getLast7DaysCampaignsQuery()->get();
        }

        // old behavior untouched
        return $this->getFilteredCampaignsQuery(request()->merge($this->filters))->get();
    }

    public function map($row): array
    {
        if ($this->mode === 'download') {
            return [
                (string) $row->campaign_id,
                $row->asin ?? '',
                $row->related_asins ? implode(',', json_decode($row->related_asins, true)) : '',
                $row->campaign_name,
                $row->report_week,
                $row->country,
                $row->campaign_types,
                $row->total_daily_budget,
                $row->total_spend,
                $row->total_sales,
                $row->purchases7d,
                $row->total_spend_7d,
                $row->total_sales_7d,
                $row->purchases7d_7d,
                $row->acos_7d / 100,
                $row->total_spend_14d,
                $row->total_sales_14d,
                $row->purchases7d_14d,
            ];
        }

        // full mode (your current one)
        return [
            (string) $row->campaign_id,
            $row->asin ?? '',
            $row->related_asins ? implode(',', json_decode($row->related_asins, true)) : '',
            $row->campaign_name,
            $row->report_week,
            $row->country,
            $row->campaign_types,
            $row->total_daily_budget,
            $row->total_spend,
            $row->total_sales,
            $row->purchases7d,
            $row->total_spend_7d,
            $row->total_sales_7d,
            $row->purchases7d_7d,
            $row->acos_7d / 100,
            $row->total_spend_14d,
            $row->total_sales_14d,
            $row->purchases7d_14d,
            $row->ai_recommendation,
            $row->ai_suggested_budget,
            $row->suggested_budget,
            $row->recommendation,
        ];
    }

    public function headings(): array
    {
        if ($this->mode === 'download') {
            return [
                'Campaign ID',
                'SP ASIN',
                'SB ASINs',
                'Campaign Name',
                'Report Week',
                'Country',
                'Campaign Type',
                'Daily Budget',
                'Spend 1d',
                'Sales 1d',
                'Purchases 1d',
                'Spend 7d',
                'Sales 7d',
                'Purchases 7d',
                'ACoS 7d',
                'Spend 14d',
                'Sales 14d',
                'Purchases 14d',
            ];
        }

        return [
            'Campaign ID',
            'SP ASIN',
            'SB ASINs',
            'Campaign Name',
            'Report Week',
            'Country',
            'Campaign Type',
            'Daily Budget',
            'Spend 1d',
            'Sales 1d',
            'Purchases 1d',
            'Spend 7d',
            'Sales 7d',
            'Purchases 7d',
            'ACoS 7d',
            'Spend 14d',
            'Sales 14d',
            'Purchases 14d',
            'AI Recommendation',
            'AI Suggested Budget',
            'Suggested Budget',
            'Recommendation',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'O' => '0.0%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:V1')->getFont()->setBold(true);
        return [];
    }
}
