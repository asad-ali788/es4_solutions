<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsCampaignPerformanceReport;
use App\Models\AmzAdsReportLog;
use App\Models\TempAmzCampaignPerformanceReport;
use App\Services\Ads\CampaignPerformanceSnapshotService;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CampaignGetReportSave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;
    public bool $isTodayReport;

    public function __construct(string $country, bool $isTodayReport = false)
    {
        $this->country = $country;
        $this->isTodayReport = $isTodayReport;
    }

    public function handle(AmazonAdsService $client,
        CampaignPerformanceSnapshotService $snapshotService
    )
    {
        if ($this->isTodayReport) {
            $reportType = 'spCampaigns_daily';
        } else {
            $reportType = 'spCampaigns';
        }

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        Log::channel('ads')->info("📥 Processing {$this->country} Amz Ads Campaign Performance Report");

        if (!$reportLog) {
            Log::channel('ads')->info('CampaignGetReportSave No Campaign report in progress.');
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);

            Log::channel('ads')->info('CampaignGetReportSave Report fetch response code: ' . $response['code']);

            if ($response['code'] != 200) {
                Log::channel('ads')->warning('CampaignGetReportSave Invalid response code: ' . $response['code']);
                return;
            }

            $responseData = json_decode($response['response'], true);
            if ($responseData['status'] === 'COMPLETED') {
                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[CampaignGetReportSave][{$this->country}] Report deleted after processing.");
                }
                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);

                if (empty($reportRows)) {
                    Log::channel('ads')->warning('CampaignGetReportSave Report is empty.');
                    return;
                }

                $records = [];
                foreach ($reportRows as $item) {
                    $records[] = [
                        'campaign_id'  => $item['campaignId'] ?? null,
                        'ad_group_id'  => $item['adGroupId'] ?? null,
                        'cost'         => $item['cost'] ?? null,
                        'sales1d'      => $item['sales1d'] ?? null,
                        'sales7d'      => $item['sales7d'] ?? null,
                        'purchases1d'  => $item['purchases1d'] ?? null,
                        'purchases7d'  => $item['purchases7d'] ?? null,
                        'clicks'       => $item['clicks'] ?? null,
                        'costPerClick' => $item['costPerClick'] ?? null,
                        'c_budget'     => $item['campaignBudgetAmount'] ?? null,
                        'c_currency'   => $item['campaignBudgetCurrencyCode'] ?? null,
                        'c_status'     => $item['campaignStatus'] ?? null,
                        'c_date'       => isset($item['date']) ? Carbon::parse($item['date']) : null,
                        'country'      => $this->country,
                        'budget_gap' => (
                            isset($item['cost'], $item['campaignBudgetAmount']) &&
                            $item['cost'] >= (0.9 * $item['campaignBudgetAmount'])
                        ) ? 1 : 0,
                        'added'        => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    foreach ($records as $record) {
                        $tempRecord = $record;
                        unset($tempRecord['budget_gap']); // drop the field not in table
                        TempAmzCampaignPerformanceReport::updateOrCreate(
                            [
                                'campaign_id' => $record['campaign_id'],
                                'ad_group_id' => $record['ad_group_id'],
                                'c_date'      => $record['c_date'],
                                'country'     => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsCampaignPerformanceReport::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Cache::forget('sales_report_today');
                Cache::forget('sales_report_yesterday');
                Log::channel('ads')->info('CampaignGetReportSave Report inserted and marked as COMPLETED.');
                /**
                 * ✅ SAFELY APPENDED: snapshot capture for SP (today report only)
                 * Does not change your existing logic/flow; it runs after save+completed.
                 */
                if ($this->isTodayReport) {
                    $snapshotService->captureDeltaForType('SP');
                }
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("CampaignGetReportSave Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error('CampaignGetReportSave Job failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
