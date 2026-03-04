<?php

namespace App\Exports;

use App\Models\AmzTargetRecommendation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TargetRecommendationsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $date;

    public function __construct($date = null)
    {
        // Default to yesterday if no date passed
        $this->date = $date ?? now(config('timezone.market'))->subDay()->toDateString();
    }

    /**
     * Fetch data for export
     */
    public function collection(): Collection
    {
        return AmzTargetRecommendation::whereDate('date', $this->date)
            ->get([
                'targeting_id',
                'targeting_text',
                'country',
                'campaign_id',
                'ad_group_id',
                'date',
                'clicks',
                'cpc',
                'ctr',
                'orders',
                'total_spend',
                'total_sales',
                'conversion_rate',
                'acos',
                'campaign_types',
                'recommendation',
                'ai_recommendation',
                'impressions',
                'suggested_bid',
                'ai_suggested_bid',
                's_bid_min',
                's_bid_range',
                's_bid_max',
                'ai_status',
            ]);
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'Targeting ID',
            'Targeting Text',
            'Country',
            'Campaign ID',
            'Ad Group ID',
            'Date',
            'Clicks',
            'CPC',
            'CTR (%)',
            'Orders',
            'Total Spend',
            'Total Sales',
            'Conversion Rate (%)',
            'ACoS (%)',
            'Campaign Type',
            'Recommendation',
            'Ai Recommendation',
            'Impressions',
            'Suggested Bid',
            'AI Suggested Bid',
            'Suggested Bid Min',
            'Suggested Bid Range',
            'Suggested Bid Max',
            'AI Status',
        ];
    }

    /**
     * Apply styles to sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:X1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ],
            'fill' => [
                'fillType' => 'solid',
                'color'    => ['rgb' => 'D9E1F2'],
            ],
        ]);

        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Targeting ID
            'B' => 30, // Targeting Text
            'C' => 12, // Country
            'D' => 15, // Campaign ID
            'E' => 15, // Ad Group ID
            'F' => 12, // Date
            'G' => 10, // Clicks
            'H' => 10, // CPC
            'I' => 10, // CTR
            'J' => 10, // Orders
            'K' => 15, // Spend
            'L' => 15, // Sales
            'M' => 18, // Conversion Rate
            'N' => 12, // ACoS
            'O' => 15, // Campaign Type
            'P' => 45, // Recommendation
            'Q' => 45, // Ai Recommendation
            'R' => 15, // Impressions
            'S' => 15, // Suggested Bid
            'T' => 15, // AI Suggested Bid
            'U' => 15, // Suggested Bid Min
            'V' => 20, // Suggested Bid Range
            'W' => 15, // Suggested Bid Max
            'X' => 15, // AI Status
        ];
    }

    /**
     * Define column formats
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // Targeting ID as text
            'D' => NumberFormat::FORMAT_TEXT, // Campaign ID as text
            'E' => NumberFormat::FORMAT_TEXT, // Ad Group ID as text
            'H' => NumberFormat::FORMAT_NUMBER_00, // CPC
            'I' => NumberFormat::FORMAT_NUMBER_00, // CTR
            'M' => NumberFormat::FORMAT_NUMBER_00, // Conversion Rate
            'N' => NumberFormat::FORMAT_NUMBER_00, // ACoS
            'K' => NumberFormat::FORMAT_NUMBER_00, // Total Spend
            'L' => NumberFormat::FORMAT_NUMBER_00, // Total Sales
            'S' => NumberFormat::FORMAT_NUMBER_00, // Suggested Bid
            'T' => NumberFormat::FORMAT_NUMBER_00, // AI Suggested Bid
            'U' => NumberFormat::FORMAT_NUMBER_00, // Suggested Bid Min
            'W' => NumberFormat::FORMAT_NUMBER_00, // Suggested Bid Max
        ];
    }
}
