<?php

namespace App\Console\Commands\Ads\Ai;

use App\Jobs\Ai\AiTargetRecommendationsJob;
use Illuminate\Console\Command;

class AiTargetRecommendations extends Command
{
    protected $signature = 'app:ai-target-recommendations';
    protected $description = 'Dispatch AI target bid recommendations job';

    public function handle()
    {
        AiTargetRecommendationsJob::dispatch()->onQueue('ai-long-running');
        $this->info("✅ Ai Target Recommendations job dispatched (SP + SB + SD).");
    }
}
