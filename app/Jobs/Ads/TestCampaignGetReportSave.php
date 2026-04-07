<?php

namespace App\Jobs\Ads;

use App\Models\TestCampaign;
use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TestCampaignGetReportSave implements ShouldQueue
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
        $reportType = 'testCampaigns';

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        Log::channel('ads')->info("📥 Processing {$this->country} Test Campaign Report");

        if (!$reportLog) {
            Log::channel('ads')->info('TestCampaignGetReportSave No test campaign report in progress.');
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);

            Log::channel('ads')->info('TestCampaignGetReportSave Report fetch response code: ' . $response['code']);

            if ($response['code'] != 200) {
                Log::channel('ads')->warning('TestCampaignGetReportSave Invalid response code: ' . $response['code']);
                return;
            }

            $responseData = json_decode($response['response'], true);
            if ($responseData['status'] === 'COMPLETED') {
                $client->deleteReport($reportLog->report_id);
                Log::channel('ads')->info("[TestCampaignGetReportSave][{$this->country}] Report deleted after processing.");
                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);

                if (empty($reportRows)) {
                    Log::channel('ads')->warning('TestCampaignGetReportSave Report is empty.');
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
                        'budget_gap'   => (isset($item['cost'], $item['campaignBudgetAmount']) && $item['cost'] >= (0.9 * $item['campaignBudgetAmount'])) ? 1 : 0,
                        'c_currency'   => $item['campaignBudgetCurrencyCode'] ?? null,
                        'c_status'     => $item['campaignStatus'] ?? null,
                        'c_date'       => isset($item['date']) ? Carbon::parse($item['date']) : null,
                        'country'      => $this->country,
                        'added'        => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                        'campaign_type'=> $item['campaignType'] ?? 'SP',
                        'report_type'  => $item['reportType'] ?? null,
                        'report_status'=> $item['reportStatus'] ?? null,
                    ];
                }

                foreach (array_chunk($records, 1000) as $chunk) {
                    TestCampaign::insert($chunk);
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info('TestCampaignGetReportSave Report inserted and marked as COMPLETED.');
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("TestCampaignGetReportSave Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error('TestCampaignGetReportSave Job failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
