<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AmzAdsReportLog;

// Jobs
use App\Jobs\Ads\ProductGetReportSaveJob;
use App\Jobs\Ads\BrandKeywordGetReportSaveJob;
use App\Jobs\Ads\CampaignSbGetReportSave;
use App\Jobs\Ads\KeywordGetReportSaveJob;
use App\Jobs\Ads\CampaignGetReportSave as JobsCampaignGetReportSave;
use App\Jobs\Ads\ProductGetReportSdSaveJob;
use App\Jobs\Ads\SdCampaignGetReportSaveJob;
use App\Jobs\Ads\TargetsSbGetReportSaveJob;
use App\Jobs\Ads\TargetsSdGetReportSaveJob;

class DispatchDailyAdReportJobs extends Command
{
    protected $signature = 'app:dispatch-daily-report-jobs';
    protected $description = 'ADS: Dispatch Jobs to Poll and Save Pending Ad Reports [US/CA]';

    /**
     * How long we allow IN_PROGRESS without report_id before marking FAILED
     */
    protected int $staleMinutes = 120;

    public function handle(): int
    {
        $date = Carbon::now(config('timezone.market'))->toDateString();
        $countries = ['US', 'CA'];
        $isTodayReport = true;
        $staleCutoff = now()->subMinutes($this->staleMinutes);

        $reportJobs = [
            'spAdvertisedProduct_daily' => ProductGetReportSaveJob::class,
            'sbTargeting_SB_daily'      => BrandKeywordGetReportSaveJob::class,
            'spCampaigns_daily'         => JobsCampaignGetReportSave::class,
            'sbCampaigns_daily'         => CampaignSbGetReportSave::class,
            'spTargeting_daily'         => KeywordGetReportSaveJob::class,
            'sdCampaigns_daily'         => SdCampaignGetReportSaveJob::class,
            'sdTargeting_daily'         => TargetsSdGetReportSaveJob::class,
            'sdAdvertisedProduct_daily' => ProductGetReportSdSaveJob::class,
            'sbTargetingClause_daily'   => TargetsSbGetReportSaveJob::class,
        ];

        foreach ($countries as $country) {

            $profileId = config("amazon_ads.profiles.$country");

            if (!$profileId) {
                $this->warn("No profile ID found for {$country}");
                continue;
            }

            foreach ($reportJobs as $reportType => $jobClass) {

                $log = AmzAdsReportLog::query()
                    ->where('report_type', $reportType)
                    ->where('country', $country)
                    ->where('report_date', $date)
                    ->where('report_status', 'IN_PROGRESS')
                    ->latest('added')
                    ->first();

                if (!$log) {

                    $this->warn("Log not found: {$reportType} for {$country}");

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CASE 1: Log exists but report_id missing → FAILED
                |--------------------------------------------------------------------------
                */
                if (!$log->report_id) {

                    $log->report_status = 'FAILED';
                    $log->save();

                    Log::channel('ads')->error('Report ID missing - Marked FAILED', [
                        'report_type' => $reportType,
                        'country' => $country,
                        'log_id' => $log->id,
                    ]);

                    $this->error("Marked FAILED (missing report_id): {$reportType} for {$country}");
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CASE 2: Stale IN_PROGRESS → FAILED
                |--------------------------------------------------------------------------
                */
                $lastUpdated = $log->updated_at ?? $log->created_at;

                if ($lastUpdated && $lastUpdated->lt($staleCutoff)) {

                    $log->report_status = 'FAILED';
                    $log->save();

                    Log::channel('ads')->warning('Stale IN_PROGRESS - Marked FAILED', [
                        'report_type' => $reportType,
                        'country' => $country,
                        'log_id' => $log->id,
                    ]);

                    $this->warn("Marked FAILED (stale): {$reportType} for {$country}");
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CASE 3: Valid → Dispatch job
                |--------------------------------------------------------------------------
                */
                $jobClass::dispatch(
                    $country,
                    $isTodayReport
                )->onQueue('long-running');

                $this->info("Dispatched: {$reportType} for {$country}");
            }
        }

        $this->info("All report jobs processed.");
        return self::SUCCESS;
    }
}
