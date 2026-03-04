<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;

class RequestPreviousKeywordReports extends Command
{
    protected $signature = 'app:request-previous-keyword-reports';
    protected $description = 'Request historical Amazon Sponsored Products Keyword reports for the last 3 months';

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
                    $reportDate = $date->toDateString();

                    $exists = AmzAdsReportLog::where('country', $country)
                        ->where('report_type', 'spTargeting_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] Keyword report already exists for $reportDate, skipping.");
                        continue;
                    }

                    Log::info("📡 [$country] Requesting keyword report for $reportDate");

                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $reportDate,
                        $reportDate,
                        $country,
                        'spTargeting',        // reportType
                        'spTargeting_prev',   // logType
                        ['targeting'],        // groupBy
                        [                     // columns
                            "campaignId",
                            "adGroupId",
                            "keywordId",
                            "matchType",
                            "targeting",
                            "keyword",
                            "impressions",
                            "clicks",
                            "cost",
                            "purchases1d",
                            "purchases7d",
                            "purchases30d",
                            "sales1d",
                            "sales7d",
                            "sales30d",
                            "date",
                            "keywordBid"
                        ],
                        'SPONSORED_PRODUCTS'
                    );

                    sleep(2); // polite
                }
            }

            $this->info("✅ Keyword reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
