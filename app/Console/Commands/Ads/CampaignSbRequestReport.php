<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use App\Services\Api\AmazonAdsService;
use Carbon\Carbon;
use App\Models\AmzAdsReportLog;
use Illuminate\Support\Facades\Log;

class CampaignSbRequestReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-sb-request-report {targetDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Request SB Campaign Performance Report [US/CA]';

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
            $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA', 'sbCampaigns_update');
            $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US', 'sbCampaigns_update');
            $this->info("✅ Campaign SB reports processed for $date.");
            return;
        }
        
        // 📅 Standard: Sub 1 day
        $date     = Carbon::now($marketTz)->subDays()->toDateString();
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $date, 'CA');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $date, 'US');

        // 📅 Update: Sub 2 days (One more day behind)
        $updateDate = Carbon::now($marketTz)->subDays(2)->toDateString();
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.CA'), $updateDate, 'CA', 'sbCampaigns_update');
        $this->requestReportForCountry($clients, config('amazon_ads.profiles.US'), $updateDate, 'US', 'sbCampaigns_update');

        echo "✅ Campaign SB reports processed for US and CA.\n";
    }

    private function requestReportForCountry(
        AmazonAdsService $clients,
        string $profileId,
        string $date,
        string $country,
        string $reportTypeOverride = null
    ): void {
        $data = [
            "name"      => "",
            "startDate" => $date,
            "endDate"   => $date,
            "configuration" => [
                "adProduct"    => "SPONSORED_BRANDS",
                "groupBy"      => ['campaign'],
                "columns"      => [
                    'campaignId',
                    'impressions',
                    'clicks',
                    'cost',
                    'purchases',
                    'unitsSold',
                    'campaignBudgetCurrencyCode',
                    'date',
                    'sales',
                    'campaignStatus',
                    'campaignBudgetAmount',
                ],
                "reportTypeId" => "sbCampaigns",
                "timeUnit"     => "DAILY",
                "format"       => "GZIP_JSON"
            ]
        ];

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $clients->requestReport($data, $profileId);

                if (($response['code'] ?? null) == 200) {
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

                    Log::channel('ads')->info("✅ [$country] Campaign SB Report requested (attempt {$attempt}): " . ($responseData['reportId'] ?? 'N/A'));
                    sleep(3);
                    return; // ✅ stop retrying
                }

                // Retryable cases (Amazon can return 425 / 429 during throttling or duplicate-in-progress)
                if (in_array(($response['code'] ?? 0), [425, 429], true)) {
                    Log::channel('ads')->warning("⚠️ [$country] SB report not ready/throttled (attempt {$attempt}/{$this->maxRetries}).", [
                        'code' => $response['code'] ?? null,
                        'detail' => is_array($response['response'] ?? null) ? ($response['response']['detail'] ?? null) : null,
                    ]);
                } else {
                    // ❌ Non-retryable error
                    Log::channel('ads')->error("❌ [$country] SB report request failed: Code " . ($response['code'] ?? 'N/A'), [
                        'profile_id'   => $profileId,
                        'request_body' => $data,
                        'raw_response' => $response['response'] ?? null,
                    ]);
                    sleep(3);
                    return;
                }
            } catch (\Throwable $e) {
                Log::channel('ads')->error("❌ [$country] SB report exception (attempt {$attempt}/{$this->maxRetries}): {$e->getMessage()}", [
                    'profile_id' => $profileId,
                ]);
            }

            // wait before next attempt
            if ($attempt < $this->maxRetries) {
                sleep($this->retryDelaySeconds);
            }
        }

        Log::channel('ads')->error("🚫 [$country] SB report request failed after {$this->maxRetries} attempts.", [
            'profile_id' => $profileId,
            'date'       => $date,
        ]);

        sleep(3);
    }
}
