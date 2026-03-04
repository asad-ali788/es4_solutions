<?php

namespace App\Exports;


use App\Traits\HasFilteredAdsPerformance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KeywordRecommendationsLast7DaysExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use HasFilteredAdsPerformance;

    public function chunkSize(): int
    {
        return 500;
    }

    public function query()
    {
        return $this->getLast7DaysKeywordsQuery()
            ->orderBy('kr.keyword_id')
            ->orderBy('kr.campaign_id');
    }

    public function map($row): array
    {
        $round = fn($v, $p = 4) => $v !== null ? round((float)$v, $p) : 0;
        $relatedAsin = $row->related_asin ?: $row->asin ?: '';

        return [
            (string)$row->keyword_id,
            (string)$row->campaign_id,
            $row->c_name ?? $row->sb_c_name,
            $relatedAsin,
            $row->keyword,
            $row->country,

            $row->clicks,
            $row->cpc,
            $round($row->ctr),
            $round($row->conversion_rate),
            $round($row->acos),
            $row->impressions,
            $row->bid,
            $row->total_spend,
            $row->total_sales,
            $row->purchases1d,

            $row->clicks_7d,
            $row->cpc_7d,
            $round($row->ctr_7d),
            $round($row->conversion_rate_7d),
            $row->total_spend_7d,
            $row->total_sales_7d,
            $row->purchases1d_7d,
            $round($row->acos_7d),
            $row->impressions_7d,

            $row->total_spend_14d,
            $row->total_sales_14d,
            $row->purchases1d_14d,
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
            'Country',
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
            'Clicks (7d)',
            'CPC (7d)',
            'CTR (%) (7d)',
            'Conversion Rate (%) (7d)',
            'Total Spend (7d)',
            'Total Sales (7d)',
            'Purchases 1d (7d)',
            'ACoS (7d)',
            'Impressions (7d)',
            'Total Spend (14d)',
            'Total Sales (14d)',
            'Purchases 1d (14d)',
        ];
    }
}
