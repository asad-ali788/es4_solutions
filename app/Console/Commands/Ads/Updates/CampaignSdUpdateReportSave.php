<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\SdCampaignGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignSdUpdateReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-sd-update-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates amz_ads_campaign_performance_report_sd for latest data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SdCampaignGetReportSaveJob::dispatch("US", false, 'sdCampaigns_update');
        SdCampaignGetReportSaveJob::dispatch("CA", false, 'sdCampaigns_update');
        
        $this->info('Campaign SD update report save jobs have been dispatched.');
        Log::channel('ads')->info(" ✅ CampaignSdUpdateReportSave Dispatched");
    }
}
