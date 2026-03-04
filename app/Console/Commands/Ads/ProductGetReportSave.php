<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\ProductGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductGetReportSave extends Command
{
    protected $signature = 'app:product-get-report-save';
    protected $description = 'Save SP Product Performance Daily Report';

    public function handle()
    {
        ProductGetReportSaveJob::dispatch('US');
        ProductGetReportSaveJob::dispatch('CA');
        Log::channel('ads')->info('ProductGetReportSaveJob dispatched.');
    }
}
