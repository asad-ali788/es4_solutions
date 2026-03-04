<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use App\Models\AmzAdsReportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RequestPreviousSbPurchasedProductReports extends Command
{
    protected $signature = 'app:request-previous-sb-purchased-reports';
    protected $description = 'Request historical Amazon Sponsored Brands Purchased Product reports for the last 4 months';

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        $marketTz   = config('timezone.market');
        $monthsBack = 3;
        $countries  = ['US', 'CA'];
        $retentionCutoff = Carbon::now($marketTz)->subDays(70)->startOfDay();

        foreach ($countries as $country) {
            $profileId = config("amazon_ads.profiles.$country");

            for ($m = 1; $m <= $monthsBack; $m++) {
                $startOfMonth = Carbon::now($marketTz)->subMonthsNoOverflow($m)->startOfMonth();
                $endOfMonth   = $startOfMonth->copy()->endOfMonth();

                for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                    if ($date->lt($retentionCutoff)) {
                        Log::info("⛔ [$country] Skipping {$date->toDateString()} (older than retention).");
                        continue;
                    }

                    $reportDate = $date->toDateString();

                    // skip if already exists
                    $exists = AmzAdsReportLog::where('country', $country)
                        ->where('report_type', 'sbPurchasedProduct_prev')
                        ->whereDate('report_date', $reportDate)
                        ->exists();

                    if ($exists) {
                        Log::info("⏭️ [$country] SB Purchased Product report already exists for $reportDate, skipping.");
                        continue;
                    }

                    try {
                        $adsReportsService->requestReport(
                            $clients,
                            $profileId,
                            $reportDate,
                            $reportDate,
                            $country,
                            'sbPurchasedProduct',
                            'sbPurchasedProduct_prev',
                            ['purchasedAsin'],
                            [
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
                            'SPONSORED_BRANDS'
                        );

                        Log::info("✅ [$country] Requested SB Purchased Product report for $reportDate");
                    } catch (\Throwable $e) {
                        Log::error("❌ [$country] Failed requesting SB Purchased Product report for $reportDate: " . $e->getMessage());
                    }

                    sleep(2); // space requests to avoid throttling
                }
            }

            $this->info("✅ Sponsored Brands Purchased Product reports requested for last {$monthsBack} month(s) for $country.");
        }
    }
}
