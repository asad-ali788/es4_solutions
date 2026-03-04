<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsTargetsPerformanceReportSd;   // create this model/table
use App\Models\TempAmzTargetsPerformanceReportSd;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TargetsSdGetReportSaveJob implements ShouldQueue
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
        $reportType = $this->isTodayReport ? 'sdTargeting_daily' : 'sdTargeting';

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        logger()->info("📥 Processing {$this->country} Amz Ads SD Targeting Report");

        if (!$reportLog) {
            Log::channel('ads')->info("[TargetsSdGetReportSave][{$this->country}] No SD Targeting report in progress.");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[TargetsSdGetReportSave][{$this->country}] Fetch response code: {$response['code']}");

            if ($response['code'] != 200) {
                Log::channel('ads')->warning("[TargetsSdGetReportSave][{$this->country}] Invalid response code: {$response['code']}");
                return;
            }

            $responseData = json_decode($response['response'], true);


            if ($responseData['status'] === 'COMPLETED') {

                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[TargetsSdGetReportSave][{$this->country}] Report deleted after processing.");
                }

                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);

                if (empty($reportRows)) {
                    $client->deleteReport($reportLog->report_id);

                    Log::channel('ads')->warning("[TargetsSdGetReportSave][{$this->country}] Empty report data. Report deleted.");

                    $reportLog->update(['report_status' => 'EMPTY']);
                    return;
                }

                $records = [];
                foreach ($reportRows as $item) {
                    $records[] = [
                        'targeting_id'   => $item['targetingId'] ?? null,
                        'targeting_text' => $item['targetingText'] ?? null,
                        'targeting_expression' => $item['targetingExpression'] ?? null,
                        'campaign_id'    => $item['campaignId'] ?? null,
                        'ad_group_id'    => $item['adGroupId'] ?? null,
                        'clicks'         => $item['clicks'] ?? null,
                        'impressions'    => $item['impressions'] ?? null,
                        'cost'           => $item['cost'] ?? null,
                        'sales'          => $item['sales'] ?? null,
                        'purchases'      => $item['purchases'] ?? null,
                        'units_sold'     => $item['unitsSold'] ?? null,
                        'c_date'         => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : now()->format('Y-m-d'),
                        'country'        => $this->country,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    foreach ($records as $record) {
                        TempAmzTargetsPerformanceReportSd::updateOrCreate(
                            [
                                'targeting_id' => $record['targeting_id'],
                                'c_date'       => $record['c_date'],
                                'country'      => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsTargetsPerformanceReportSd::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
                Log::channel('ads')->info("[TargetsSdGetReportSave][{$this->country}] Report inserted and marked COMPLETED.");
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("[TargetsSdGetReportSave][{$this->country}] Report still pending. Iteration: {$reportLog->r_iteration}");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[TargetsSdGetReportSave][{$this->country}] Job failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
