<?php

namespace App\Jobs\Ads;

use App\Models\AmzAdsCampaignPerformanceReport;
use App\Models\AmzAdsCampaignSBPerformanceReport;
use App\Models\AmzAdsCampaignPerformanceReportSd;
use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CampaignLastTwoDaysReportSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $country;
    public array $reportTypes;    // ['sp','sb','sd']
    public array $dayIndexes;     // [1,2]

    public function __construct(string $country, array $reportTypes, array $dayIndexes)
    {
        $this->country = strtoupper($country);
        $this->reportTypes = $reportTypes;
        $this->dayIndexes = $dayIndexes;
    }

    public function handle(AmazonAdsService $client)
    {
        foreach ($this->reportTypes as $type) {
            foreach ($this->dayIndexes as $dayIndex) {
                $this->processReport($type, $dayIndex, $client);
            }
        }
    }

    protected function processReport(string $type, int $dayIndex, AmazonAdsService $client)
    {
        $map = [
            'sp' => [
                'model' => AmzAdsCampaignPerformanceReport::class,
                'log_prefix' => 'spCampaigns_Last_two_days_',
                'unique_keys' => ['campaign_id', 'ad_group_id', 'c_date', 'country'],
            ],
            'sb' => [
                'model' => AmzAdsCampaignSBPerformanceReport::class,
                'log_prefix' => 'sbCampaigns_Last_two_days_',
                'unique_keys' => ['campaign_id', 'date', 'country'],
            ],
            'sd' => [
                'model' => AmzAdsCampaignPerformanceReportSd::class,
                'log_prefix' => 'sdCampaigns_Last_two_days_',
                'unique_keys' => ['campaign_id', 'c_date', 'country'],
            ],
        ];

        if (!isset($map[$type])) return;

        $config = $map[$type];
        $modelClass = $config['model'];
        $reportLogType = $config['log_prefix'] . $dayIndex;

        $reportLog = AmzAdsReportLog::where('country', $this->country)
            ->where('report_type', $reportLogType)
            ->where('report_status', 'IN_PROGRESS')
            ->latest()
            ->first();

        if (!$reportLog) {
            Log::channel('ads')->warning("❌ No log found for [$reportLogType][{$this->country}]");
            return;
        }

        try {
            $profileId = config("amazon_ads.profiles." . $this->country);
            $response = $client->getReport($reportLog->report_id, $profileId);

            if ($response['code'] != 200) {
                Log::channel('ads')->warning("❌ Invalid response code {$response['code']} for [$reportLogType][{$this->country}]");
                return;
            }

            $responseData = json_decode($response['response'], true);

            if ($responseData['status'] === 'COMPLETED') {
                $downloaded = $client->downloadReport($responseData['url'], true, $profileId);
                $rows = json_decode($downloaded['response'], true);

                if (empty($rows)) {
                    Log::channel('ads')->warning("⚠ Empty report for [$reportLogType][{$this->country}]");
                    return;
                }

                $records = array_map(fn($item) => $this->mapRow($item, $type), $rows);

                foreach (array_chunk($records, 1000) as $chunk) {
                    $modelClass::upsert($chunk, $config['unique_keys'], array_keys($chunk[0]));
                }
                $client->deleteReport($reportLog->report_id, $profileId);
                $reportLog->update(['report_status' => 'COMPLETED']);
                // Log::channel('ads')->info("✅ Saved Last 2 Days [$type] Report [$reportLogType][{$this->country}]");
            }

            if ($responseData['status'] === 'PENDING') {
                $reportLog->increment('r_iteration');
                // Log::channel('ads')->info("⏳ Pending → iteration {$reportLog->r_iteration} for [$reportLogType][{$this->country}]");
            }
        } catch (\Throwable $e) {
            Log::channel('ads')->error("💥 Failed [$reportLogType][{$this->country}]: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function mapRow(array $item, string $type): array
    {
        $now = now();
        $country = $this->country;

        return match ($type) {
            'sp' => [
                'campaign_id' => $item['campaignId'] ?? null,
                'ad_group_id' => $item['adGroupId'] ?? null,
                'cost' => $item['cost'] ?? null,
                'sales1d' => $item['sales1d'] ?? null,
                'sales7d' => $item['sales7d'] ?? null,
                'purchases1d' => $item['purchases1d'] ?? null,
                'purchases7d' => $item['purchases7d'] ?? null,
                'clicks' => $item['clicks'] ?? null,
                'costPerClick' => $item['costPerClick'] ?? null,
                'c_budget' => $item['campaignBudgetAmount'] ?? null,
                'c_currency' => $item['campaignBudgetCurrencyCode'] ?? null,
                'c_status' => $item['campaignStatus'] ?? null,
                'c_date' => isset($item['date']) ? Carbon::parse($item['date']) : null,
                'country' => $country,
                'budget_gap' => isset($item['cost'], $item['campaignBudgetAmount']) && $item['cost'] >= 0.9 * $item['campaignBudgetAmount'] ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'sb' => [
                'campaign_id' => $item['campaignId'] ?? null,
                'cost' => $item['cost'] ?? null,
                'clicks' => $item['clicks'] ?? null,
                'unitsSold' => $item['unitsSold'] ?? null,
                'purchases' => $item['purchases'] ?? null,
                'impressions' => $item['impressions'] ?? null,
                'c_budget' => $item['campaignBudgetAmount'] ?? null,
                'c_currency' => $item['campaignBudgetCurrencyCode'] ?? null,
                'c_status' => $item['campaignStatus'] ?? null,
                'sales' => $item['sales'] ?? null,
                'date' => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : $now->format('Y-m-d'),
                'country' => $country,
                'budget_gap' => isset($item['cost'], $item['campaignBudgetAmount']) && $item['cost'] >= 0.9 * $item['campaignBudgetAmount'] ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'sd' => [
                'campaign_id' => $item['campaignId'] ?? null,
                'campaign_status' => $item['campaignStatus'] ?? null,
                'campaign_budget_amount' => $item['campaignBudgetAmount'] ?? null,
                'campaign_budget_currency_code' => $item['campaignBudgetCurrencyCode'] ?? null,
                'impressions' => $item['impressions'] ?? null,
                'clicks' => $item['clicks'] ?? null,
                'cost' => $item['cost'] ?? null,
                'sales' => $item['sales'] ?? null,
                'purchases' => $item['purchases'] ?? null,
                'units_sold' => $item['unitsSold'] ?? null,
                'c_date' => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : $now->format('Y-m-d'),
                'country' => $country,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            default => [],
        };
    }
}
