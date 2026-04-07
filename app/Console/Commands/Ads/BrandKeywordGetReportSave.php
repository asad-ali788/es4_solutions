<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\BrandKeywordGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BrandKeywordGetReportSave extends Command
{
    protected $signature = 'app:brand-keyword-get-report-save';
    protected $description = 'ADS: Save SB Keyword Performance Report [US/CA]';

    public function handle()
    {
        BrandKeywordGetReportSaveJob::dispatch('US');
        BrandKeywordGetReportSaveJob::dispatch('CA');
        Log::channel('ads')->info(" ✅ BrandKeywordGetReportSaveJob Dispatched");
    }
}
