<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\CampaignGetReportSave as JobsCampaignGetReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignGetReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-request-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save campaign SP Daily Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsCampaignGetReportSave::dispatch("US");
        JobsCampaignGetReportSave::dispatch("CA");
        $this->info('Campaign get report job has been dispatched.');
        Log::channel('ads')->info(" ✅ JobsCampaignGetReportSave Dispatched");
    }
}
