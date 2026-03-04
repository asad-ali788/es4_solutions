<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsCampaignPerformanceReportSd;
use App\Models\TempAmzCampaignSDPerformanceReport;
use App\Services\Ads\CampaignPerformanceSnapshotService;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SdCampaignGetReportSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;
    public bool $isTodayReport;

    public function __construct(string $country, bool $isTodayReport = false)
    {
        $this->country = $country;
        $this->isTodayReport = $isTodayReport;
    }

    public function handle(
        AmazonAdsService $client,
        CampaignPerformanceSnapshotService $snapshotService
    ) {
        $reportType = $this->isTodayReport ? 'sdCampaigns_daily' : 'sdCampaigns';

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        logger()->info("📥 Processing {$this->country} Amz Ads Campaign SD Performance Report");

        if (!$reportLog) {
            Log::channel('ads')->info("[CampaignSdGetReportSave][{$this->country}] No SD report in progress.");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[CampaignSdGetReportSave][{$this->country}] Fetch response code: {$response['code']}");

            if ($response['code'] != 200) {
                Log::channel('ads')->warning("[CampaignSdGetReportSave][{$this->country}] Invalid response code: {$response['code']}");
                return;
            }

            $responseData = json_decode($response['response'], true);

            if ($responseData['status'] === 'COMPLETED') {

                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[CampaignSdGetReportSave][{$this->country}] Report deleted after processing.");
                }

                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);

                if (empty($reportRows)) {
                    Log::channel('ads')->warning("[CampaignSdGetReportSave][{$this->country}] Empty report data.");
                    return;
                }

                $records = [];
                foreach ($reportRows as $item) {
                    $records[] = [
                        'campaign_id'   => $item['campaignId'] ?? null,
                        'campaign_status' => $item['campaignStatus'] ?? null,
                        'campaign_budget_amount' => $item['campaignBudgetAmount'] ?? null,
                        'campaign_budget_currency_code' => $item['campaignBudgetCurrencyCode'] ?? null,
                        'impressions'   => $item['impressions'] ?? null,
                        'clicks'        => $item['clicks'] ?? null,
                        'cost'          => $item['cost'] ?? null,
                        'sales'         => $item['sales'] ?? null,
                        'purchases'     => $item['purchases'] ?? null,
                        'units_sold'    => $item['unitsSold'] ?? null,
                        'c_date'        => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : now()->format('Y-m-d'),
                        'country'       => $this->country,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    foreach ($records as $record) {
                        $tempRecord = $record;
                        TempAmzCampaignSDPerformanceReport::updateOrCreate(
                            [
                                'campaign_id' => $record['campaign_id'],
                                'c_date'      => $record['c_date'],
                                'country'     => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsCampaignPerformanceReportSd::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info("[CampaignSdGetReportSave][{$this->country}] Report inserted and marked COMPLETED.");
                if ($this->isTodayReport) {
                    $snapshotService->captureDeltaForType('SD');
                }
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("[CampaignSdGetReportSave][{$this->country}] Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[CampaignSdGetReportSave][{$this->country}] Job failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
