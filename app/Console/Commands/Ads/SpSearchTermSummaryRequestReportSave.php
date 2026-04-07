<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SpSearchTermSummaryGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SpSearchTermSummaryRequestReportSave extends Command
{
    protected $signature = 'app:sp-search-term-summary-request-report-save';
    protected $description = 'ADS: Save SP Search Term Summary Report [US/CA]';

    public function handle()
    {
        SpSearchTermSummaryGetReportSaveJob::dispatchSync('US');
        SpSearchTermSummaryGetReportSaveJob::dispatchSync('CA');

        $this->info('Sponsored Products Search Term Summary report jobs dispatched.');
        Log::channel('ads')->info('SpSearchTermSummaryGetReportSaveJob dispatched.');
    }
}
