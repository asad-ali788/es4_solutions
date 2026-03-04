<?php

namespace App\Jobs\Ads;

use App\Services\Api\AmazonAdsService;
use App\Services\Ads\AdsReportsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Carbon\Carbon;

class RequestSbPurchasedProductReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $profileId;
    protected string $country;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public int $tries = 5;        // max retries
    public int $backoff = 30;     // base delay between retries

    public function __construct(string $profileId, string $country, Carbon $startDate, Carbon $endDate)
    {
        $this->profileId  = $profileId;
        $this->country    = $country;
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
    }

    public function handle(AmazonAdsService $clients, AdsReportsService $adsReportsService)
    {
        Log::info("📡 [{$this->country}] Requesting SB Purchased Product report for {$this->startDate->toDateString()} - {$this->endDate->toDateString()}");

        try {
            $adsReportsService->requestReport(
                $clients,
                $this->profileId,
                $this->startDate->toDateString(),
                $this->endDate->toDateString(),
                $this->country,
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

            Log::info("✅ [{$this->country}] Report request SUCCESS for {$this->startDate->toDateString()} - {$this->endDate->toDateString()}");
        } catch (RequestException $e) {
            $retryAfter = $e->response?->header('Retry-After');
            $sleepFor   = $retryAfter ? (int) $retryAfter : $this->backoff;

            Log::warning("⚠️ [{$this->country}] Report request failed ({$e->getCode()}): {$e->getMessage()}");
            Log::info("⏳ Retrying in {$sleepFor}s...");

            // re-queue the job with delay
            self::dispatch($this->profileId, $this->country, $this->startDate, $this->endDate)
                ->delay(now()->addSeconds($sleepFor));

            // prevent default retry since we manually re-dispatched
            $this->delete();
        }
    }
}
