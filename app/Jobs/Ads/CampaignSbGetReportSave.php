<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\AmzAdsCampaignSBPerformanceReport;
use App\Models\TempAmzCampaignSBPerformanceReport;
use App\Services\Ads\CampaignPerformanceSnapshotService;

class CampaignSbGetReportSave implements ShouldQueue
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
        $reportType = $this->isTodayReport ? 'sbCampaigns_daily' : 'sbCampaigns';

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        logger()->info("📥 Processing {$this->country} Amz Ads Campaign SB Performance Report");

        if (!$reportLog) {
            Log::channel('ads')->info('CampaignSBGetReportSave No Campaign SB report in progress.');
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info('CampaignSBGetReportSave Report fetch response code: ' . $response['code']);

            if ($response['code'] != 200) {
                Log::channel('ads')->warning('CampaignSBGetReportSave Invalid response code: ' . $response['code']);
                return;
            }

            $responseData = json_decode($response['response'], true);

            if ($responseData['status'] === 'COMPLETED') {

                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[CampaignSBGetReportSave][{$this->country}] Report deleted after processing.");
                }

                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);
                if (empty($reportRows)) {
                    Log::channel('ads')->warning('CampaignSBGetReportSave Report is empty.');
                    return;
                }

                $records = [];
                foreach ($reportRows as $item) {
                    $records[] = [
                        'campaign_id' => $item['campaignId'] ?? null,
                        'cost'        => $item['cost'] ?? null,
                        'clicks'      => $item['clicks'] ?? null,
                        'unitsSold'   => $item['unitsSold'] ?? null,
                        'impressions' => $item['impressions'] ?? null,
                        'purchases'   => $item['purchases'] ?? null,
                        'c_budget'    => $item['campaignBudgetAmount'] ?? null,
                        'c_currency'  => $item['campaignBudgetCurrencyCode'] ?? null,
                        'c_status'    => $item['campaignStatus'] ?? null,
                        'sales'       => $item['sales'] ?? null,
                        'date'        => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : now()->format('Y-m-d'),
                        'country'     => $this->country,
                        'budget_gap'  => (isset($item['cost'], $item['campaignBudgetAmount']) &&
                            $item['cost'] >= (0.9 * $item['campaignBudgetAmount'])
                        ) ? 1 : 0,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    foreach ($records as $record) {
                        $tempRecord = $record;
                        unset($tempRecord['budget_gap']); // drop the field not in table
                        TempAmzCampaignSBPerformanceReport::updateOrCreate(
                            [
                                'campaign_id' => $record['campaign_id'],
                                'date'        => $record['date'],
                                'country'     => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsCampaignSBPerformanceReport::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info('CampaignSBGetReportSave Report inserted and marked as COMPLETED.');
                if ($this->isTodayReport) {
                    $snapshotService->captureDeltaForType('SB');
                }
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("CampaignSBGetReportSave Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error('CampaignSBGetReportSave Job failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
