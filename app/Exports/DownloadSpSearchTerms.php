<?php

namespace App\Exports;

use App\Traits\SearchTermsTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DownloadSpSearchTerms implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithColumnWidths,
    WithStyles

{
    use SearchTermsTrait;

    protected Request $request;
    protected bool $isAggregated;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $days = $request->days ? (int) $request->days : null;
        $this->isAggregated = $days && in_array($days, [7, 14]);
    }

    public function collection()
    {
        return $this->getSearchTermsQuery($this->request)->get();
    }

    /* -------------------- Mapping -------------------- */

    public function map($row): array
    {
        if ($this->isAggregated) {
            return [
                (string) $row->campaign_id,
                $row->campaign_name,
                (string) $row->keyword_id,
                $row->keyword,
                $row->search_term,
                $row->keyword_type,
                $row->country,
                (string) $row->asin,
                (float) $row->keyword_bid,
                (int) $row->impressions,
                (int) $row->clicks,
                (float) $row->cost_per_click,
                (float) $row->cost,
                (int) $row->purchases_7d,
                (float) $row->sales_7d,
            ];
        }

        // -------- Daily --------
        return [
            (string) $row->campaign_id,
            $row->campaign_name,
            (string) $row->keyword_id,
            (string) $row->ad_group_id,
            $row->keyword,
            $row->search_term,

            $row->country,
            $row->date,
            (string) $row->asin,

            (int) $row->impressions,
            (int) $row->clicks,
            (float) $row->cost_per_click,
            (float) $row->cost,

            (int) $row->purchases_1d,
            (int) $row->purchases_7d,
            (int) $row->purchases_14d,

            (float) $row->sales_1d,
            (float) $row->sales_7d,
            (float) $row->sales_14d,

            (float) $row->campaign_budget_amount,
            (float) $row->keyword_bid,

            $row->keyword_type,
            $row->match_type,
            $row->targeting,
            $row->ad_keyword_status,
        ];
    }

    /* -------------------- Headings -------------------- */

    public function headings(): array
    {
        if ($this->isAggregated) {
            return [
                'Campaign ID',
                'Campaign Name',
                'Keyword ID',
                'Keyword',
                'Search Terms',
                'Keyword Type',
                'Country',
                'ASIN',
                'Keyword Bid',
                'Impressions',
                'Clicks',
                'CPC',
                'Cost',
                'Purchases (7d)',
                'Sales (7d)',
            ];
        }

        return [
            'Campaign ID',
            'Campaign Name',
            'Keyword ID',
            'Ad Group ID',
            'Keyword',
            'Search Term',

            'Country',
            'Date',
            'ASIN',

            'Impressions',
            'Clicks',
            'CPC',
            'Cost',

            'Purchases (1d)',
            'Purchases (7d)',
            'Purchases (14d)',

            'Sales (1d)',
            'Sales (7d)',
            'Sales (14d)',

            'Campaign Budget',
            'Keyword Bid',

            'Keyword Type',
            'Match Type',
            'Targeting',
            'Keyword Status',
        ];
    }

    public function columnWidths(): array
    {
        if ($this->isAggregated) {
            return [
                'A' => 18, // Campaign ID
                'B' => 30, // Campaign Name ✅
                'C' => 18, // Keyword ID
                'D' => 25, // Keyword
                'E' => 50, // Search Terms
                'F' => 24, // Keyword Type
                'G' => 10, // Country
                'H' => 15, // ASIN

                'I' => 12, // Keyword Bid
                'J' => 12, // Impressions
                'K' => 10, // Clicks
                'L' => 10, // CPC
                'M' => 12, // Cost
                'N' => 14, // Purchases
                'O' => 14, // Sales
            ];
        }

        // -------- Daily --------
        return [
            'A' => 18, // Campaign ID
            'B' => 30, // Campaign Name ✅
            'C' => 18, // Keyword ID
            'D' => 18, // Ad Group ID
            'E' => 25, // Keyword
            'F' => 50, // Search Term

            'G' => 8,  // Country
            'H' => 12, // Date
            'I' => 15, // ASIN

            'J' => 12, // Impressions
            'K' => 10, // Clicks
            'L' => 10, // CPC
            'M' => 12, // Cost

            'N' => 14, // Purchases 1d
            'O' => 14, // Purchases 7d
            'P' => 14, // Purchases 14d

            'Q' => 14, // Sales 1d
            'R' => 14, // Sales 7d
            'S' => 14, // Sales 14d

            'T' => 16, // Campaign Budget
            'U' => 14, // Keyword Bid

            'V' => 22, // Keyword Type
            'W' => 20, // Match Type
            'X' => 20, // Targeting
            'Y' => 16, // Keyword Status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->isAggregated) {
            return [
                // Clicks / CPC / Cost → Light Green
                'J' => ['fill' => $this->fill('#C6EFCE')],
                'K' => ['fill' => $this->fill('#C6EFCE')],
                'L' => ['fill' => $this->fill('#C6EFCE')],

                // Purchases → Light Blue
                'M' => ['fill' => $this->fill('#DDEBF7')],

                // Sales → Light Yellow
                'N' => ['fill' => $this->fill('#FFF2CC')],

                // Keyword Bid → Light Orange
                'H' => ['fill' => $this->fill('#FCE4D6')],
            ];
        }

        // -------- Daily --------
        return [
            // Clicks / CPC / Cost → Light Green
            'J' => ['fill' => $this->fill('#C6EFCE')],
            'K' => ['fill' => $this->fill('#C6EFCE')],
            'L' => ['fill' => $this->fill('#C6EFCE')],

            // Purchases → Light Blue
            'M' => ['fill' => $this->fill('#DDEBF7')],
            'N' => ['fill' => $this->fill('#DDEBF7')],
            'O' => ['fill' => $this->fill('#DDEBF7')],

            // Sales → Light Yellow
            'P' => ['fill' => $this->fill('#FFF2CC')],
            'Q' => ['fill' => $this->fill('#FFF2CC')],
            'R' => ['fill' => $this->fill('#FFF2CC')],

            // Keyword Bid → Light Orange
            'T' => ['fill' => $this->fill('#FCE4D6')],
        ];
    }

    private function fill(string $hex): array
    {
        return [
            'fillType' => 'solid',
            'startColor' => [
                'argb' => 'FF' . strtoupper(ltrim($hex, '#')),
            ],
        ];
    }


    /* ---------------- Column Formatting ---------------- */

    public function columnFormats(): array
    {
        if ($this->isAggregated) {
            return [
                'A' => NumberFormat::FORMAT_TEXT,        // Campaign ID
                'B' => NumberFormat::FORMAT_TEXT,        // Keyword ID
                'G' => NumberFormat::FORMAT_TEXT,        // ASIN

                'H' => NumberFormat::FORMAT_NUMBER_00,  // Keyword Bid
                'I' => NumberFormat::FORMAT_NUMBER,     // Impressions
                'J' => NumberFormat::FORMAT_NUMBER,     // Clicks
                'K' => NumberFormat::FORMAT_NUMBER_00,  // CPC
                'L' => NumberFormat::FORMAT_NUMBER_00,  // Cost
                'M' => NumberFormat::FORMAT_NUMBER,     // Purchases
                'N' => NumberFormat::FORMAT_NUMBER_00,  // Sales
            ];
        }

        return [
            'A' => NumberFormat::FORMAT_TEXT,           // Campaign ID
            'B' => NumberFormat::FORMAT_TEXT,           // Keyword ID
            'C' => NumberFormat::FORMAT_TEXT,           // Ad Group ID

            'H' => NumberFormat::FORMAT_TEXT,           // ASIN
            'G' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Date

            'I' => NumberFormat::FORMAT_NUMBER,         // Impressions
            'J' => NumberFormat::FORMAT_NUMBER,         // Clicks
            'K' => NumberFormat::FORMAT_NUMBER_00,      // CPC
            'L' => NumberFormat::FORMAT_NUMBER_00,      // Cost

            'M' => NumberFormat::FORMAT_NUMBER,         // Purchases 1d
            'N' => NumberFormat::FORMAT_NUMBER,         // Purchases 7d
            'O' => NumberFormat::FORMAT_NUMBER,         // Purchases 14d

            'P' => NumberFormat::FORMAT_NUMBER_00,      // Sales 1d
            'Q' => NumberFormat::FORMAT_NUMBER_00,      // Sales 7d
            'R' => NumberFormat::FORMAT_NUMBER_00,      // Sales 14d

            'S' => NumberFormat::FORMAT_NUMBER_00,      // Campaign Budget
            'T' => NumberFormat::FORMAT_NUMBER_00,      // Keyword Bid
        ];
    }
}
