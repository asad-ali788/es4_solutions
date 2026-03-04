<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsProductPerformanceReport;
use App\Models\AmzAdsReportLog;
use App\Models\TempAmzProductPerformanceReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProductGetReportSaveJob implements ShouldQueue
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
        $reportType = $this->isTodayReport ? 'spAdvertisedProduct_daily' : 'spAdvertisedProduct';

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[ProductGetReportSave][{$this->country}] No report found in progress.");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[ProductGetReportSave][{$this->country}] Fetch response code: " . $response['code']);

            if ($response['code'] !== 200) return;

            $data = json_decode($response['response'], true);

            if ($data['status'] === 'COMPLETED') {

                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[ProductGetReportSave][{$this->country}] Report deleted after processing.");
                }

                $download = $client->downloadReport($data['url'], true);
                $rows = json_decode($download['response'], true);

                if (empty($rows)) {
                    Log::channel('ads')->warning("[ProductGetReportSave][{$this->country}] Empty report.");
                    return;
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
                        'country'       => $this->country,
                        'added'         => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    foreach ($records as $record) {
                        TempAmzProductPerformanceReport::updateOrCreate(
                            [
                                'campaign_id' => $record['campaign_id'],
                                'ad_group_id' => $record['ad_group_id'],
                                'ad_id'       => $record['ad_id'],
                                'sku'         => $record['sku'],
                                'c_date'      => $record['c_date'],
                                'country'     => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    // bulk insert for regular report
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsProductPerformanceReport::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info("[ProductGetReportSave][{$this->country}] Processed product performance report.");
            } elseif ($data['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("[ProductGetReportSave][{$this->country}] Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[ProductGetReportSave][{$this->country}] Failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
