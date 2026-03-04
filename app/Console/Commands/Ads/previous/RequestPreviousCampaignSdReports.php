<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use App\Models\AmzAdsReportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RequestPreviousCampaignSdReports extends Command
{
    protected $signature = 'app:request-previous-sd-campaign-reports';
    protected $description = 'Request historical Amazon Sponsored Display Campaign reports for the last 3 months';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $monthsBack = 3;
        $countries = ['US', 'CA'];

        // Define retention cutoff (skip reports older than this)
        $retentionCutoff = Carbon::now($marketTz)->subDays(60)->startOfDay();

        foreach ($countries as $country) {
            $profileId = config("amazon_ads.profiles.$country");

            for ($m = 1; $m <= $monthsBack; $m++) {
                $startOfMonth = Carbon::now($marketTz)->subMonthsNoOverflow($m)->startOfMonth();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {

                    // Skip dates older than retention window
                    if ($date->lt($retentionCutoff)) {
                        Log::info("⛔ [$country] Skipping {$date->toDateString()}, older than retention window.");
                        continue;
                    }

                    $reportDate = $date->toDateString();

                    // Skip if report already exists
                    $exists = AmzAdsReportLog::where('country', $country)
                        ->where('report_type', 'sdCampaigns_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] SD Campaign report already exists for $reportDate, skipping.");
                        continue;
                    }

                    // Request report
                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $reportDate,
                        $reportDate,
                        $country,
                        'sdCampaigns',
                        'sdCampaigns_prev',
                        ['campaign'],
                        [
                            "campaignId",
                            "campaignStatus",
                            "campaignBudgetAmount",
                            "campaignBudgetCurrencyCode",
                            "impressions",
                            "clicks",
                            "cost",
                            "sales",
                            "purchases",
                            "unitsSold",
                            "date"
                        ],
                        'SPONSORED_DISPLAY'
                    );

                    sleep(2); // optional, to avoid hitting API limits
                }
            }

            $this->info("✅ Sponsored Display Campaign reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
