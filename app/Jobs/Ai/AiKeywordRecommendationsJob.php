<?php

namespace App\Jobs\Ai;

use App\Models\AmzKeywordRecommendation;
use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordSb;
use App\Services\Api\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AiKeywordRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * IDs of AmzKeywordRecommendation to process in this job
     *
     * @var array<int>
     */
    public array $ids = [];

    public function __construct(array $ids)
    {
        $this->ids = $ids;

        // Always push to dedicated queue
        $this->onQueue('ai-long-running');
    }

    public function handle(): void
    {
        $openAi = app(OpenAIService::class);

        if (empty($this->ids)) {
            Log::channel('ai')->warning('AiKeywordRecommendationsJob called with empty $ids');
            return;
        }

        // Load only this job's batch
        $rows = AmzKeywordRecommendation::query()
            ->whereIn('id', $this->ids)
            ->orderBy('id')
            ->get();

        Log::channel('ai')->info('▶️ Keyword AI Job Started', [
            'row_count' => $rows->count(),
        ]);

        /**
         * Split into small chunks of 30 (same as campaign)
         */
        $rows->chunk(30)->each(function ($chunk) use ($openAi) {

            /**
             * Preload bid values (avoid N+1)
             */
            $spIds = $chunk->where('campaign_types', 'SP')->pluck('keyword_id')->all();
            $sbIds = $chunk->where('campaign_types', 'SB')->pluck('keyword_id')->all();

            $spKeywords = $spIds
                ? AmzAdsKeywords::whereIn('keyword_id', $spIds)->get()->keyBy('keyword_id')
                : collect();

            $sbKeywords = $sbIds
                ? AmzAdsKeywordSb::whereIn('keyword_id', $sbIds)->get()->keyBy('keyword_id')
                : collect();

            /**
             * Prepare payload for 30 keywords
             */
            $payload = $chunk->values()->map(function ($r) use ($spKeywords, $sbKeywords) {

                $bid = null;
                if ($r->campaign_types === "SP") {
                    $bid = optional($spKeywords->get($r->keyword_id))->bid;
                } elseif ($r->campaign_types === "SB") {
                    $bid = optional($sbKeywords->get($r->keyword_id))->bid;
                }

                return [
                    'keyword_id'             => $r->keyword_id,
                    'campaign_type'          => $r->campaign_types,
                    'country'                => $r->country,
                    'keyword'                => $r->keyword ?? 'N/A',
                    'current_bid'            => $bid,

                    // Performance metrics
                    'clicks_7days'           => $r->clicks_7d ?? 0,
                    'impressions_7days'      => $r->impressions ?? 0,
                    'ctr_7days'              => $r->ctr_7d ?? 0,
                    'cpc_7days'              => $r->cpc_7d ?? 0,
                    'orders_7days'           => $r->purchases1d_7d ?? 0,
                    'conversion_rate_7days'  => $r->conversion_rate_7d ?? 0,
                    'spend_7days'            => $r->total_spend_7d ?? 0,
                    'sales_7days'            => $r->total_sales_7d ?? 0,
                    'acos_7days'             => $r->acos_7d ?? 0,

                    'type'                   => 'keyword',
                ];
            })->toArray();

            // Log::channel('ai')->info('📦 Keyword Payload', [
            //     'count' => count($payload),
            // ]);
            /**
             * Single bulk request for this sub-chunk
             */
            $results = $openAi->sendBulkRecommendations($payload, 'keyword_bulk') ?? [];
            /**
             * Update DB
             */
            foreach ($chunk as $row) {
                $data = $results[$row->keyword_id] ?? null;

                $row->update([
                    'ai_status'           => $data ? 'done' : 'failed',
                    'ai_suggested_bid'    => $data['suggested_value'] ?? null,
                    'ai_recommendation'   => $data['recommendation'] ?? null,
                    'updated_at'          => now(),
                ]);
            }

            // Log::channel('ai')->info('🔹 Keyword Ai sub-chunk processed', [
            //     'count' => $chunk->count(),
            // ]);

            // Rate limit protection
            // sleep(5);
        });

        Log::channel('ai')->info('✅ Keyword AI Job Completed', [
            'row_count' => $rows->count(),
        ]);
    }
}
