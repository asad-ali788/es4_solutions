<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\SdCampaignGetReportSaveJob;
use App\Jobs\Ads\ProductGetReportSdSaveJob;
use Illuminate\Console\Command;

class ProductUpdateReportSdSave extends Command
{
    protected $signature = 'app:product-update-report-sd-save';
    protected $description = 'Save SD Product Performance Update Report (2 days old)';

    public function handle()
    {
        // sponsored display product update
        ProductGetReportSdSaveJob::dispatch('US', false, 'sdAdvertisedProduct_update');
        ProductGetReportSdSaveJob::dispatch('CA', false, 'sdAdvertisedProduct_update');

        $this->info('SD Product Update report save jobs dispatched for US and CA.');
    }
}
