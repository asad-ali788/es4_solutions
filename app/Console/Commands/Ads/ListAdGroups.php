<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncProductsAdGroups;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListAdGroups extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:list-ad-groups';

    /**
     * The console command description.
     */
    protected $description = 'Get SP Ad Groups Daily Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncProductsAdGroups::dispatch('US');
        SyncProductsAdGroups::dispatch('CA');
        $this->info('✅ Ad Groups sync job dispatched for US & CA.');
        Log::channel('ads')->info('✅ SyncProductsAdGroups dispatched for US & CA.');
    }
}
