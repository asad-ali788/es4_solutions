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
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\SyncUnifiedPerformanceLite;

class KeywordGetReportSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;
    public bool $isTodayReport;
    public ?string $reportType;

    public function __construct(string $country, bool $isTodayReport = false, ?string $reportType = null)
    {
        $this->country = $country;
        $this->isTodayReport = $isTodayReport;
        $this->reportType = $reportType;
    }

    public function handle(AmazonAdsService $client)
    {
        if ($this->reportType) {
            $reportType = $this->reportType;
        } elseif ($this->isTodayReport) {
            $reportType = 'spTargeting_daily';
        } else {
            $reportType = 'spTargeting';
        }

        $reportLog = AmzAdsReportLog::where([
            'country' => $this->country,
            'report_type' => $reportType,
            'report_status' => 'IN_PROGRESS'
        ])->latest()->first();

        if (!$reportLog) {
            Log::info("🚫 [KeywordGetReportSave] No in-progress report for {$this->country} type: {$reportType}.");
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

            foreach (array_chunk($records, 2000) as $chunk) {
                $model::upsert(
                    $chunk,
                    ['campaign_id', 'keyword_id', 'c_date', 'country'],
                    ['cost', 'clicks', 'impressions', 'sales1d', 'sales7d', 'sales30d', 'purchases1d', 'purchases7d', 'purchases30d', 'keyword_bid', 'targeting', 'keyword_text', 'match_type', 'added', 'updated_at']
                );
            }

            $reportLog->update(['report_status' => 'COMPLETED']);

            // 🚀 Refresh recommendations if this was an update fetch
            if ($this->reportType === 'spTargeting_update' && !empty($records)) {
                $targetDate = $records[0]['c_date'] ?? null;
                if ($targetDate) {
                    Artisan::call('app:keyword-recommendations', ['date' => $targetDate->toDateString()]);
                    SyncUnifiedPerformanceLite::dispatch($targetDate);
                    Log::info("🔄 Keyword recommendation refresh dispatched for date: {$targetDate->toDateString()}");
                }
            }

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

