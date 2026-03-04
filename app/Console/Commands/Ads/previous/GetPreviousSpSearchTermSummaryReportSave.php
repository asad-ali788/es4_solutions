<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsReportLog;
use App\Models\SpSearchTermSummaryReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetPreviousSpSearchTermSummaryReportSave extends Command
{
    protected $signature = 'app:get-previous-sp-search-term-summary-report-save';
    protected $description = 'Fetch and save completed Amazon SP Search Term Summary reports for past 3 months';

    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            Log::info("📥 Processing {$country} SP Search Term Summary Previous Reports...");

            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'spSearchTermSummary_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SP Search Term Summary reports found for {$country}.");
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

                    if (in_array($responseData['status'], ['COMPLETED', 'SUCCESS'])) {

                        $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                        $reportRows = json_decode($downloaded['response'], true);

                        if (empty($reportRows)) {
                            Log::warning("⚠️ [$country][$dateStr] Empty SP Search Term Summary data.");
                            $reportLog->update(['report_status' => 'EMPTY']);
                            continue;
                        }

                        $records = [];
                        foreach ($reportRows as $item) {
                            $records[] = [
                                'country'               => $country,
                                'date'                  => $dateStr,
                                'campaign_id'           => $item['campaignId'] ?? null,
                                'ad_group_id'           => $item['adGroupId'] ?? null,
                                'keyword_id'            => $item['keywordId'] ?? null,
                                'keyword'               => $item['keyword'] ?? null,
                                'search_term'           => $item['searchTerm'] ?? null,
                                'impressions'           => $item['impressions'] ?? null,
                                'clicks'                => $item['clicks'] ?? null,
                                'cost_per_click'        => $item['costPerClick'] ?? null,
                                'cost'                  => $item['cost'] ?? null,
                                'purchases_1d'          => $item['purchases1d'] ?? null,
                                'purchases_7d'          => $item['purchases7d'] ?? null,
                                'purchases_14d'         => $item['purchases14d'] ?? null,
                                'sales_1d'              => $item['sales1d'] ?? null,
                                'sales_7d'              => $item['sales7d'] ?? null,
                                'sales_14d'             => $item['sales14d'] ?? null,
                                'campaign_budget_amount' => $item['campaignBudgetAmount'] ?? null,
                                'keyword_bid'           => $item['keywordBid'] ?? null,
                                'keyword_type'          => $item['keywordType'] ?? null,
                                'match_type'            => $item['matchType'] ?? null,
                                'targeting'             => $item['targeting'] ?? null,
                                'ad_keyword_status'     => $item['adKeywordStatus'] ?? null,
                                'start_date'            => isset($item['startDate']) ? Carbon::parse($item['startDate']) : null,
                                'end_date'              => isset($item['endDate']) ? Carbon::parse($item['endDate']) : null,
                                'created_at'            => now(),
                                'updated_at'            => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            SpSearchTermSummaryReport::insert($chunk);
                        }

                        $reportLog->update(['report_status' => 'COMPLETED']);
                        $client->deleteReport($reportLog->report_id);

                        Log::info("🗑️ [$country][$dateStr] Report {$reportLog->report_id} deleted from Amazon.");
                        Log::info("✅ [$country][$dateStr] SP Search Term Summary saved and marked as COMPLETED.");
                    } elseif ($responseData['status'] === 'PENDING') {

                        $reportLog->increment('r_iteration');
                        Log::info("⏳ [$country][$dateStr] Still PENDING. Iteration: {$reportLog->r_iteration}");
                    } elseif ($responseData['status'] === 'FAILURE') {

                        $reportLog->update(['report_status' => 'FAILED']);
                        Log::warning("⚠️ [$country][$dateStr] FAILED: " . ($responseData['failureReason'] ?? 'unknown'));
                    } else {
                        Log::warning("⚠️ [$country][$dateStr] Unknown status: {$responseData['status']}");
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 [$country][$dateStr] SP Search Term Summary error: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                unset($response, $responseData, $downloaded, $reportRows, $records);
                gc_collect_cycles();
                sleep(2);
            }
        }

        Log::info("🚀 Completed all SP Search Term Summary historical reports for US & CA.");
    }
}
