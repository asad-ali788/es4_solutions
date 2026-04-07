<?php

namespace App\Console\Commands\Ads\Updates;

use App\Jobs\Ads\ProductGetReportSaveJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductUpdateReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:product-update-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates amz_ads_product_performance_report for latest data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProductGetReportSaveJob::dispatch("US", false, 'spAdvertisedProduct_update');
        ProductGetReportSaveJob::dispatch("CA", false, 'spAdvertisedProduct_update');
        
        $this->info('Product update report save jobs have been dispatched.');
        Log::channel('ads')->info(" ✅ ProductUpdateReportSave Dispatched");
    }
}
