<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use App\Models\AmzAdsReportLog;

class RequestDailyAdReports extends Command
{
    protected $signature = 'app:request-daily-reports';
    protected $description = 'ADS: Request All Daily Ad Reports [US/CA] (SP/SB/SD/KW/Targets)';

    public function handle()
    {
        $marketTz = config('timezone.market');
        $date = Carbon::now($marketTz)->toDateString();
        $adsClient = new AmazonAdsService();
        $adsReportsService = new AdsReportsService();
        $countries = ['US', 'CA'];

        $reportTypes = [
            [
                'reportCode'    => 'spAdvertisedProduct',
                'reportLogType' => 'spAdvertisedProduct_daily',
                'adProduct'     => 'SPONSORED_PRODUCTS',
                'groupBy'       => ['advertiser'],
                'columns'       => [
                    "adId",
                    "adGroupId",
                    "campaignId",
                    "impressions",
                    "clicks",
                    "cost",
                    "purchases1d",
                    "purchases7d",
                    "purchases30d",
                    "sales1d",
                    "sales7d",
                    "sales30d",
                    "advertisedSku",
                    "advertisedAsin",
                    "date"
                ],
            ],
            [
                'reportCode'    => 'sbTargeting',
                'reportLogType' => 'sbTargeting_SB_daily',
                'adProduct'     => 'SPONSORED_BRANDS',
                'groupBy'       => ['targeting'],
                'columns'       => [
                    "adGroupId",
                    "campaignId",
                    "keywordId",
                    "matchType",
                    "impressions",
                    "clicks",
                    "cost",
                    "keywordText",
                    "sales",
                    "keywordType",
                    "purchases",
                    "keywordBid",
                    "date"
                ],
                'filters' => [
                    [
                        "field" => "keywordType",
                        "values" => [
                            "TARGETING_EXPRESSION",
                            "TARGETING_EXPRESSION_PREDEFINED",
                            "BROAD",
                            "PHRASE",
                            "EXACT"
                        ]
                    ]
                ],
            ],
            [
                'reportCode'    => 'spCampaigns',
                'reportLogType' => 'spCampaigns_daily',
                'adProduct'     => 'SPONSORED_PRODUCTS',
                'groupBy'       => ['campaign', 'adGroup'],
                'columns'       => [
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
                ],
            ],
            [
                'reportCode'    => 'sbCampaigns',
                'reportLogType' => 'sbCampaigns_daily',
                'adProduct'     => 'SPONSORED_BRANDS',
                'groupBy'       => ['campaign'],
                'columns'       => [
                    'campaignId',
                    'impressions',
                    'clicks',
                    'cost',
                    'purchases',
                    'unitsSold',
                    'campaignBudgetCurrencyCode',
                    'sales',
                    'date',
                    'campaignStatus',
                    'campaignBudgetAmount',
                ],
            ],
            [
                'reportCode'    => 'spTargeting',
                'reportLogType' => 'spTargeting_daily',
                'adProduct'     => 'SPONSORED_PRODUCTS',
                'groupBy'       => ['targeting'],
                'columns'       => [
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
            ],
            [
                'reportCode'    => 'sdCampaigns',
                'reportLogType' => 'sdCampaigns_daily',
                'adProduct'     => 'SPONSORED_DISPLAY',
                'groupBy'       => ['campaign'],
                'columns'       => [
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
            ],
            [
                'reportCode'    => 'sdTargeting',
                'reportLogType' => 'sdTargeting_daily',
                'adProduct'     => 'SPONSORED_DISPLAY',
                'groupBy'       => ['targeting', 'matchedTarget'],
                'columns'       => [
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
            ],
            [
                'reportCode'    => 'sdAdvertisedProduct',
                'reportLogType' => 'sdAdvertisedProduct_daily',
                'adProduct'     => 'SPONSORED_DISPLAY',
                'groupBy'       => ['advertiser'],
                'columns'       => [
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
                ],
            ],
            [
                'reportCode'    => 'sbTargeting',
                'reportLogType' => 'sbTargetingClause_daily',
                'adProduct'     => 'SPONSORED_BRANDS',
                'groupBy'       => ['targeting'],
                'columns'       => [
                    "targetingId",
                    "targetingText",
                    "targetingExpression",
                    "campaignId",
                    "adGroupId",
                    "clicks",
                    "impressions",
                    "cost",
                    "date",
                    "sales",
                    "purchases",
                    "unitsSold"
                ],
            ],
        ];

        foreach ($countries as $country) {
            $profileId = config("amazon_ads.profiles.$country");

            if (!$profileId) {
                $this->warn("⚠️ No profile ID found for $country");
                continue;
            }

            foreach ($reportTypes as $report) {

                // ✅ 1) Skip if already COMPLETED for today
                $completedExists = AmzAdsReportLog::query()
                    ->where('report_type', $report['reportLogType'])
                    ->where('country', $country)
                    ->where('report_date', $date)
                    ->where('report_status', 'IN_PROGRESS')
                    ->exists();

                    if ($completedExists) {
                    $this->info("✔️ Already completed: {$report['reportLogType']} for $country - $profileId");
                    continue;
                }

                // ✅ 2) Handle IN_PROGRESS safely (stale protection)
                $staleMinutes = 180; // adjust based on your avg Amazon report generation time
                $staleCutoff  = now()->subMinutes($staleMinutes);

                $inProgressLog = AmzAdsReportLog::query()
                    ->where('report_type', $report['reportLogType'])
                    ->where('country', $country)
                    ->where('report_date', $date)
                    ->where('report_status', 'IN_PROGRESS')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($inProgressLog) {
                    $lastTouch = $inProgressLog->updated_at ?? $inProgressLog->created_at;

                    // Fresh IN_PROGRESS -> skip (don't request again)
                    if ($lastTouch && $lastTouch->gte($staleCutoff)) {
                        $this->info("⏳ Still IN_PROGRESS (fresh): {$report['reportLogType']} for $country - $profileId");
                        continue;
                    }

                    // Stale IN_PROGRESS -> mark FAILED and retry request
                    $inProgressLog->report_status = 'FAILED';

                    // Optional: only if your table has error_message column
                    if (isset($inProgressLog->error_message)) {
                        $inProgressLog->error_message = "Marked stale after {$staleMinutes} minutes; retrying request.";
                    }

                    $inProgressLog->save();

                    Log::channel('ads')->warning("Stale IN_PROGRESS marked FAILED; retrying request", [
                        'country'     => $country,
                        'profile_id'  => $profileId,
                        'report_type' => $report['reportLogType'],
                        'report_date' => $date,
                        'log_id'      => $inProgressLog->id ?? null,
                    ]);
                }

                // ✅ 3) Request the report
                try {
                    $adsReportsService->requestReport(
                        $adsClient,
                        $profileId,
                        $date,
                        $date,
                        $country,
                        $report['reportCode'],
                        $report['reportLogType'],
                        $report['groupBy'],
                        $report['columns'],
                        $report['adProduct']
                    );

                    $this->info("📤 Requested: {$report['reportLogType']} for $country - $profileId");
                } catch (\Throwable $e) {
                    Log::channel('ads')->error("❌ Failed: {$report['reportLogType']} for $country - $profileId", [
                        'error' => $e->getMessage(),
                    ]);

                    // Optional: mark latest log FAILED if your requestReport created/updated a log row
                    AmzAdsReportLog::query()
                        ->where('report_type', $report['reportLogType'])
                        ->where('country', $country)
                        ->where('report_date', $date)
                        ->where('report_status', 'IN_PROGRESS')
                        ->orderByDesc('updated_at')
                        ->limit(1)
                        ->update([
                            'report_status' => 'FAILED',
                        ]);

                    $this->error("❌ Request failed: {$report['reportLogType']} for $country - $profileId");
                }
            }
        }

        $this->info("🎯 Daily report requests completed.");
    }
}
