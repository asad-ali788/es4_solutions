<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;

class SpSearchTermSummaryRequestReport extends Command
{
    protected $signature = 'app:sp-search-term-summary-request-report {targetDate?}';
    protected $description = 'ADS: Request SP Search Term Summary Report [US/CA]';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
            $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $date, 'CA');
            $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $date, 'US');
            $this->info("✅ Sponsored Products Search Term Summary reports requested for $date.");
            return;
        }

        $date = Carbon::now($marketTz)->subDay()->toDateString();

        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $date, 'US');

        $this->info("✅ Sponsored Products Search Term Summary reports requested for US and CA.");
    }

    private function requestReportForCountry(
        AmazonAdsService $clients,
        AdsReportsService $adsReportsService,
        string $profileId,
        string $date,
        string $country
    ): void {

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
            // "date",
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

        // 👇 Pass extra parameter to specify SUMMARY
        $adsReportsService->requestReport(
            $clients,
            $profileId,
            $date,
            $date,
            $country,
            'spSearchTerm',              // reportTypeId
            'spSearchTermSummary',       // log type
            ['searchTerm'],              // groupBy
            $columns,
            'SPONSORED_PRODUCTS',
            'SUMMARY'                    // <<-- NEW PARAM
        );
    }
}
