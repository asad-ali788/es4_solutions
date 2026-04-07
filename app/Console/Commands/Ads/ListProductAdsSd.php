<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListProductAdsSd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListProductAdsSd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-product-ads-sd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Sync SD Product Ads [US/CA] from Amazon Ads API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncListProductAdsSd::dispatch('US');
        SyncListProductAdsSd::dispatch('CA');
        $this->info('Product Ads SD job has been dispatched for US & CA.');
        Log::channel('ads')->info('SyncListProductAdsSd dispatched for US & CA.');
        
    }
}
