<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use App\Models\AmzAdsReportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RequestPreviousSpSearchTermSummaryReport extends Command
{
    protected $signature = 'app:request-previous-sp-search-term-summary-report';
    protected $description = 'Request historical Amazon Sponsored Products Search Term Summary reports for the last 3 months';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $monthsBack = 3;
        $countries = ['US', 'CA'];
        $retentionCutoff = Carbon::now($marketTz)->subDays(90)->startOfDay();

        $columns = [
            "impressions",
            "clicks",
            "costPerClick",
            "cost",
            "purchases1d",
            "purchases7d",
            "purchases14d",
            "sales1d",
            "sales7d",
            "sales14d",
            "keywordId",
            "keyword",
            "startDate",
            "endDate",
            "searchTerm",
            "campaignId",
            "campaignBudgetAmount",
            "keywordBid",
            "adGroupId",
            "keywordType",
            "matchType",
            "targeting",
            "adKeywordStatus",
        ];

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
                        ->where('report_type', 'spSearchTermSummary_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] SP SearchTerm Summary already exists for $reportDate, skipping.");
                        continue;
                    }

                    $adsReportsService->requestReport(
                        $clients,
                        $profileId,
                        $reportDate,
                        $reportDate,
                        $country,
                        'spSearchTerm',
                        'spSearchTermSummary_prev',
                        ['searchTerm'],
                        $columns,
                        'SPONSORED_PRODUCTS',
                        'SUMMARY'
                    );

                    sleep(2);
                }
            }

            $this->info("✅ SP Search Term Summary reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
