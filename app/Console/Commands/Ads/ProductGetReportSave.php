<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\ProductGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductGetReportSave extends Command
{
    protected $signature = 'app:product-get-report-save';
    protected $description = 'ADS: Save SP Product Performance Report [US/CA]';

    public function handle()
    {
        ProductGetReportSaveJob::dispatch('US');
        ProductGetReportSaveJob::dispatch('CA');
        Log::channel('ads')->info('ProductGetReportSaveJob dispatched.');
    }
}
