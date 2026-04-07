<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListProductAdsSb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListProductAdsSb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-product-ads-sb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Sync SB Product Ads [US/CA] from Amazon Ads API';

    /**
     * https://advertising.amazon.com/API/docs/en-us/sponsored-brands/3-0/openapi/prod#tag/Ads/operation/ListSponsoredBrandsAds
     * Execute the console command.
     */
    public function handle()
    {
        SyncListProductAdsSb::dispatch('US');
        SyncListProductAdsSb::dispatch('CA');
        $this->info('Product Ads SB job has been dispatched for US & CA.');
        Log::channel('ads')->info('SyncListProductAdsSb dispatched for US & CA.');
    }
}
