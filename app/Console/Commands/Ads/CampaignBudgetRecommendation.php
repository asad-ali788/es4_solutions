<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\CampaignBudgetRecommendationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignBudgetRecommendation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-budget-recommendation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Campaign Budget Recommendation jobs dispatched';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CampaignBudgetRecommendationJob::dispatchSync('US');
        CampaignBudgetRecommendationJob::dispatchSync('CA');

        $this->info('Campaign Budget Recommendation jobs dispatched.');
        Log::channel('ads')->info('CampaignBudgetRecommendationJob dispatched.');
    }
}
