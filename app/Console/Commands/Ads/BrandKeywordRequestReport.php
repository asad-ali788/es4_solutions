<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BrandKeywordRequestReport extends Command
{
    protected $signature = 'app:brand-keyword-request-report {targetDate?}';
    protected $description = 'ADS: Request SB Keyword Performance Report [US/CA]';

    public function handle(AmazonAdsService $client)
    {
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
            $this->requestReportForCountry($client, config('amazon_ads.profiles.US'), $date, 'US', 'sbTargeting_SB_update');
            $this->requestReportForCountry($client, config('amazon_ads.profiles.CA'), $date, 'CA', 'sbTargeting_SB_update');
            $this->info("✅ Sponsored Brands Keyword reports requested for $date.");
            return;
        }
        
        // 📅 Standard: Sub 1 day
        $date = Carbon::now($marketTz)->subDay()->toDateString();
        $this->requestReportForCountry($client, config('amazon_ads.profiles.US'), $date, 'US');
        $this->requestReportForCountry($client, config('amazon_ads.profiles.CA'), $date, 'CA');

        // 📅 Update: Sub 2 days
        $updateDate = Carbon::now($marketTz)->subDays(2)->toDateString();
        $this->requestReportForCountry($client, config('amazon_ads.profiles.US'), $updateDate, 'US', 'sbTargeting_SB_update');
        $this->requestReportForCountry($client, config('amazon_ads.profiles.CA'), $updateDate, 'CA', 'sbTargeting_SB_update');

        $this->info("✅ Sponsored Brands Keyword reports requested for US and CA.");
    }

    private function requestReportForCountry(AmazonAdsService $client, string $profileId, string $date, string $country, string $reportTypeOverride = null): void
    {
        $reportType = $reportTypeOverride ?? 'sbTargeting_SB';

        $exists = AmzAdsReportLog::where('country', $country)
            ->where('report_type', $reportType)
            ->where('report_date', $date)
            ->whereIn('report_status', ['IN_PROGRESS', 'COMPLETED'])
            ->exists();

        if ($exists) {
            $this->info("⚠️ [$country] Report already exists for date: {$date}");
            return;
        }

        $data = [
            "name" => "",
            "startDate" => $date,
            "endDate" => $date,
            "configuration" => [
                "adProduct" => "SPONSORED_BRANDS",
                "groupBy" => ["targeting"],
                "columns" => [
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
                "filters" => [
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
                "reportTypeId" => "sbTargeting",
                "timeUnit" => "DAILY",
                "format" => "GZIP_JSON"
            ]
        ];

        $response = $client->requestReport($data, $profileId);

        if ($response['code'] == 200) {
            $res = json_decode($response['response'], true);

            AmzAdsReportLog::create([
                'country'       => $country,
                'report_type'   => $reportType,
                'report_id'     => $res['reportId'],
                'report_status' => 'IN_PROGRESS',
                'r_iteration'   => 0,
                'report_date'   => $res['startDate'],
                'added'         => now(),
            ]);

            $this->info("✅ [$country] Report successfully requested for {$date}.");
        } elseif ($response['code'] == 425) {
            Log::channel('ads')->warning("⚠️ [$country] Brands Keyword detected [" . ($response['response']['detail'] ?? 'No details') . "].");
        } else {
            $this->error("❌ [$country] Failed to request report. Code: {$response['code']}");
        }

        sleep(3);
    }
}
