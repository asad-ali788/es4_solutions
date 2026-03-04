<?php

namespace App\Services\Seller;

use Illuminate\Support\Facades\Log;

class CampaignService
{
    public function getCampaignReportDataDaily(string $asin): array
    {
        try {
            return getCampaignReportDataDaily($asin, 'asin');
        } catch (\Throwable $e) {
            Log::error('CampaignService@getCampaignReportDataDaily: ' . $e->getMessage());
            return ['sp' => [], 'sb' => [], 'campaignMetrics' => [], 'days' => [], 'dayNames' => []];
        }
    }
}
