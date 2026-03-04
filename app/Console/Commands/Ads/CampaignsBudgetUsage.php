<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\CampaignsBudgetUsageJobs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignsBudgetUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaigns-budget-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Campaign Budget Usage for all';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CampaignsBudgetUsageJobs::dispatchSync('US');
        CampaignsBudgetUsageJobs::dispatchSync('CA');
        $this->info('Campaigns Budget Usage Stated');
        Log::channel('ads')->info('CampaignBudgetUsageJobs dispatched for US & CA.');
    }
}
