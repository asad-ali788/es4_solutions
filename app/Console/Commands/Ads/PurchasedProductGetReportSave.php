<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\PurchasedProductGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurchasedProductGetReportSave extends Command
{
    protected $signature = 'app:purchased-product-get-report-save';
    protected $description = 'Save SB Purchased Product Daily Report';

    public function handle()
    {
        PurchasedProductGetReportSaveJob::dispatch('US');
        PurchasedProductGetReportSaveJob::dispatch('CA');
        Log::channel('ads')->info('PurchasedProductGetReportSaveJob dispatched.');
        $this->info('✅ Purchased Product get report jobs dispatched.');
    }
}
