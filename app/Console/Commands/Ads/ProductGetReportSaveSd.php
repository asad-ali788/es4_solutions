<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\ProductGetReportSdSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductGetReportSaveSd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:product-get-report-save-sd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Save SD Product Performance Report [US/CA]';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProductGetReportSdSaveJob::dispatch('US');
        ProductGetReportSdSaveJob::dispatch('CA');
        Log::channel('ads')->info('ProductGetReportSdSaveJob dispatched.');
    }
}
