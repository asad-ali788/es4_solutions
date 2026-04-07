<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\CampaignSbGetReportSave as JobsCampaignSbGetReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignSbUpdateReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-sb-update-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates amz_ads_campaign_performance_reports_sb for latest data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsCampaignSbGetReportSave::dispatch("US", false, 'sbCampaigns_update');
        JobsCampaignSbGetReportSave::dispatch("CA", false, 'sbCampaigns_update');
        
        $this->info('Campaign SB update report save jobs have been dispatched.');
        Log::channel('ads')->info(" ✅ CampaignSbUpdateReportSave Dispatched");
    }
}
