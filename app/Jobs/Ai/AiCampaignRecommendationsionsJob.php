<?php

namespace App\Jobs\Ai;

use App\Models\CampaignRecommendations;
use App\Services\Api\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AiCampaignRecommendationsionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * IDs of CampaignRecommendations to process in this job.
     *
     * @var array<int>
     */
    public array $ids = [];

    public function __construct(array $ids)
    {
        $this->ids = $ids;

        // Ensure these always go to your long-running queue
        $this->onQueue('ai-long-running');
    }

    public function handle(): void
    {
        $openAi = app(OpenAIService::class);

        // Safety: in case something weird gets queued
        if (empty($this->ids)) {
            Log::channel('ai')->warning('AiCampaignRecommendationsionsJob called with empty $ids');
            return;
        }

        $rows = CampaignRecommendations::query()
            ->whereIn('id', $this->ids)
            ->orderBy('id')
            ->get();
        // Log::channel('ai')->info('✅ Campaign Ai Job Started', [
        //     'row_count' => $rows->count(),
        // ]);

        $rows->chunk(30)->each(function ($chunk) use ($openAi) {
            $payload = $chunk->values()->map(fn($r) => [
                'campaign_id'             => (string) $r->campaign_id,
                'campaign_type'           => $r->campaign_types,
                'country'                 => $r->country,
                'current_daily_budget'    => $r->total_daily_budget,
                'total_spend_7d'          => $r->total_spend_7d,
                'total_sales_7d'          => $r->total_sales_7d,
                'purchases7d_7d'          => $r->purchases7d_7d,
                'acos_7d'                 => $r->acos_7d,
                'total_spend_14d'         => $r->total_spend_14d,
                'total_sales_14d'         => $r->total_sales_14d,
                'purchases7d_14d'         => $r->purchases7d_14d,
            ])->toArray();

            // Log::channel('ai')->info('📧 Payload', [
            //     'payload' => $payload,
            // ]);
            $results = $openAi->sendBulkRecommendations($payload, 'campaign_bulk') ?? [];

            foreach ($chunk as $rec) {
                $data = $results[$rec->campaign_id] ?? null;

                $rec->update([
                    'ai_status'           => $data ? 'done' : 'failed',
                    'ai_suggested_budget' => $data['suggested_value'] ?? null,
                    'ai_recommendation'   => $data['recommendation'] ?? null,
                    'updated_at'          => now(),
                ]);
            }

            // Log::channel('ai')->info('Campaign Ai sub-chunk processed', [
            //     'count' => $chunk->count(),
            // ]);

            // sleep(10);
        });

        Log::channel('ai')->info('✅ Campaign Ai Job Completed', [
            'row_count' => $rows->count(),
        ]);
    }
}
