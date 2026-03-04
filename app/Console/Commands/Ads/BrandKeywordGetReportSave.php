<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\BrandKeywordGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BrandKeywordGetReportSave extends Command
{
    protected $signature = 'app:brand-keyword-get-report-save';
    protected $description = 'Save SB Keyword Performance Daily Report';

    public function handle()
    {
        BrandKeywordGetReportSaveJob::dispatch('US');
        BrandKeywordGetReportSaveJob::dispatch('CA');
        Log::channel('ads')->info(" ✅ BrandKeywordGetReportSaveJob Dispatched");
    }
}
