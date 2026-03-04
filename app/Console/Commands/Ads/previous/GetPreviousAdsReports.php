<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AmzAdsCampaignPerformanceReport;
use phpDocumentor\Reflection\Types\Parent_;

class GetPreviousAdsReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-previous-ads-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $country;

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            $this->country = $country;

            $logs = AmzAdsReportLog::where('country', $this->country)
                ->where('report_type', 'spCampaigns_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending historical campaign reports found for {$this->country}.");
                continue;
            }

            foreach ($logs as $reportLog) {
                $dateStr = Carbon::parse($reportLog->report_date)->toDateString();
                Log::info("📅 Processing report for {$this->country} on {$dateStr}");

                try {
                    $profileId = config("amazon_ads.profiles.{$this->country}");

                    $response = $client->getReport($reportLog->report_id);
                    Log::info("🛰️ Report fetch response code for {$dateStr}: " . $response['code']);

                    if ($response['code'] != 200) {
                        Log::warning("⛔ Invalid response code for {$dateStr}: " . $response['code']);
                        continue;
                    }

                    $responseData = json_decode($response['response'], true);

                    if ($responseData['status'] === 'COMPLETED') {
                        $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                        $reportRows = json_decode($downloaded['response'], true);

                        if (empty($reportRows)) {
                            Log::warning("⚠️ Empty report data for {$dateStr}.");
                            continue;
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
                                'added'        => now(),
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsCampaignPerformanceReport::insert($chunk);
                        }

                        $reportLog->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ Report for {$dateStr} saved and marked as COMPLETED.");
                    } elseif ($responseData['status'] === 'PENDING') {
                        $reportLog->increment('r_iteration');
                        Log::info("⏳ Report for {$dateStr} is still PENDING. Iteration: {$reportLog->r_iteration}");
                    } else {
                        Log::warning("⚠️ Unknown status for report on {$dateStr}: " . $responseData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 Error processing report for {$dateStr}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                sleep(2);
                unset($response, $responseData, $downloaded, $reportRows, $records);
                gc_collect_cycles();
            }

            Log::info("🚀 Done processing available historical campaign reports for {$this->country}.");
        }
    }
}
