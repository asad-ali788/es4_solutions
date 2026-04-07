<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use App\Models\AmzAdsReportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CampaignSdRequestReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-sd-request-report {targetDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Request SD Campaign Performance Report [US/CA]';

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
            $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $date, 'CA', 'sdCampaigns_update');
            $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $date, 'US', 'sdCampaigns_update');
            $this->info("✅ Sponsored Display Campaign reports requested for $date.");
            return;
        }

        // 📅 Standard: Sub 1 day
        $date = Carbon::now($marketTz)->subDay()->toDateString();
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $date, 'US');

        // 📅 Update: Sub 2 days (One more day behind)
        $updateDate = Carbon::now($marketTz)->subDays(2)->toDateString();
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $updateDate, 'CA', 'sdCampaigns_update');
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $updateDate, 'US', 'sdCampaigns_update');

        Log::channel('ads')->info('Sponsored Display Campaign reports requested for US and CA.');
    }

    private function requestReportForCountry(AmazonAdsService $clients, AdsReportsService $adsReportsService, string $profileId, string $date, string $country, string $reportTypeOverride = null): void
    {
        $adsReportsService->requestReport(
            $clients,
            $profileId,
            $date,
            $date,
            $country,
            'sdCampaigns',        // reportTypeId
            $reportTypeOverride ?? 'sdCampaigns',  // log type
            ['campaign'],         // groupBy
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
    }
}
