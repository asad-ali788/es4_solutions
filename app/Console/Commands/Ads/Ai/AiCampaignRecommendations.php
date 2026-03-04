<?php

namespace App\Console\Commands\Ads\Ai;

use App\Jobs\Ai\AiCampaignRecommendationsionsJob;
use App\Models\CampaignRecommendations;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AiCampaignRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ai-campaign-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Amz Ads AI campaign Performance recommendation (ChatGPT)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz  = config('timezone.market');
        $dayStart  = Carbon::now($marketTz)->startOfDay()->subDay();

        $this->info("🔍 Selecting campaigns for report_week: {$dayStart}");

        $totalDispatched = 0;

        CampaignRecommendations::query()
            ->whereNull('ai_status')
            ->where('report_week', $dayStart)
            ->where('total_spend', '>', 0)
            ->orderBy('id')
            // 200 records per job
            ->chunkById(600, function ($rows) use (&$totalDispatched) {
                $ids = $rows->pluck('id')->all();

                AiCampaignRecommendationsionsJob::dispatch($ids)->onQueue('ai-long-running');

                $count = count($ids);
                $totalDispatched += $count;

                Log::channel('ai')->info('📤 Dispatched AiCampaignRecommendationsionsJob', [
                    'job_row_count' => $count,
                ]);

                $this->info("📤 Dispatched job for {$count} campaigns");
            });

        $this->info("✅ Done. Total campaigns dispatched to AI jobs: {$totalDispatched}");
    }
}
