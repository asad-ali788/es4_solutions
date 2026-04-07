<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\KeywordGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KeywordUpdateReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:keyword-update-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates amz_ads_keyword_performance_report for latest data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        KeywordGetReportSaveJob::dispatch("US", false, 'spTargeting_update')->onQueue('long-running');
        KeywordGetReportSaveJob::dispatch("CA", false, 'spTargeting_update')->onQueue('long-running');
        
        $this->info('Keyword update report save jobs have been dispatched.');
        Log::channel('ads')->info(" ✅ KeywordUpdateReportSave Dispatched");
    }
}
