<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsSbPurchasedProductReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PurchasedProductGetReportSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;
    public ?string $reportType;

    public function __construct(string $country, ?string $reportType = null)
    {
        $this->country = $country;
        $this->reportType = $reportType;
    }

    public function handle(AmazonAdsService $client)
    {
        $reportType = $this->reportType ?? 'sbPurchasedProduct';
        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[PurchasedProductGetReportSave][{$this->country}] No report found in progress.");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[PurchasedProductGetReportSave][{$this->country}] Fetch response code: " . $response['code']);

            if ($response['code'] !== 200) return;

            $data = json_decode($response['response'], true);

            if (($data['status'] ?? null) === 'SUCCESS' || ($data['status'] ?? null) === 'COMPLETED') {

                $download = $client->downloadReport($data['url'], true);
                $rows = json_decode($download['response'], true);

                if (empty($rows)) {
                    Log::channel('ads')->warning("[PurchasedProductGetReportSave][{$this->country}] Empty report.");
                    return;
                }

                $records = [];
                foreach ($rows as $item) {
                    $records[] = [
                        'campaign_id'          => $item['campaignId'] ?? null,
                        'campaign_name'        => $item['campaignName'] ?? null,
                        'ad_group_id'          => $item['adGroupId'] ?? null,
                        'ad_group_name'        => $item['adGroupName'] ?? null,
                        'asin'                 => $item['purchasedAsin'] ?? null,
                        'product_name'         => $item['productName'] ?? null,
                        'product_cat'          => $item['productCategory'] ?? null,
                        'orders14d'            => $item['orders14d'] ?? null,
                        'sales14d'             => $item['sales14d'] ?? null,
                        'units_sold14d'        => $item['unitsSold14d'] ?? null,
                        'ntb_orders14d'        => $item['newToBrandOrders14d'] ?? null,
                        'ntb_orders_pct14d'    => $item['newToBrandOrdersPercentage14d'] ?? null,
                        'ntb_purchases14d'     => $item['newToBrandPurchases14d'] ?? null,
                        'ntb_purchases_pct14d' => $item['newToBrandPurchasesPercentage14d'] ?? null,
                        'ntb_sales14d'         => $item['newToBrandSales14d'] ?? null,
                        'ntb_sales_pct14d'     => $item['newToBrandSalesPercentage14d'] ?? null,
                        'ntb_units14d'         => $item['newToBrandUnitsSold14d'] ?? null,
                        'ntb_units_pct14d'     => $item['newToBrandUnitsSoldPercentage14d'] ?? null,
                        'c_date'               => isset($item['date']) ? Carbon::parse($item['date']) : null,
                        'country'              => $this->country,
                        'added'                => now(),
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                // bulk upsert in chunks to handle updates/corrections
                foreach (array_chunk($records, 1000) as $chunk) {
                    AmzAdsSbPurchasedProductReport::upsert(
                        $chunk,
                        ['campaign_id', 'ad_group_id', 'asin', 'c_date', 'country'],
                        ['campaign_name', 'ad_group_name', 'product_name', 'product_cat', 'orders14d', 'sales14d', 'units_sold14d', 'ntb_orders14d', 'ntb_orders_pct14d', 'ntb_purchases14d', 'ntb_purchases_pct14d', 'ntb_sales14d', 'ntb_sales_pct14d', 'ntb_units14d', 'ntb_units_pct14d', 'updated_at']
                    );
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info("[PurchasedProductGetReportSave][{$this->country}] Processed Purchased Product Report: {$reportLog->report_id}");

            } else {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("[PurchasedProductGetReportSave][{$this->country}] Report still in progress. Iteration: {$reportLog->r_iteration}");
            }

        } catch (\Throwable $e) {
            Log::channel('ads')->error("[PurchasedProductGetReportSave][{$this->country}] Failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
