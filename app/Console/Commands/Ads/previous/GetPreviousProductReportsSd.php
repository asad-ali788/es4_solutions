<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsProductPerformanceReportSd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousProductReportsSd extends Command
{
    protected $signature = 'app:get-previous-product-reports-sd';
    protected $description = 'Fetch and save completed Amazon Sponsored Display Product reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'sdAdvertisedProduct_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SD product reports for $country.");
                continue;
            }

            foreach ($logs as $log) {
                $dateStr = Carbon::parse($log->report_date)->toDateString();
                Log::info("📅 Processing SD product report for $country on $dateStr");

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
                            Log::warning("⚠️ Empty SD product report for $dateStr.");
                            $log->update(['report_status' => 'EMPTY']);
                            continue;
                        }

                        $records = [];
                        foreach ($rows as $item) {
                            $records[] = [
                                'campaign_id'   => $item['campaignId'] ?? null,
                                'ad_group_id'   => $item['adGroupId'] ?? null,
                                'ad_id'         => $item['adId'] ?? null,
                                'cost'          => $item['cost'] ?? null,
                                'sales'         => $item['sales'] ?? null,
                                'purchases'     => $item['purchases'] ?? null,
                                'units_sold'    => $item['unitsSold'] ?? null,
                                'clicks'        => $item['clicks'] ?? null,
                                'impressions'   => $item['impressions'] ?? null,
                                'sku'           => $item['promotedSku'] ?? null,
                                'asin'          => $item['promotedAsin'] ?? null,
                                'date'          => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'       => $country,
                                'added'         => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsProductPerformanceReportSd::insert($chunk);
                        }

                        $log->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ SD product report saved and marked as COMPLETED for $dateStr.");
                    } elseif ($resData['status'] === 'PENDING') {
                        $log->increment('r_iteration');
                        Log::info("⏳ SD product report pending for $dateStr. Retry: {$log->r_iteration}");
                    } else {
                        Log::warning("⚠️ Unknown status for SD product report on $dateStr: " . $resData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 Error processing SD product report for $dateStr: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                sleep(2);
                unset($response, $resData, $download, $rows, $records);
                gc_collect_cycles();
            }

            Log::info("✅ Finished processing SD product reports for $country.");
        }
    }
}
