<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\KeywordGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KeywordGetReportSave extends Command
{
    protected $signature = 'app:keyword-get-report-save';
    protected $description = 'Save SP Keyword Performance Daily Report';

    public function handle()
    {
        KeywordGetReportSaveJob::dispatch('US');
        KeywordGetReportSaveJob::dispatch('CA');
        $this->info('Keyword performance report job dispatched.');
        Log::channel('ads')->info('KeywordGetReportSaveJob dispatched.');
    }
}
