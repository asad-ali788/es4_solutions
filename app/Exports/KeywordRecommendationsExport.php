<?php

namespace App\Exports;

use App\Traits\HasFilteredAdsPerformance;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class KeywordRecommendationsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    use HasFilteredAdsPerformance;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = $this->getFilteredKeywordsQuery($this->request);
        $data = $query->get();

        // ✅ Group by keyword_id and combine all related_asin values
        $grouped = $data->groupBy('keyword_id')->map(function ($rows) {
            $first = $rows->first();
            $first->related_asin = $rows->pluck('related_asin')
                ->filter()
                ->unique()
                ->implode(', ');
            return $first;
        });

        return $grouped->values();
    }

    public function map($row): array
    {
        $relatedAsin = $row->related_asin ?: $row->asin ?: '';

        return [
            (string) $row->keyword_id,
            (string) $row->campaign_id,
            $row->c_name ?? $row->sb_c_name,
            $relatedAsin,
            $row->keyword,
            $row->date,
            $row->country,

            // 1d metrics
            $row->clicks,
            $row->cpc,
            $row->ctr,
            $row->conversion_rate,
            $row->acos,
            $row->impressions,
            $row->bid,
            $row->total_spend,
            $row->total_sales,
            $row->purchases1d,

            // 7d metrics
            $row->clicks_7d,
            $row->cpc_7d,
            $row->ctr_7d,
            $row->conversion_rate_7d,
            $row->total_spend_7d,
            $row->total_sales_7d,
            $row->purchases1d_7d,
            $row->acos_7d,
            $row->impressions_7d,

            // 14d metrics
            $row->total_spend_14d,
            $row->total_sales_14d,
            $row->purchases1d_14d,

            // Recommendations
            $row->recommendation,
            $row->suggested_bid,
            $row->ai_suggested_bid,
            $row->ai_recommendation,
        ];
    }

    public function headings(): array
    {
        return [
            'Keyword ID',
            'Campaign ID',
            'Campaign Name',
            'ASIN',
            'Keyword',
            'Date',
            'Country',

            // 1d
            'Clicks (1d)',
            'CPC (1d)',
            'CTR (%) (1d)',
            'Conversion Rate (%) (1d)',
            'ACoS (%)',
            'Impressions (1d)',
            'Bid',
            'Total Spend (1d)',
            'Total Sales (1d)',
            'Purchases (1d)',

            // 7d
            'Clicks (7d)',
            'CPC (7d)',
            'CTR (%) (7d)',
            'Conversion Rate (%) (7d)',
            'Total Spend (7d)',
            'Total Sales (7d)',
            'Purchases 1d (7d)',
            'ACoS (7d)',
            'Impressions (7d)',

            // 14d
            'Total Spend (14d)',
            'Total Sales (14d)',
            'Purchases 1d (14d)',

            // Recommendations
            'Recommendation',
            'Suggested Bid',
            'AI Suggested Bid',
            'AI Recommendation',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'K' => '0.0%', // CTR (1d)
            'X' => '0.0%', // CTR (7d)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AF1')->getFont()->setBold(true);
        return [];
    }
}
