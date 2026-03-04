<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsCampaignPerformanceReportSd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousCampaignSdReports extends Command
{
    protected $signature = 'app:get-previous-sd-campaign-reports';
    protected $description = 'Fetch and save completed Amazon Sponsored Display Campaign reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            Log::info("📥 Processing {$country} Sponsored Display Previous Reports...");

            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'sdCampaigns_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SD reports found for {$country}.");
                continue;
            }

            foreach ($logs as $reportLog) {
                $dateStr = Carbon::parse($reportLog->report_date)->toDateString();

                try {
                    $profileId = match ($country) {
                        'US' => config('amazon_ads.profiles.US'),
                        'CA' => config('amazon_ads.profiles.CA'),
                        default => config('amazon_ads.profiles.US'),
                    };

                    $response = $client->getReport($reportLog->report_id, $profileId);
                    Log::info("📡 [$country][$dateStr] Fetch response code: {$response['code']}");

                    if ($response['code'] != 200) {
                        Log::warning("⛔ [$country][$dateStr] Invalid response code: {$response['code']}");
                        continue;
                    }

                    $responseData = json_decode($response['response'], true);

                    if ($responseData['status'] === 'COMPLETED' || $responseData['status'] === 'SUCCESS') {
                        $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                        $reportRows = json_decode($downloaded['response'], true);

                        if (empty($reportRows)) {
                            Log::warning("⚠️ [$country][$dateStr] Empty report data.");
                            $reportLog->update(['report_status' => 'EMPTY']);
                            continue;
                        }

                        $records = [];
                        foreach ($reportRows as $item) {
                            $records[] = [
                                'campaign_id'                  => $item['campaignId'] ?? null,
                                'campaign_status'              => $item['campaignStatus'] ?? null,
                                'campaign_budget_amount'       => $item['campaignBudgetAmount'] ?? null,
                                'campaign_budget_currency_code' => $item['campaignBudgetCurrencyCode'] ?? null,
                                'impressions'                  => $item['impressions'] ?? null,
                                'clicks'                       => $item['clicks'] ?? null,
                                'cost'                         => $item['cost'] ?? null,
                                'sales'                        => $item['sales'] ?? null,
                                'purchases'                    => $item['purchases'] ?? null,
                                'units_sold'                   => $item['unitsSold'] ?? null,
                                'c_date'                       => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'                      => $country,
                                'added'                        => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsCampaignPerformanceReportSd::insert($chunk);
                        }

                        $reportLog->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ [$country][$dateStr] SD Report saved and marked as COMPLETED.");
                    } elseif ($responseData['status'] === 'PENDING') {
                        $reportLog->increment('r_iteration');
                        Log::info("⏳ [$country][$dateStr] Still PENDING. Iteration: {$reportLog->r_iteration}");
                    } elseif ($responseData['status'] === 'FAILURE') {
                        $reportLog->update(['report_status' => 'FAILED']);
                        Log::warning("⚠️ [$country][$dateStr] Report FAILED. Reason: " . ($responseData['failureReason'] ?? 'unknown'));
                    } else {
                        Log::warning("⚠️ [$country][$dateStr] Unknown status: {$responseData['status']}");
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 [$country][$dateStr] SD Report processing error: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                unset($response, $responseData, $downloaded, $reportRows, $records);
                gc_collect_cycles();
                sleep(2); // prevent hitting API too fast
            }
        }

        Log::info("🚀 Completed all SD historical reports for US & CA.");
    }
}
