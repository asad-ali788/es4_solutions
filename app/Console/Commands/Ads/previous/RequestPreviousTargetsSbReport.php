<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use App\Models\AmzAdsReportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RequestPreviousTargetsSbReport extends Command
{
    protected $signature = 'app:request-previous-targets-sb-report';
    protected $description = 'Request historical Amazon Sponsored Brands Targets reports for the last 3 months';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $monthsBack = 3;
        $countries = ['US', 'CA'];
        $retentionCutoff = Carbon::now($marketTz)->subDays(70)->startOfDay();

        foreach ($countries as $country) {
            $profileId = config("amazon_ads.profiles.$country");

            for ($m = 1; $m <= $monthsBack; $m++) {
                $startOfMonth = Carbon::now($marketTz)->subMonthsNoOverflow($m)->startOfMonth();
                $endOfMonth   = $startOfMonth->copy()->endOfMonth();

                for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                    if ($date->lt($retentionCutoff)) {
                        Log::info("⛔ [$country] Skipping {$date->toDateString()} (older than retention).");
                        continue;
                    }

                    $reportDate = $date->toDateString();

                    $exists = AmzAdsReportLog::where('country', $country)
                        ->where('report_type', 'sbTargetclause_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] SB Targets report already exists for $reportDate, skipping.");
                        continue;
                    }

                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $reportDate,
                        $reportDate,
                        $country,
                        'sbTargeting',
                        'sbTargetclause_prev',
                        ['targeting'],
                        [
                            "targetingId",
                            "targetingText",
                            "targetingExpression",
                            "campaignId",
                            "adGroupId",
                            "clicks",
                            "impressions",
                            "cost",
                            "sales",
                            "purchases",
                            "unitsSold",
                            "date",
                        ],
                        'SPONSORED_BRANDS'
                    );

                    sleep(2);
                }
            }

            $this->info("✅ Sponsored Brands Targets reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
