<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListCampaignsSb;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListCampaignsSb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-campaigns-sb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get SB Campaigns Daily Report';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        SyncListCampaignsSb::dispatch('US');
        SyncListCampaignsSb::dispatch('CA');
        $this->info('SB Campaigns job has been dispatched for US & CA.');
        Log::channel('ads')->info('SyncListCampaignsSb job has been dispatched for US & CA.');
    }
}
