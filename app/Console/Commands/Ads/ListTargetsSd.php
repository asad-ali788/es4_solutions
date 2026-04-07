<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\SyncListTargetsSd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListTargetsSd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-targets-sd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Sync SD Targets [US/CA] from Amazon Ads API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncListTargetsSd::dispatch('US');
        SyncListTargetsSd::dispatch('CA');
        $this->info('Targets SD job has been dispatched for US & CA.');
        Log::channel('ads')->info('SyncListTargetsSd dispatched for US & CA.');
    }
}
