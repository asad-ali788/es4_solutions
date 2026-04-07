<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\TargetsSdGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TargetsSdRequestReportSave extends Command
{
    protected $signature = 'app:targets-sd-request-report-save';
    protected $description = 'ADS: Save SD Targeting Performance Report [US/CA]';

    public function handle()
    {
        TargetsSdGetReportSaveJob::dispatch('US');
        TargetsSdGetReportSaveJob::dispatch('CA');

        $this->info('Sponsored Display Targeting report jobs dispatched.');
        Log::channel('ads')->info('TargetsSdGetReportSaveJob dispatched.');
    }
}
