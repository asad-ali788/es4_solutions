<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\CampaignGetReportSave as JobsCampaignGetReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignUpdateReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-update-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates amz_ads_campaign_performance_report for latest data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsCampaignGetReportSave::dispatch("US", false, 'spCampaigns_update');
        JobsCampaignGetReportSave::dispatch("CA", false, 'spCampaigns_update');
        
        $this->info('Campaign update report save jobs have been dispatched.');
        Log::channel('ads')->info(" ✅ CampaignUpdateReportSave Dispatched");
    }
}
