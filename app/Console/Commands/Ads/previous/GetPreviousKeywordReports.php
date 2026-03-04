<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsKeywordPerformanceReport;
use Carbon\Carbon;

class GetPreviousKeywordReports extends Command
{
    protected $signature = 'app:get-previous-keyword-reports';
    protected $description = 'Fetch and save completed Amazon Keyword Performance reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');

        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'spTargeting_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending keyword reports for $country.");
                continue;
            }

            foreach ($logs as $log) {
                $dateStr = Carbon::parse($log->report_date)->toDateString();
                Log::info("📅 Processing keyword report for $country on $dateStr");

                try {
                    $profileId = config("amazon_ads.profiles.$country");
                    $response = $client->getReport($log->report_id);

                    if ($response['code'] != 200) {
                        Log::warning("⛔ Bad response code for $dateStr: " . $response['code']);
                        continue;
                    }

                    $responseData = json_decode($response['response'], true);

                    if ($responseData['status'] === 'COMPLETED') {
                        $download = $client->downloadReport($responseData['url'], true, $profileId);
                        $reportData = json_decode($download['response'], true);

                        if (empty($reportData)) {
                            Log::warning("⚠️ Empty keyword report for $dateStr.");
                            continue;
                        }

                        $rows = [];
                        foreach ($reportData as $item) {
                            $rows[] = [
                                'campaign_id'     => $item['campaignId'] ?? null,
                                'ad_group_id'     => $item['adGroupId'] ?? null,
                                'keyword_id'      => $item['keywordId'] ?? null,
                                'cost'            => $item['cost'] ?? null,
                                'sales1d'         => $item['sales1d'] ?? null,
                                'sales7d'         => $item['sales7d'] ?? null,
                                'sales30d'        => $item['sales30d'] ?? null,
                                'purchases1d'     => $item['purchases1d'] ?? null,
                                'purchases7d'     => $item['purchases7d'] ?? null,
                                'purchases30d'    => $item['purchases30d'] ?? null,
                                'clicks'          => $item['clicks'] ?? null,
                                'impressions'     => $item['impressions'] ?? null,
                                'keyword_bid'     => $item['keywordBid'] ?? null,
                                'targeting'       => $item['targeting'] ?? null,
                                'keyword_text'    => $item['keyword'] ?? null,
                                'match_type'      => $item['matchType'] ?? null,
                                'c_date'          => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'         => $country ?? "",
                                'added'           => now(),
                            ];
                        }

                        foreach (array_chunk($rows, 1000) as $chunk) {
                            AmzAdsKeywordPerformanceReport::insert($chunk);
                        }

                        $log->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ Saved keyword report for $dateStr, marked as COMPLETED.");
                    } elseif ($responseData['status'] === 'PENDING') {
                        $log->increment('r_iteration');
                        Log::info("⏳ Keyword report still pending for $dateStr. Iteration: {$log->r_iteration}");
                    } else {
                        Log::warning("⚠️ Unknown keyword report status for $dateStr: " . $responseData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 Error while processing keyword report for $dateStr: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                sleep(2);
                unset($response, $responseData, $download, $reportData, $rows);
                gc_collect_cycles();
            }

            Log::info("✅ Done processing keyword reports for $country.");
        }
    }
}
