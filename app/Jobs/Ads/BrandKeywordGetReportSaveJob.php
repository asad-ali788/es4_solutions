<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsKeywordPerformanceReportSb;
use App\Models\AmzAdsReportLog;
use App\Models\TempAmzKeywordSBPerformanceReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BrandKeywordGetReportSaveJob implements ShouldQueue
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
        $marketTz = config('timezone.market');
        $date = Carbon::now($marketTz)->subDay()->toDateString();
        
        if ($this->reportType) {
            $reportType = $this->reportType;
        } elseif ($this->isTodayReport) {
            $reportType = 'sbTargeting_SB_daily';
        } else {
            $reportType = 'sbTargeting_SB';
        }

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[BrandKeywordGetReportSave][{$this->country}] No brand keyword report in progress for type: {$reportType}");
            return;
        }

        try {
            $profileId = match (strtoupper($this->country)) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[BrandKeywordGetReportSave][{$this->country}] Response code: " . $response['code']);

            if ($response['code'] !== 200) return;

            $data = json_decode($response['response'], true);

            if ($data['status'] === 'COMPLETED') {
                if ($this->isTodayReport) {
                    $client->deleteReport($reportLog->report_id);
                    Log::channel('ads')->info("[BrandKeywordGetReportSave][{$this->country}] Report deleted after processing.");
                }

                $download = $client->downloadReport($data['url'], true);
                $rows = json_decode($download['response'], true);

                if (empty($rows)) {
                    Log::channel('ads')->warning("[BrandKeywordGetReportSave][{$this->country}] Empty brand keyword report.");
                    return;
                }

                $records = [];
                foreach ($rows as $item) {
                    $records[] = [
                        'campaign_id'   => $item['campaignId'] ?? null,
                        'ad_group_id'   => $item['adGroupId'] ?? null,
                        'keyword_id'    => $item['keywordId'] ?? null,
                        'cost'          => $item['cost'] ?? null,
                        'sales1d'       =>  null,
                        'sales7d'       =>  $item['sales'] ?? null,
                        'sales30d'      => null,
                        'purchases1d'   =>  null,
                        'purchases7d'   => $item['purchases'] ?? null,
                        'purchases30d'  => null,
                        'clicks'        => $item['clicks'] ?? null,
                        'impressions'   => $item['impressions'] ?? null,
                        'keyword_bid'   => $item['keywordBid'] ?? null,
                        'targeting'     => null,
                        'keyword_text'  => $item['keywordText'] ?? null,
                        'match_type'    => $item['matchType'] ?? null,
                        'c_date'        => isset($item['date']) ? Carbon::parse($item['date']) : null,
                        'country'       => $this->country,
                        'added'         => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                if ($this->isTodayReport) {
                    // updateOrCreate per record for temp model
                    foreach ($records as $record) {
                        TempAmzKeywordSBPerformanceReport::updateOrCreate(
                            [
                                'campaign_id' => $record['campaign_id'],
                                'ad_group_id' => $record['ad_group_id'],
                                'keyword_id'  => $record['keyword_id'],
                                'c_date'      => $record['c_date'],
                                'country'     => $record['country'],
                            ],
                            $record
                        );
                    }
                } else {
                    // upsert for regular model
                    foreach (array_chunk($records, 1000) as $chunk) {
                        AmzAdsKeywordPerformanceReportSb::upsert(
                            $chunk,
                            ['campaign_id', 'keyword_id', 'c_date', 'country'],
                            ['cost', 'sales1d', 'sales7d', 'sales30d', 'purchases1d', 'purchases7d', 'purchases30d', 'clicks', 'impressions', 'keyword_bid', 'targeting', 'keyword_text', 'match_type', 'added', 'updated_at']
                        );
                    }
                }

                $reportLog->update(['report_status' => 'COMPLETED']);
            } elseif ($data['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[BrandKeywordGetReportSave][{$this->country}] Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
