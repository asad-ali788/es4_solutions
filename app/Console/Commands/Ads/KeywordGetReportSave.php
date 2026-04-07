<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\KeywordGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KeywordGetReportSave extends Command
{
    protected $signature = 'app:keyword-get-report-save';
    protected $description = 'ADS: Save SP Keyword Performance Report [US/CA]';

    public function handle()
    {
        KeywordGetReportSaveJob::dispatch('US')->onQueue('long-running');
        KeywordGetReportSaveJob::dispatch('CA')->onQueue('long-running');
        $this->info('Keyword performance report job dispatched.');
        Log::channel('ads')->info('KeywordGetReportSaveJob dispatched.');
    }
}
