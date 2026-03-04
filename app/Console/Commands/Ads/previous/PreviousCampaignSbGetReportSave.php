<?php

namespace App\Console\Commands\Ads\Previous;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AmzAdsCampaignSBPerformanceReport;

class PreviousCampaignSbGetReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:previous-campaign-sb-get-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $client)
    {
        ini_set('memory_limit', '10240M');
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            logger()->info("📥 Processing {$country} Sponsored Brands Previous Reports...");

            $logs = AmzAdsReportLog::where('country', $country)
                ->where('report_type', 'sbCampaigns_prev')
                ->where('report_status', 'IN_PROGRESS')
                ->orderBy('report_date', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                Log::info("ℹ️ No pending SB reports found for {$country}.");
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
                    if ($responseData['status'] === 'COMPLETED') {
                        $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                        $reportRows = json_decode($downloaded['response'], true);

                        if (empty($reportRows)) {
                            Log::warning("⚠️ [$country][$dateStr] Empty report data.");
                            continue;
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
                                'date'        => isset($item['date']) ? Carbon::parse($item['date']) : null,
                                'country'     => $country,
                            ];
                        }

                        foreach (array_chunk($records, 1000) as $chunk) {
                            AmzAdsCampaignSBPerformanceReport::insert($chunk);
                        }

                        $reportLog->update(['report_status' => 'COMPLETED']);
                        Log::info("✅ [$country][$dateStr] SB Report saved and marked as COMPLETED.");
                    } elseif ($responseData['status'] === 'PENDING') {
                        $reportLog->increment('r_iteration');
                        Log::info("⏳ [$country][$dateStr] Still PENDING. Iteration: {$reportLog->r_iteration}");
                    } else {
                        Log::warning("⚠️ [$country][$dateStr] Unknown status: " . $responseData['status']);
                    }
                } catch (\Throwable $e) {
                    Log::error("💥 [$country][$dateStr] SB Report processing error: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // Memory cleanup
                unset($response, $responseData, $downloaded, $reportRows, $records);
                gc_collect_cycles();

                sleep(2); // Be kind to the API
            }
        }

        Log::info("🚀 Completed all SB historical reports for US & CA.");
    }
}
