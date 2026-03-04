<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;

class RequestPreviousProductReports extends Command
{
    protected $signature = 'app:request-previous-product-reports';
    protected $description = 'Request historical Amazon Sponsored Products Product reports for the last 3 months';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $monthsBack = 3;
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
                        ->where('report_type', 'spAdvertisedProduct_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] Product report already exists for $reportDate, skipping.");
                        continue;
                    }

                    Log::info("📡 [$country] Requesting product report for $reportDate");

                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $reportDate,
                        $reportDate,
                        $country,
                        'spAdvertisedProduct',
                        'spAdvertisedProduct_prev',
                        ['advertiser'],
                        [
                            "adId", "adGroupId", "campaignId",
                            "impressions", "clicks", "cost",
                            "purchases1d", "purchases7d", "purchases30d",
                            "sales1d", "sales7d", "sales30d",
                            "advertisedSku", "advertisedAsin", "date"
                        ],
                        'SPONSORED_PRODUCTS'
                    );

                    sleep(3); 
                }
            }

            $this->info("✅ Product reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
