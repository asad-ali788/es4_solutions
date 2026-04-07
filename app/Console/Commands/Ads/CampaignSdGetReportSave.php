<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SdCampaignGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignSdGetReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-sd-get-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Save SD Campaign Performance Report [US/CA]';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SdCampaignGetReportSaveJob::dispatch('US');
        SdCampaignGetReportSaveJob::dispatch('CA');

        $this->info('Sponsored Display Campaign report jobs dispatched.');
        Log::channel('ads')->info('SdCampaignGetReportSaveJob dispatched.');
    }
}
