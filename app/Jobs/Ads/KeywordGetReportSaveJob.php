<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsKeywordPerformanceReport;
use App\Models\AmzAdsReportLog;
use App\Models\TempAmzKeywordPerformanceReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class KeywordGetReportSaveJob implements ShouldQueue
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
        $reportType = $this->isTodayReport ? 'spTargeting_daily' : 'spTargeting';

        $reportLog = AmzAdsReportLog::where([
            'country' => $this->country,
            'report_type' => $reportType,
            'report_status' => 'IN_PROGRESS'
        ])->latest()->first();

        if (!$reportLog) {
            Log::info("🚫 [KeywordGetReportSave] No in-progress report for {$this->country}.");
            return;
        }

        $profileId = match (strtoupper($this->country)) {
            'US' => config('amazon_ads.profiles.US'),
            'CA' => config('amazon_ads.profiles.CA'),
            default => config('amazon_ads.profiles.US'),
        };

        try {
            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::info("[KeywordGetReportSave][{$this->country}] Response code: {$response['code']}");

            if ($response['code'] !== 200) return;

            $data = json_decode($response['response'], true);

            if ($data['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                return;
            }

            if (empty($data['url'])) return;

            $rows = json_decode($client->downloadReport($data['url'], true)['response'], true);
            if (empty($rows)) return;

            $records = array_map(fn($item) => [
                'campaign_id'  => $item['campaignId'] ?? null,
                'ad_group_id'  => $item['adGroupId'] ?? null,
                'keyword_id'   => $item['keywordId'] ?? null,
                'c_date'       => isset($item['date']) ? Carbon::parse($item['date']) : null,
                'country'      => $this->country,
                'cost'         => $item['cost'] ?? null,
                'clicks'       => $item['clicks'] ?? null,
                'impressions'  => $item['impressions'] ?? null,
                'sales1d'      => $item['sales1d'] ?? null,
                'sales7d'      => $item['sales7d'] ?? null,
                'sales30d'     => $item['sales30d'] ?? null,
                'purchases1d'  => $item['purchases1d'] ?? null,
                'purchases7d'  => $item['purchases7d'] ?? null,
                'purchases30d' => $item['purchases30d'] ?? null,
                'keyword_bid'  => $item['keywordBid'] ?? null,
                'targeting'    => $item['targeting'] ?? null,
                'keyword_text' => $item['keyword'] ?? null,
                'match_type'   => $item['matchType'] ?? null,
                'added'        => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ], $rows);

            $model = $this->isTodayReport ? TempAmzKeywordPerformanceReport::class : AmzAdsKeywordPerformanceReport::class;

            if ($this->isTodayReport) {
                foreach ($records as $record) {
                    $model::updateOrCreate(
                        [
                            'campaign_id' => $record['campaign_id'],
                            'keyword_id'  => $record['keyword_id'],
                            'c_date'      => $record['c_date'],
                            'country'     => $record['country'],
                        ],
                        $record
                    );
                }
            } else {
                foreach (array_chunk($records, 1000) as $chunk) {
                    $model::insert($chunk);
                }
            }

            $reportLog->update(['report_status' => 'COMPLETED']);

            if ($this->isTodayReport) {
                $client->deleteReport($reportLog->report_id);
            }

            Log::info("✅ [KeywordGetReportSave][{$this->country}] Report saved and completed.");

        } catch (\Throwable $e) {
            Log::error("[KeywordGetReportSave][{$this->country}] Job failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

