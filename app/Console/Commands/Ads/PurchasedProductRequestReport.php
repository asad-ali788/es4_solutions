<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurchasedProductRequestReport extends Command
{
    protected $signature = 'app:purchased-product-request-report';
    protected $description = 'Generate SB Purchased Product Daily Report';

    public function handle(AmazonAdsService $clients)
    {
        $marketTz = config('timezone.market');
        $date = Carbon::now($marketTz)->subDay();

        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US');

        $this->info("✅ Purchased Product reports requested for US and CA.");
    }

    private function requestReportForCountry(AmazonAdsService $clients, string $profileId, Carbon $date, string $country): void
    {
        $data = [
            "name"   => "sbPurchasedProduct-{$country}-{$date->toDateString()}",
            "startDate" => $date->toDateString(),
            "endDate"   => $date->toDateString(),
            "configuration" => [
                "adProduct" => "SPONSORED_BRANDS",
                "groupBy" => ["purchasedAsin"],
                "columns" => [
                    "adGroupId",
                    "adGroupName",
                    "attributionType",
                    "budgetCurrency",
                    "campaignBudgetCurrencyCode",
                    "campaignId",
                    "campaignName",
                    "date",
                    "newToBrandOrders14d",
                    "newToBrandOrdersPercentage14d",
                    "newToBrandPurchases14d",
                    "newToBrandPurchasesPercentage14d",
                    "newToBrandSales14d",
                    "newToBrandSalesPercentage14d",
                    "newToBrandUnitsSold14d",
                    "newToBrandUnitsSoldPercentage14d",
                    "orders14d",
                    "productCategory",
                    "productName",
                    "purchasedAsin",
                    "sales14d",
                    "unitsSold14d"
                ],
                "reportTypeId" => "sbPurchasedProduct",
                "timeUnit"     => "DAILY",
                "format"       => "GZIP_JSON"
            ]
        ];

        $response = $clients->requestReport($data, $profileId);

        if ($response['code'] == 200) {
            $res = json_decode($response['response'], true);

            AmzAdsReportLog::create([
                'country'       => $country,
                'report_type'   => $res['configuration']['reportTypeId'] ?? 'sbPurchasedProduct',
                'report_id'     => $res['reportId'] ?? null,
                'report_status' => 'IN_PROGRESS',
                'r_iteration'   => 0,
                'report_date'   => $res['startDate'] ?? null,
                'added'         => now(),
            ]);

            Log::channel('ads')->info("✅ [$country] Purchased Product Report requested: " . ($res['reportId'] ?? 'N/A'));
        } elseif ($response['code'] == 425) {
            Log::channel('ads')->warning("⚠️ [$country] Purchased Product report detected [" . ($response['response']['detail'] ?? 'No details') . "].");
        } else {
            Log::channel('ads')->error("❌ [$country] Purchased Product Report request failed: Code {$response['code']}", [
                'profile_id'   => $profileId,
                'request_body' => $data,
                'raw_response' => $response['response'] ?? null,
            ]);
        }

        sleep(3); // throttle between countries
    }
}
