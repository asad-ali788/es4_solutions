<?php

namespace App\Exports;

use App\Models\AmzAdsCampaignPerformanceReport;
use App\Models\AmzAdsCampaignSBPerformanceReport;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzAdsCampaignPerformanceReportSd;
use App\Models\AmzCampaignsSd;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdsPerformanceByProductCampaignExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $country;

    public function __construct($country = 'US')
    {
        $this->country = $country;
    }

    public function collection(): Collection
    {
        $startDate = now()->subDays(6)->toDateString();
        $endDate   = now()->toDateString();

        // SP Campaigns
        $spCampaigns = AmzAdsCampaignPerformanceReport::where('c_status', 'ENABLED')
            ->where('country', $this->country)
            ->whereBetween('c_date', [$startDate, $endDate])
            ->selectRaw('campaign_id, SUM(clicks) as clicks, SUM(cost) as spend, SUM(purchases7d) as orders, SUM(sales7d) as sales')
            ->groupBy('campaign_id')
            ->get();

        // SB Campaigns
        $sbCampaigns = AmzAdsCampaignSBPerformanceReport::where('c_status', 'ENABLED')
            ->where('country', $this->country)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('campaign_id, SUM(clicks) as clicks, SUM(cost) as spend, SUM(purchases) as orders, SUM(sales) as sales')
            ->groupBy('campaign_id')
            ->get();

        // SD Campaigns
        $sdCampaigns = AmzAdsCampaignPerformanceReportSd::where('campaign_status', 'ENABLED')
            ->where('country', $this->country)
            ->whereBetween('c_date', [$startDate, $endDate])
            ->selectRaw('campaign_id, SUM(clicks) as clicks, SUM(cost) as spend, SUM(purchases) as orders, SUM(sales) as sales')
            ->groupBy('campaign_id')
            ->get();

        // Merge campaigns
        $campaigns = $spCampaigns->concat($sbCampaigns)->concat($sdCampaigns);

        $rows = collect();

        foreach ($campaigns as $campaign) {
            $campaignName = 'Unknown Campaign';

            $spCampaign = AmzCampaigns::where('campaign_id', $campaign->campaign_id)->first();
            if ($spCampaign) {
                $campaignName = $spCampaign->campaign_name;
            } else {
                $sbCampaign = AmzCampaignsSb::where('campaign_id', $campaign->campaign_id)->first();
                if ($sbCampaign) {
                    $campaignName = $sbCampaign->campaign_name;
                } else {
                    $sdCampaign = AmzCampaignsSd::where('campaign_id', $campaign->campaign_id)->first();
                    if ($sdCampaign) {
                        $campaignName = $sdCampaign->campaign_name;
                    }
                }
            }

            $acos = calculateACOSExcel($campaign->spend, $campaign->sales);

            $rows->push([
                'campaign_name' => $campaignName,
                'campaign_id'   => $campaign->campaign_id, 
                'clicks'        => $campaign->clicks,
                'spend'         => $campaign->spend,
                'orders'        => $campaign->orders,
                'sales'         => $campaign->sales,
                'acos'          => round($acos, 4),
            ]);
        }

        // Totals row
        $totals = [
            'campaign_name' => 'Total',
            'campaign_id'   => $rows->count(),
            'clicks'        => $rows->sum('clicks'),
            'spend'         => $rows->sum('spend'),
            'orders'        => $rows->sum('orders'),
            'sales'         => $rows->sum('sales'),
            'acos'          => calculateACOSExcel($rows->sum('spend'), $rows->sum('sales')),
        ];

        $rows->push($totals);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Campaign Name',
            'Campaign ID',  
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
            'A' => 60,
            'B' => 25,  // 👈 Wider for campaign_id
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
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
        $sheet->getStyle("A{$lastRow}:G{$lastRow}")->getFont()->setBold(true);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_PERCENTAGE_00, // ACoS
            'B' => NumberFormat::FORMAT_NUMBER,        // Campaign ID
        ];
    }
}
