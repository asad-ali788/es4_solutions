<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\AmzAdsTargetsPerformanceReportSb;
use App\Models\TempAmzTargetsPerformanceReportSb;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TargetsSbGetReportSaveJob implements ShouldQueue
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
        $reportType = $this->isTodayReport ? 'sbTargetingClause_daily' : 'sbTargetingClause';
        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[TargetsSbGetReportSave][{$this->country}] No SB report in progress.");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            if ($response['code'] != 200) return;

            $responseData = json_decode($response['response'], true);

            if ($responseData['status'] === 'COMPLETED') {
                if ($this->isTodayReport) $client->deleteReport($reportLog->report_id);

                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);

                if (empty($reportRows)) return;

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
                        TempAmzTargetsPerformanceReportSb::updateOrCreate(
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
                        AmzAdsTargetsPerformanceReportSb::insert($chunk);
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[TargetsSbGetReportSave][{$this->country}] Job failed: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
        }
    }
}
