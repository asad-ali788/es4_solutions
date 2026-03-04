<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsProductPerformanceReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousProductReports extends Command
{
    protected $signature = 'app:get-previous-product-reports';
    protected $description = 'Fetch and save completed Amazon Product Performance reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');

        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'spAdvertisedProduct_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending product reports for $country.");
                continue;
            }

            foreach ($logs as $log) {
                $dateStr = Carbon::parse($log->report_date)->toDateString();
                Log::info("📅 Processing product report for $country on $dateStr");

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
                            Log::warning("⚠️ Empty product report for $dateStr.");
                            continue;
                        }

                        $records = [];
                        foreach ($rows as $item) {
                            $records[] = [
                                'campaign_id'   => $item['campaignId'] ?? null,
                                'ad_group_id'   => $item['adGroupId'] ?? null,
                                'ad_id'         => $item['adId'] ?? null,
                                'cost'          => $item['cost'] ?? null,
                                'sales1d'       => $item['sales1d'] ?? null,
                                'sales7d'       => $item['sales7d'] ?? null,
                                'sales30d'      => $item['sales30d'] ?? null,
                                'purchases1d'   => $item['purchases1d'] ?? null,
                                'purchases7d'   => $item['purchases7d'] ?? null,
                                'purchases30d'  => $item['purchases30d'] ?? null,
                                'clicks'        => $item['clicks'] ?? null,
                                'impressions'   => $item['impressions'] ?? null,
                                'sku'           => $item['advertisedSku'] ?? null,
                                'asin'          => $item['advertisedAsin'] ?? null,
                                'c_date'        => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'       => $country,
                                'added'         => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsProductPerformanceReport::insert($chunk);
                        }

                        $log->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ Product report saved and marked as COMPLETED for $dateStr.");
                    } elseif ($resData['status'] === 'PENDING') {
                        $log->increment('r_iteration');
                        Log::info("⏳ Product report pending for $dateStr. Retry: {$log->r_iteration}");
                    } else {
                        Log::warning("⚠️ Unknown status for product report on $dateStr: " . $resData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 Error processing product report for $dateStr: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                sleep(2);
                unset($response, $resData, $download, $rows, $records);
                gc_collect_cycles();
            }

            Log::info("✅ Finished processing product reports for $country.");
        }
    }
}
