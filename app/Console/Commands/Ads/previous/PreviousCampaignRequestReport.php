<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;

class PreviousCampaignRequestReport extends Command
{
    protected $signature = 'app:previous-campaign-request-report';
    protected $description = 'Request previous Sponsored Brands campaign reports';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $monthsBack = 2;
        $countries = ['US', 'CA'];
        $retentionCutoff = Carbon::now($marketTz)->subDays(60)->startOfDay();

        foreach ($countries as $country) {
            $profileId = config("amazon_ads.profiles.$country");

            for ($m = 1; $m <= $monthsBack; $m++) {
                $startOfMonth = Carbon::now($marketTz)->subMonthsNoOverflow($m)->startOfMonth();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                    if ($date->lt($retentionCutoff)) {
                        Log::info("⛔ [$country] Skipping {$date->toDateString()}, older than retention window.");
                        continue;
                    }

                    $reportDate = $date->toDateString();

                    $exists = AmzAdsReportLog::where('country', $country)
                        ->where('report_type', 'sbCampaigns_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] Campaign SB report already exists for $reportDate, skipping.");
                        continue;
                    }

                    Log::info("📡 [$country] Requesting Campaign SB report for $reportDate");

                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $reportDate,
                        $reportDate,
                        $country,
                        'sbCampaigns',
                        'sbCampaigns_prev',
                        ['campaign'],
                        [
                            'campaignId',
                            'impressions',
                            'clicks',
                            'cost',
                            'purchases',
                            'unitsSold',
                            'campaignBudgetCurrencyCode',
                            'sales',
                            'date',
                            'campaignStatus',
                            'campaignBudgetAmount',
                        ],
                        'SPONSORED_BRANDS'
                    );

                    sleep(3); // Respectful delay
                }
            }

            $this->info("✅ Sponsored Brands campaign reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
