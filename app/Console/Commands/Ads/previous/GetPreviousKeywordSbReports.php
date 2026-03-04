<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsKeywordPerformanceReportSb;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousKeywordSbReports extends Command
{
    protected $signature = 'app:get-previous-brand-keyword-reports';
    protected $description = 'Fetch and save completed Amazon Sponsored Brands Keyword reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');

        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'sbTargeting_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SB keyword reports for $country.");
                continue;
            }

            foreach ($logs as $log) {
                $dateStr = Carbon::parse($log->report_date)->toDateString();
                Log::info("📅 Processing SB keyword report for $country on $dateStr");

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
                            Log::warning("⚠️ Empty SB keyword report for $dateStr.");
                            continue;
                        }

                        $rows = [];
                        foreach ($reportData as $item) {
                            $rows[] = [
                                'campaign_id'     => $item['campaignId'] ?? null,
                                'ad_group_id'     => $item['adGroupId'] ?? null,
                                'keyword_id'      => $item['keywordId'] ?? null,
                                'cost'            => $item['cost'] ?? null,
                                'sales1d'         => $item['sales'] ?? null, // Not available in SB report
                                'sales7d'         => null,
                                'sales30d'        => null,
                                'purchases1d'     => $item['purchases'] ?? null,
                                'purchases7d'     => null,
                                'purchases30d'    => null,
                                'clicks'          => $item['clicks'] ?? null,
                                'impressions'     => $item['impressions'] ?? null,
                                'keyword_bid'     => $item['keywordBid'] ?? null,
                                'targeting'       => null, // Not available in SB
                                'keyword_text'    => $item['keywordText'] ?? null,
                                'match_type'      => $item['matchType'] ?? null,
                                'c_date'          => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'         => $country ?? "",
                                'added'           => now(),
                            ];
                        }

                        foreach (array_chunk($rows, 1000) as $chunk) {
                            AmzAdsKeywordPerformanceReportSb::insert($chunk);
                        }

                        $log->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ Saved SB keyword report for $dateStr, marked as COMPLETED.");
                    } elseif ($responseData['status'] === 'PENDING') {
                        $log->increment('r_iteration');
                        Log::info("⏳ SB keyword report still pending for $dateStr. Iteration: {$log->r_iteration}");
                    } else {
                        Log::warning("⚠️ Unknown SB keyword report status for $dateStr: " . $responseData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 Error while processing SB keyword report for $dateStr: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                sleep(2);
                unset($response, $responseData, $download, $reportData, $rows);
                gc_collect_cycles();
            }

            Log::info("✅ Done processing SB keyword reports for $country.");
        }
    }
}
