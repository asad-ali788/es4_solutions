<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\CampaignSbGetReportSave as JobsCampaignSbGetReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignSbGetReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-sb-get-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save campaign SB Daily Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsCampaignSbGetReportSave::dispatch("US");
        JobsCampaignSbGetReportSave::dispatch("CA");
        $this->info('Campaign SB get report job has been dispatched for US & CA.');
        Log::channel('ads')->info('JobsCampaignSbGetReportSave dispatched for US & CA.');
    }
}
