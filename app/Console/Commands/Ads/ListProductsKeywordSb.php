<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncProductsKeywordSb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListProductsKeywordSb extends Command
{

    protected $signature = 'app:list-products-keyword-sb';


    protected $description = 'ADS: Sync SB Keywords [US/CA] from Amazon Ads API';

    public function handle()
    {
        SyncProductsKeywordSb::dispatch('US')->onQueue('long-running');
        SyncProductsKeywordSb::dispatch('CA')->onQueue('long-running');
        $this->info('✅ Sponsored Products Keywords job dispatched.');
        Log::channel('ads')->info('SyncProductsKeywordSb dispatched for US & CA.');
    }
}
