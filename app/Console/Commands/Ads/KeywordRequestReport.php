<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KeywordRequestReport extends Command
{
    protected $signature = 'app:keyword-request-report {targetDate?}';
    protected $description = 'ADS: Request SP Keyword Performance Report [US/CA]';

    public function handle(AmazonAdsService $clients)
    {
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
            $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA', 'spTargeting_update');
            $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US', 'spTargeting_update');
            $this->info("✅ Keyword reports processed for $date.");
            return;
        }
        
        // 📅 Standard: Sub 1 day
        $date = Carbon::now($marketTz)->subDay()->toDateString();
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US');

        // 📅 Update: Sub 2 days
        $updateDate = Carbon::now($marketTz)->subDays(2)->toDateString();
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $updateDate, 'CA', 'spTargeting_update');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $updateDate, 'US', 'spTargeting_update');

        echo "✅ Keyword reports processed for US and CA.\n";
    }

    private function requestReportForCountry(AmazonAdsService $clients, string $profileId, string $date, string $country, string $reportTypeOverride = null): void
    {
        $data = [
            "name" => "",
            "startDate" => $date,
            "endDate" => $date,
            "configuration" => [
                "adProduct" => "SPONSORED_PRODUCTS",
                "groupBy" => ["targeting"],
                "columns" => [
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
                "reportTypeId" => "spTargeting",
                "timeUnit" => "DAILY",
                "format" => "GZIP_JSON"
            ]
        ];

        $response = $clients->requestReport($data, $profileId);

        if ($response['code'] == 200) {
            $res = json_decode($response['response'], true);

            AmzAdsReportLog::create([
                'country' => $country,
                'report_type' => $reportTypeOverride ?? ($res['configuration']['reportTypeId'] ?? null),
                'report_id' => $res['reportId'] ?? null,
                'report_status' => 'IN_PROGRESS',
                'r_iteration' => 0,
                'report_date' => $res['startDate'] ?? null,
                'added' => now(),
            ]);

            Log::channel('ads')->info("✅ [$country] Keyword Report requested: " . ($res['reportId'] ?? 'N/A'));
        } elseif ($response['code'] == 425) {
            Log::channel('ads')->warning("⚠️ [$country] Keyword report detected [" . ($response['response']['detail'] ?? 'No details') . "].");
        } else {
            Log::channel('ads')->error("❌ [$country] Keyword Report request failed: Code {$response['code']}", [
                'profile_id' => $profileId,
                'request_body' => $data,
                'raw_response' => $response['response'] ?? null,
            ]);
        }

        sleep(3);
    }
}
