<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListCampaigns;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Sync SP Campaigns [US/CA] from Amazon Ads API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncListCampaigns::dispatch('US');
        SyncListCampaigns::dispatch('CA');
        $this->info('Campaigns jobs dispatched for US & CA.');
        Log::channel('ads')->info('SyncListCampaigns jobs dispatched for US & CA.');
    }
}
