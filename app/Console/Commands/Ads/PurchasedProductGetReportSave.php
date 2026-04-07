<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\PurchasedProductGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurchasedProductGetReportSave extends Command
{
    protected $signature = 'app:purchased-product-get-report-save';
    protected $description = 'ADS: Save SB Purchased Product Report [US/CA]';

    public function handle()
    {
        PurchasedProductGetReportSaveJob::dispatch('US');
        PurchasedProductGetReportSaveJob::dispatch('CA');
        Log::channel('ads')->info('PurchasedProductGetReportSaveJob dispatched.');
        $this->info('✅ Purchased Product get report jobs dispatched.');
    }
}
