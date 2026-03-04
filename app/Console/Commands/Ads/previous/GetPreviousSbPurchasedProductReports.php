<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsSbPurchasedProductReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousSbPurchasedProductReports extends Command
{
    protected $signature = 'app:get-previous-sb-purchased-reports';
    protected $description = 'Fetch and save completed Amazon SB Purchased Product reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');

        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'sbPurchasedProduct_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SB Purchased Product reports for $country.");
                continue;
            }

            foreach ($logs as $log) {
                $dateStr = Carbon::parse($log->report_date)->toDateString();
                Log::info("📅 Processing SB Purchased Product report for $country on $dateStr");

                try {
                    $profileId = config("amazon_ads.profiles.$country");
                    $response = $client->getReport($log->report_id);

                    if ($response['code'] != 200) {
                        Log::warning("⛔ Bad response code for $dateStr: " . $response['code']);
                        continue;
                    }

                    $resData = json_decode($response['response'], true);

                    if ($resData['status'] === 'COMPLETED') {
                        $download = $client->downloadReport($resData['url'], true, $profileId);
                        $rows = json_decode($download['response'], true);

                        if (empty($rows)) {
                            Log::warning("⚠️ Empty SB Purchased Product report for $dateStr.");
                            continue;
                        }

                        $records = [];
                        foreach ($rows as $item) {
                            $records[] = [
                                'campaign_id'   => $item['campaignId'] ?? null,
                                'campaign_name' => $item['campaignName'] ?? null,
                                'ad_group_id'   => $item['adGroupId'] ?? null,
                                'ad_group_name' => $item['adGroupName'] ?? null,
                                'asin'          => $item['purchasedAsin'] ?? null,
                                'product_name'  => $item['productName'] ?? null,
                                'product_cat'   => $item['productCategory'] ?? null,
                                'orders14d'     => $item['orders14d'] ?? null,
                                'sales14d'      => $item['sales14d'] ?? null,
                                'units_sold14d' => $item['unitsSold14d'] ?? null,
                                'ntb_orders14d' => $item['newToBrandOrders14d'] ?? null,
                                'ntb_orders_pct14d' => $item['newToBrandOrdersPercentage14d'] ?? null,
                                'ntb_purchases14d' => $item['newToBrandPurchases14d'] ?? null,
                                'ntb_purchases_pct14d' => $item['newToBrandPurchasesPercentage14d'] ?? null,
                                'ntb_sales14d'  => $item['newToBrandSales14d'] ?? null,
                                'ntb_sales_pct14d' => $item['newToBrandSalesPercentage14d'] ?? null,
                                'ntb_units14d'  => $item['newToBrandUnitsSold14d'] ?? null,
                                'ntb_units_pct14d' => $item['newToBrandUnitsSoldPercentage14d'] ?? null,
                                'c_date'        => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'       => $country,
                                'added'         => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsSbPurchasedProductReport::insert($chunk);
                        }

                        $log->update(['report_status' => 'COMPLETED']);
                        $client->deleteReport($log->report_id);
                        Log::info("✅ SB Purchased Product report saved, marked as COMPLETED, and deleted for $dateStr.");
                    } elseif ($resData['status'] === 'PENDING') {
                        $log->increment('r_iteration');
                        Log::info("⏳ SB Purchased Product report pending for $dateStr. Retry: {$log->r_iteration}");
                    } else {
                        Log::warning("⚠️ Unknown status for SB Purchased Product report on $dateStr: " . $resData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 Error processing SB Purchased Product report for $dateStr: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                sleep(3);
                unset($response, $resData, $download, $rows, $records);
                gc_collect_cycles();
            }

            Log::info("✅ Finished processing SB Purchased Product reports for $country.");
        }
    }
}
