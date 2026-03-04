<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductRequestReport extends Command
{
    protected $signature = 'app:product-request-report';
    protected $description = 'Generate SP Product Performance Daily Report';

    public function handle(AmazonAdsService $clients)
    {
        $marketTz = config('timezone.market');
        $date = Carbon::now($marketTz)->subDay()->toDateString();

        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US');

        echo "✅ Product reports processed for US and CA.\n";
    }

    private function requestReportForCountry(AmazonAdsService $clients, string $profileId, string $date, string $country): void
    {
        $data = [
            "name" => "",
            "startDate" => $date,
            "endDate" => $date,
            "configuration" => [
                "adProduct" => "SPONSORED_PRODUCTS",
                "groupBy" => ["advertiser"],
                "columns" => [
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
                "reportTypeId" => "spAdvertisedProduct",
                "timeUnit" => "DAILY",
                "format" => "GZIP_JSON"
            ]
        ];

        $response = $clients->requestReport($data, $profileId);

        if ($response['code'] == 200) {
            $res = json_decode($response['response'], true);

            AmzAdsReportLog::create([
                'country'        => $country,
                'report_type'    => $res['configuration']['reportTypeId'] ?? null,
                'report_id'      => $res['reportId'] ?? null,
                'report_status'  => 'IN_PROGRESS',
                'r_iteration'    => 0,
                'report_date'    => $res['startDate'] ?? null,
                'added'          => now(),
            ]);

            Log::channel('ads')->info("✅ [$country] Product Report requested: " . ($res['reportId'] ?? 'N/A'));
        } elseif ($response['code'] == 425) {
            Log::channel('ads')->warning("⚠️ [$country] Product report detected [" . ($response['response']['detail'] ?? 'No details') . "].");
        } else {
            Log::channel('ads')->error("❌ [$country] Product Report request failed: Code {$response['code']}", [
                'profile_id'    => $profileId,
                'request_body'  => $data,
                'raw_response'  => $response['response'] ?? null,
            ]);
        }

        sleep(3);
    }
}
