<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;

class TargetsSdRequestReport extends Command
{
    protected $signature = 'app:targets-sd-request-report';
    protected $description = 'Generate SD Targeting Daily Report';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $date = Carbon::now($marketTz)->subDay()->toDateString();

        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $date, 'US');

        $this->info("✅ Sponsored Display Targeting reports requested for US and CA.");
    }

    private function requestReportForCountry(AmazonAdsService $clients, AdsReportsService $adsReportsService, string $profileId, string $date, string $country): void
    {
        $adsReportsService->requestReport(
            $clients,
            $profileId,
            $date,
            $date,
            $country,
            'sdTargeting',
            'sdTargeting',
            ['targeting','matchedTarget'],
            [
                "adGroupId",
                "adGroupName",
                "adKeywordStatus",
                "campaignId",
                "campaignName",
                "campaignBudgetCurrencyCode",
                "clicks",
                "impressions",
                "cost",
                "sales",
                "purchases",
                "unitsSold",
                "date",
                "targetingExpression",
                "targetingId",
                "targetingText",
            ],
            'SPONSORED_DISPLAY'
        );
    }
}
