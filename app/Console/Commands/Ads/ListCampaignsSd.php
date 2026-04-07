<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListCampaignsSd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListCampaignsSd extends Command
{
    protected $signature = 'app:list-campaigns-sd';
    protected $description = 'ADS: Sync SD Campaigns [US/CA] from Amazon Ads API';

    public function handle()
    {
        SyncListCampaignsSd::dispatch('US');
        SyncListCampaignsSd::dispatch('CA');
        $this->info('SD Campaigns job has been dispatched for US & CA.');
        Log::channel('ads')->info('SyncListCampaignsSd job dispatched for US & CA.');
    }
}
