<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsTargetsPerformanceReportSd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousTargetsSdReports extends Command
{
    protected $signature = 'app:get-previous-targets-sd-reports';
    protected $description = 'Fetch and save completed Amazon Sponsored Display Targets reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            Log::info("📥 Processing {$country} Sponsored Display Targets Previous Reports...");

            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'sdTargets_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SD Targets reports found for {$country}.");
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
                                'targeting_id'         => $item['targetingId'] ?? null,
                                'targeting_text'       => $item['targetingText'] ?? null,
                                'targeting_expression' => isset($item['targetingExpression']) ? json_encode($item['targetingExpression']) : null,
                                'campaign_id'          => $item['campaignId'] ?? null,
                                'ad_group_id'          => $item['adGroupId'] ?? null,
                                'clicks'               => $item['clicks'] ?? null,
                                'impressions'          => $item['impressions'] ?? null,
                                'cost'                 => $item['cost'] ?? null,
                                'sales'                => $item['sales'] ?? null,
                                'purchases'            => $item['purchases'] ?? null,
                                'units_sold'           => $item['unitsSold'] ?? null,
                                'c_date'               => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'              => $country,
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsTargetsPerformanceReportSd::insert($chunk);
                        }

                        $reportLog->update(['report_status' => 'COMPLETED']);
                        $client->deleteReport($reportLog->report_id);
                        Log::info("🗑️ [$country][$dateStr] Report {$reportLog->report_id} deleted from Amazon.");
                        Log::info("✅ [$country][$dateStr] SD Targets Report saved and marked as COMPLETED.");
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
                    Log::error("💥 [$country][$dateStr] SD Targets Report error: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                unset($response, $responseData, $downloaded, $reportRows, $records);
                gc_collect_cycles();
                sleep(2);
            }
        }

        Log::info("🚀 Completed all SD Targets historical reports for US & CA.");
    }
}
