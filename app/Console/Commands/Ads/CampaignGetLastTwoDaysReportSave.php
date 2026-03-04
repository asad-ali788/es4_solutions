<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use App\Jobs\Ads\CampaignLastTwoDaysReportSaveJob;
use Illuminate\Support\Facades\Log;

class CampaignGetLastTwoDaysReportSave extends Command
{
    protected $signature = 'app:campaign-get-last-two-days-report-save';
    protected $description = 'Dispatch one job per country to fetch & save SP/SB/SD campaign reports for the last 2 days';

    public function handle()
    {
        $countries = ['US', 'CA'];

        foreach ($countries as $country) {
            CampaignLastTwoDaysReportSaveJob::dispatch(
                $country,
                ['sp', 'sb', 'sd'],  // types
                [1, 2]                // day indexes
            );

            $this->info("✅ Dispatched consolidated job for {$country}");
        }

        Log::channel('ads')->info("📤 Dispatched consolidated Last Two Days SP/SB/SD Campaign Report Jobs for US and CA");
    }
}
