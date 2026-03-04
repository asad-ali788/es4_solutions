<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;

class RequestPreviousAdsReports extends Command
{
    protected $signature = 'app:request-previous-ads-reports';
    protected $description = 'Request SP Campaign reports for the past 3 months';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $monthsBack = 3;
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $profileId = config("amazon_ads.profiles.$country");

            for ($m = 1; $m <= $monthsBack; $m++) {
                $startOfMonth = Carbon::now($marketTz)->subMonthsNoOverflow($m)->startOfMonth();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                    $reportDate = $date->startOfDay()->toDateTimeString();

                    $exists = AmzAdsReportLog::where('country', $country)
                        ->where('report_type', 'spCampaigns_prev')
                        ->whereDate('report_date', $date->toDateString())
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] Report already exists for $reportDate, skipping.");
                        continue;
                    }

                    Log::info("📡 [$country] Requesting report for $reportDate");

                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $date->toDateString(),
                        $date->toDateString(),
                        $country,
                        'spCampaigns',          // reportTypeId
                        'spCampaigns_prev',     // report_log type
                        ['campaign', 'adGroup'],
                        [
                            'adGroupId',
                            'campaignId',
                            'impressions',
                            'clicks',
                            'cost',
                            'purchases1d',
                            'purchases7d',
                            'campaignBudgetCurrencyCode',
                            'date',
                            'sales7d',
                            'sales1d',
                            'costPerClick',
                            'campaignStatus',
                            'campaignBudgetAmount',
                            'adGroupName',
                            'adStatus'
                        ],
                        'SPONSORED_PRODUCTS'
                    );

                    sleep(2); // be kind to API
                }
            }

            echo "✅ Campaign reports requested for last {$monthsBack} month(s) for $country.\n";
        }
    }
}
