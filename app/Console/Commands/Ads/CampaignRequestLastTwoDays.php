<?php

namespace App\Console\Commands\Ads;

use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CampaignRequestLastTwoDays extends Command
{
    protected $signature = 'app:campaign-request-last-two-days';
    protected $description = 'ADS: Request SP/SB/SD Campaign Reports - Last 2 Days [US/CA]';

    public function handle(AmazonAdsService $client, AdsReportsService $service)
    {
        $marketTz = config('timezone.market');

        // D-1 and D-2
        $dates = [
            Carbon::now($marketTz)->subDay()->toDateString(),
            Carbon::now($marketTz)->subDays(2)->toDateString(),
        ];

        $countries = ['US', 'CA'];

        // master config for all three: SP, SB, SD
        $reportConfigs = [
            'spCampaigns' => [
                'type'      => 'spCampaigns',
                'adProduct' => 'SPONSORED_PRODUCTS',
                'groupBy'   => ['campaign', 'adGroup'],
                'columns'   => [
                    'adGroupId',
                    'campaignId',
                    'impressions',
                    'clicks',
                    'cost',
                    'purchases1d',
                    'purchases7d',
                    'campaignBudgetCurrencyCode',
                    'date',
                    'sales7d',
                    'sales1d',
                    'costPerClick',
                    'campaignStatus',
                    'campaignBudgetAmount',
                    'adGroupName',
                    'adStatus'
                ]
            ],
            'sbCampaigns' => [
                'type'      => 'sbCampaigns',
                'adProduct' => 'SPONSORED_BRANDS',
                'groupBy'   => ['campaign'],
                'columns'   => [
                    'campaignId',
                    'impressions',
                    'clicks',
                    'cost',
                    'purchases',
                    'unitsSold',
                    'campaignBudgetCurrencyCode',
                    'date',
                    'sales',
                    'campaignStatus',
                    'campaignBudgetAmount'
                ]
            ],
            'sdCampaigns' => [
                'type'      => 'sdCampaigns',
                'adProduct' => 'SPONSORED_DISPLAY',
                'groupBy'   => ['campaign'],
                'columns'   => [
                    'campaignId',
                    'campaignStatus',
                    'campaignBudgetAmount',
                    'campaignBudgetCurrencyCode',
                    'impressions',
                    'clicks',
                    'cost',
                    'sales',
                    'purchases',
                    'unitsSold',
                    'date'
                ]
            ],
        ];

        foreach ($dates as $index => $date) {
            foreach ($countries as $country) {

                $profileId = config("amazon_ads.profiles.$country");

                foreach ($reportConfigs as $key => $conf) {

                    $logType = "{$key}_Last_two_days_" . ($index + 1);

                    $service->requestReport(
                        client: $client,
                        profileId: $profileId,
                        startDate: $date,
                        endDate: $date,
                        country: $country,
                        reportType: $conf['type'],
                        reportLogType: $logType,
                        groupBy: $conf['groupBy'],
                        columns: $conf['columns'],
                        adProduct: $conf['adProduct']
                    );

                    $this->info("Requested [$key][$country][$date] log: $logType");
                }
            }
        }

        $this->info("✔ All last-two-days reports (SP, SB, SD) requested successfully.");
    }
}
