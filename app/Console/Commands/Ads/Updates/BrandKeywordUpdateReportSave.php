<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\BrandKeywordGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BrandKeywordUpdateReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:brand-keyword-update-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates amz_ads_keyword_performance_report_sb for latest data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        BrandKeywordGetReportSaveJob::dispatch("US", false, 'sbTargeting_SB_update');
        BrandKeywordGetReportSaveJob::dispatch("CA", false, 'sbTargeting_SB_update');
        
        $this->info('Brand Keyword update report save jobs have been dispatched.');
        Log::channel('ads')->info(" ✅ BrandKeywordUpdateReportSave Dispatched");
    }
}
