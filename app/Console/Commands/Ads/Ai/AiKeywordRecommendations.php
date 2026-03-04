<?php

namespace App\Console\Commands\Ads\Ai;

use App\Jobs\Ai\AiKeywordRecommendationsJob;
use App\Models\AmzKeywordRecommendation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AiKeywordRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ai-keyword-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Amz Ads AI Keyword Performance recommendation (ChatGPT)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market');
        $dayStart = Carbon::now($marketTz)->startOfDay()->subDay();
        $dateStr  = $dayStart->toDateString();

        $batchSize = 500;

        $baseQuery = AmzKeywordRecommendation::query()
            ->whereNull('ai_status')
            ->where('date', $dayStart)
            ->orderBy('id');

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info("ℹ️ No pending keyword recommendations for {$dateStr}.");
            return 0;
        }

        $this->info("Found {$total} pending keywords for {$dateStr}. Dispatching in batches of {$batchSize}...");

        $batchIndex = 0;

        $baseQuery->chunkById($batchSize, function ($rows) use ($dateStr, $batchSize, &$batchIndex) {
            $ids = $rows->pluck('id')->all();

            AiKeywordRecommendationsJob::dispatch($ids, $dateStr)
                ->onQueue('ai-long-running');

            $batchIndex++;

            $this->info("  → Dispatched batch #{$batchIndex} ({$rows->count()} rows)");
        });

        $this->info("✅ Finished dispatching AI keyword recommendation jobs. Total jobs: {$batchIndex}");

        return 0;
    }
}
