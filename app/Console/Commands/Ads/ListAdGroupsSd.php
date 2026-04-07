<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncProductsAdGroupsSd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListAdGroupsSd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-ad-groups-sd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Sync SD Ad Groups [US/CA] from Amazon Ads API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncProductsAdGroupsSd::dispatch('US');
        SyncProductsAdGroupsSd::dispatch('CA');
        $this->info('✅ Ad Groups sync job dispatched for US & CA.');
        Log::channel('ads')->info('✅ SyncProductsAdGroupsSd dispatched for US & CA.');
    }
}
