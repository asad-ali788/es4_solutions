<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListProductAds;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListProductAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-product-ads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get SP Products Daily Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncListProductAds::dispatch('US');
        SyncListProductAds::dispatch('CA');
        $this->info('Product Ads job has been dispatched.');
        Log::channel('ads')->info('SyncListProductAds job dispatched.');
    }
}
