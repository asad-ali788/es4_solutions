<?php

namespace App\Console\Commands\Ads;

use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProductRequestReportSd extends Command
{
    protected $signature = 'app:product-request-report-sd {targetDate?}';
    protected $description = 'ADS: Request SD Product Performance Report [US/CA]';

    public function handle(AmazonAdsService $client, AdsReportsService $reportService)
    {
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
            $reportLogType = "sdAdvertisedProduct_update";
        } else {
            $date = Carbon::now($marketTz)->subDay()->toDateString();
            $reportLogType = "sdAdvertisedProduct";
        }

        // common config for SD Product report
        $reportType   = "sdAdvertisedProduct";
        // $reportLogType is already defined above dynamically
        $groupBy      = ["advertiser"];
        $columns      = [
            "adGroupId",
            "adId",
            "campaignId",
            "impressions",
            "clicks",
            "cost",
            "purchases",
            "sales",
            "unitsSold",
            "promotedSku",
            "promotedAsin",
            "date"
        ];
        $adProduct    = "SPONSORED_DISPLAY";

        // CA
        $reportService->requestReport(
            $client,
            config('amazon_ads.profiles.CA'),
            $date,
            $date,
            'CA',
            $reportType,
            $reportLogType,
            $groupBy,
            $columns,
            $adProduct
        );

        // US
        $reportService->requestReport(
            $client,
            config('amazon_ads.profiles.US'),
            $date,
            $date,
            'US',
            $reportType,
            $reportLogType,
            $groupBy,
            $columns,
            $adProduct
        );

        $this->info("✅ SD Product reports processed for US and CA.");
    }
}
