<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsReportLog;
use App\Models\SpSearchTermSummaryReport;
use App\Services\Api\AmazonAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SpSearchTermSummaryGetReportSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;

    public function __construct(string $country)
    {
        $this->country = strtoupper($country);
    }

    public function handle(AmazonAdsService $client)
    {
        $reportType = 'spSearchTermSummary';

        Log::channel('ads')->info("📥 Processing {$this->country} SP Search Term Summary Report");

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->info("[SpSearchTermSummary][{$this->country}] No report in progress.");
            return;
        }

        try {
            $profileId = match ($this->country) {
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
                default => config('amazon_ads.profiles.US'),
            };

            $response = $client->getReport($reportLog->report_id, $profileId);
            Log::channel('ads')->info("[SpSearchTermSummary][{$this->country}] Response Code: {$response['code']}");

            if ($response['code'] != 200) {
                Log::channel('ads')->warning("[SpSearchTermSummary][{$this->country}] Invalid response code.");
                return;
            }

            $responseData = json_decode($response['response'], true);

            if (($responseData['status'] ?? '') === 'COMPLETED') {

                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $reportRows = json_decode($downloaded['response'], true);

                if (empty($reportRows)) {

                    $client->deleteReport($reportLog->report_id);

                    Log::channel('ads')->warning("[SpSearchTermSummary][{$this->country}] Empty report → deleted.");

                    $reportLog->update(['report_status' => 'EMPTY']);
                    return;
                }

                $records = [];
                foreach ($reportRows as $row) {
                    $records[] = [
                        'country'       => $this->country,
                        'date'          => !empty($row['date']) ? Carbon::parse($row['date'])->format('Y-m-d') : Carbon::parse($reportLog->report_date)->format('Y-m-d'),
                        'campaign_id'   => $row['campaignId'] ?? null,
                        'ad_group_id'   => $row['adGroupId'] ?? null,
                        'keyword_id'    => $row['keywordId'] ?? null,
                        'keyword'       => $row['keyword'] ?? null,
                        'search_term'   => $row['searchTerm'] ?? null,
                        'impressions'   => $row['impressions'] ?? null,
                        'clicks'        => $row['clicks'] ?? null,
                        'cost_per_click' => $row['costPerClick'] ?? null,
                        'cost'          => $row['cost'] ?? null,
                        'purchases_1d'  => $row['purchases1d'] ?? null,
                        'purchases_7d'  => $row['purchases7d'] ?? null,
                        'purchases_14d' => $row['purchases14d'] ?? null,
                        'sales_1d'      => $row['sales1d'] ?? null,
                        'sales_7d'      => $row['sales7d'] ?? null,
                        'sales_14d'     => $row['sales14d'] ?? null,
                        'campaign_budget_amount' => $row['campaignBudgetAmount'] ?? null,
                        'keyword_bid'   => $row['keywordBid'] ?? null,
                        'keyword_type'  => $row['keywordType'] ?? null,
                        'match_type'    => $row['matchType'] ?? null,
                        'targeting'     => $row['targeting'] ?? null,
                        'ad_keyword_status' => $row['adKeywordStatus'] ?? null,
                        'start_date'    => !empty($row['startDate']) ? Carbon::parse($row['startDate'])->format('Y-m-d') : null,
                        'end_date'      => !empty($row['endDate']) ? Carbon::parse($row['endDate'])->format('Y-m-d') : null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                foreach (array_chunk($records, 1000) as $chunk) {
                    SpSearchTermSummaryReport::upsert(
                        $chunk,

                        [
                            'country',
                            'date',
                            'campaign_id',
                            'ad_group_id',
                            'keyword_id',
                            'search_term',
                        ],

                        [
                            'keyword',
                            'impressions',
                            'clicks',
                            'cost_per_click',
                            'cost',
                            'purchases_1d',
                            'purchases_7d',
                            'purchases_14d',
                            'sales_1d',
                            'sales_7d',
                            'sales_14d',
                            'campaign_budget_amount',
                            'keyword_bid',
                            'keyword_type',
                            'match_type',
                            'targeting',
                            'ad_keyword_status',
                            'start_date',
                            'end_date',
                            'updated_at',
                        ]
                    );
                }


                $reportLog->update(['report_status' => 'COMPLETED']);

                Log::channel('ads')->info("[SpSearchTermSummary][{$this->country}] Report saved to DB → COMPLETED.");
            } elseif ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                Log::channel('ads')->info("[SpSearchTermSummary][{$this->country}] Still pending. Iteration++");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("[SpSearchTermSummary][{$this->country}] ERROR: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
