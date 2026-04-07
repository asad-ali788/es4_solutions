<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignRequestReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-request-report {targetDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Request SP Campaign Performance Report [US/CA]';

    protected int $maxRetries = 3;
    protected int $retryDelaySeconds = 60;

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $clients)
    {
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
            $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA', 'spCampaigns_update');
            $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US', 'spCampaigns_update');
            $this->info("✅ Campaign reports processed for $date.");
            return;
        }
        
        // 📅 Standard: Sub 1 day
        $date     = Carbon::now($marketTz)->subDays()->toDateString();
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US');
        
        // 📅 Update: Sub 2 days (One more day behind)
        $updateDate = Carbon::now($marketTz)->subDays(2)->toDateString();
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $updateDate, 'CA', 'spCampaigns_update');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $updateDate, 'US', 'spCampaigns_update');

        echo "✅ Campaign reports processed for US and CA.\n";
    }

    private function requestReportForCountry(
        AmazonAdsService $clients,
        string $profileId,
        string $date,
        string $country,
        string $reportTypeOverride = null
    ): void {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;
            try {
                $response = $clients->requestReport([
                    "name"      => "",
                    "startDate" => $date,
                    "endDate"   => $date,
                    "configuration" => [
                        "adProduct"    => "SPONSORED_PRODUCTS",
                        "groupBy"      => ['campaign', 'adGroup'],
                        "columns"      => [
                            'adGroupId',
                            'campaignId',
                            'impressions',
                            'clicks',
                            'cost',
                            'purchases1d',
                            'purchases7d',
                            'campaignBudgetCurrencyCode',
                            'date',
                            'sales7d',
                            'sales1d',
                            'costPerClick',
                            'campaignStatus',
                            'campaignBudgetAmount',
                            'adGroupName',
                            'adStatus'
                        ],
                        "reportTypeId" => "spCampaigns",
                        "timeUnit"     => "DAILY",
                        "format"       => "GZIP_JSON"
                    ]
                ], $profileId);

                if ($response['code'] === 200) {
                    $responseData = json_decode($response['response'], true);
                    AmzAdsReportLog::create([
                        'country'       => $country,
                        'report_type'   => $reportTypeOverride ?? ($responseData['configuration']['reportTypeId'] ?? null),
                        'report_id'     => $responseData['reportId'] ?? null,
                        'report_status' => 'IN_PROGRESS',
                        'r_iteration'   => 0,
                        'report_date'   => $responseData['startDate'] ?? null,
                        'added'         => now(),
                    ]);

                    Log::channel('ads')->info("✅ [$country] Campaign report requested (attempt {$attempt})");
                    return; // stop retrying
                }

                // 🔁 Retryable (rate limited / not ready)
                if (in_array($response['code'], [425, 429])) {
                    Log::channel('ads')->warning(
                        "⏳ [$country] Retry {$attempt}/{$this->maxRetries} – API not ready"
                    );
                } else {
                    // ❌ Non-retryable error
                    Log::channel('ads')->error("❌ [$country] Campaign report request failed", [
                        'code' => $response['code'],
                        'response' => $response['response'] ?? null,
                    ]);
                    return;
                }
            } catch (\Throwable $e) {
                Log::channel('ads')->error("❌ [$country] Exception on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                ]);
            }

            // wait before retry
            if ($attempt < $this->maxRetries) {
                sleep($this->retryDelaySeconds);
            }
        }

        Log::channel('ads')->error("🚫 [$country] Campaign report failed after {$this->maxRetries} retries");
    }
}
