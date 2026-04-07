<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCampaignRequestReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-campaign-request-report {targetDate?} {--country=US}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: [Test] Request Campaign Report for SP Campaigns';

    protected int $maxRetries = 3;
    protected int $retryDelaySeconds = 60;

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $clients)
    {
        $country  = strtoupper($this->option('country'));
        $marketTz = config('timezone.market');
        $targetDate = $this->argument('targetDate');

        if ($targetDate) {
            $date = Carbon::parse($targetDate)->toDateString();
        } else {
            $date = Carbon::now($marketTz)->subDays()->toDateString();
        }

        $this->requestReportForCountry($clients, config("amazon_ads.profiles.{$country}"), $date, $country);
        $this->info("✅ Test Campaign report requested for {$country} on {$date}.");
    }

    private function requestReportForCountry(
        AmazonAdsService $clients,
        string $profileId,
        string $date,
        string $country
    ): void {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;
            try {

                $response = $clients->requestReport([
                    "name"      => "Test Campaigns Report",
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
                            'campaignBudgetAmount'
                        ],
                        "reportTypeId" => "spCampaigns",
                        "timeUnit"     => "DAILY",
                        "format"       => "GZIP_JSON"
                    ]
                ], $profileId);

                if ($response['code'] === 200 || $response['code'] === 202) {
                    $responseData = json_decode($response['response'], true);
                    AmzAdsReportLog::create([
                        'country'       => $country,
                        'report_type'   => 'testCampaigns', // Crucial to match GetReportSave job
                        'report_id'     => $responseData['reportId'] ?? null,
                        'report_status' => 'IN_PROGRESS',
                        'r_iteration'   => 0,
                        'report_date'   => $responseData['startDate'] ?? null,
                        'added'         => now(),
                    ]);

                    Log::channel('ads')->info("✅ [$country] Test Campaign report requested (attempt {$attempt})");
                    return; // stop retrying
                }

                // 🔁 Retryable (rate limited / not ready)
                if (in_array($response['code'], [425, 429])) {
                    Log::channel('ads')->warning(
                        "⏳ [$country] Retry {$attempt}/{$this->maxRetries} – API not ready"
                    );
                } else {
                    // ❌ Non-retryable error
                    Log::channel('ads')->error("❌ [$country] Test Campaign report request failed", [
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

        Log::channel('ads')->error("🚫 [$country] Test Campaign report failed after {$this->maxRetries} retries");
    }
}
