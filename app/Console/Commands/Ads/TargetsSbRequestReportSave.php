<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\TargetsSbGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TargetsSbRequestReportSave extends Command
{
    protected $signature = 'app:targets-sb-request-report-save';
    protected $description = 'Save SD Targeting Daily Report';

    public function handle()
    {
        TargetsSbGetReportSaveJob::dispatch('US');
        TargetsSbGetReportSaveJob::dispatch('CA');

        $this->info('Sponsored Brands Targeting report jobs dispatched.');
        Log::channel('ads')->info('TargetsSbGetReportSaveJob dispatched.');
    }
}
