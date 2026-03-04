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
    protected $signature = 'app:campaign-sd-request-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate SD Campaign Daily Report';

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz = config('timezone.market');
        $date = Carbon::now($marketTz)->subDay()->toDateString();

        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, $adsReportsService, config('amazon_ads.profiles.US'), $date, 'US');

        Log::channel('ads')->info('Sponsored Display Campaign reports requested for US and CA.');
    }

    private function requestReportForCountry(AmazonAdsService $clients, AdsReportsService $adsReportsService, string $profileId, string $date, string $country): void
    {
        $adsReportsService->requestReport(
            $clients,
            $profileId,
            $date,
            $date,
            $country,
            'sdCampaigns',        // reportTypeId
            'sdCampaigns',  // log type
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
