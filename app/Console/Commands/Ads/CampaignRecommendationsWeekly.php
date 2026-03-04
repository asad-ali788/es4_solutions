<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\CampaignRecommendationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignRecommendationsWeekly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-recommendations-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Amz Ads Campaign Performance Daily Report';

    /**
     * Execute the console command.->onQueue('long-running');
     */
    public function handle()
    {
        CampaignRecommendationJob::dispatchSync();
        Log::channel('ads')->info("✅ Unified Campaign Recommendations job dispatched (SP + SB + SD).");
    }
}
