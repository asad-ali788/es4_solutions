<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsProductPerformanceReportSd;
use App\Models\TempAmzProductPerformanceReportSd as TempProductPerformanceSd;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProductGetReportSdSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;
    public bool $isTodayReport;

    public function __construct(string $country, bool $isTodayReport = false)
    {
        $this->country = $country;
        $this->isTodayReport = $isTodayReport;
    }

    public function handle(AmazonAdsService $client)
    {
        $reportType = $this->isTodayReport ? 'sdAdvertisedProduct_daily' : 'sdAdvertisedProduct';

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[SdProductGetReportSave][{$this->country}] No report found in progress.");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[SdProductGetReportSave][{$this->country}] Fetch response code: " . $response['code']);

            if ($response['code'] !== 200) return;

            $data = json_decode($response['response'], true);


            if ($data['status'] === 'COMPLETED') {
                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[SdProductGetReportSave][{$this->country}] Report deleted after processing.");
                }

                $download = $client->downloadReport($data['url'], true);
                $rows = json_decode($download['response'], true);

                if (empty($rows)) {
                    Log::channel('ads')->warning("[SdProductGetReportSave][{$this->country}] Empty report.");
                    $reportLog->update(['report_status' => 'EMPTY']);
                    return;
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
                        'date'        => isset($item['date']) ? Carbon::parse($item['date']) : null,
                        'country'       => $this->country,
                        'added'         => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    foreach ($records as $record) {
                        TempProductPerformanceSd::updateOrCreate(
                            [
                                'campaign_id' => $record['campaign_id'],
                                'ad_group_id' => $record['ad_group_id'],
                                'ad_id'       => $record['ad_id'],
                                'sku'         => $record['sku'],
                                'date'      => $record['date'],
                                'country'     => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsProductPerformanceReportSd::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info("[SdProductGetReportSave][{$this->country}] Processed SD product performance report.");
            } elseif ($data['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("[SdProductGetReportSave][{$this->country}] Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[SdProductGetReportSave][{$this->country}] Failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
