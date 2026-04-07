<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\PurchasedProductGetReportSaveJob;
use Illuminate\Console\Command;

class PurchasedProductUpdateReportSave extends Command
{
    protected $signature = 'app:purchased-product-update-report-save';
    protected $description = 'Save SB Purchased Product Update Report (2 days old)';

    public function handle()
    {
        // sponsored brands purchased product update
        PurchasedProductGetReportSaveJob::dispatch('US', 'sbPurchasedProduct_update');
        PurchasedProductGetReportSaveJob::dispatch('CA', 'sbPurchasedProduct_update');

        $this->info('SB Purchased Product Update report save jobs dispatched for US and CA.');
    }
}
