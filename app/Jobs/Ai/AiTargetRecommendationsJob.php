<?php

namespace App\Jobs\Ai;

use App\Models\AmzTargetRecommendation;
use App\Services\Api\OpenAIService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AiTargetRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $marketTz  = config('timezone.market');
        $dayStart  = Carbon::now($marketTz)->startOfDay()->subDay();
        $chunkSize = 15;
        Log::channel('ai')->info('✅ Target Ai Recommendation Started');

        $openAi = app(OpenAIService::class);

        AmzTargetRecommendation::query()
            ->whereNull('ai_status')
            ->whereDate('date', $dayStart)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($openAi) {

                // 🧠 Build payload for OpenAI
                $payload = $rows->map(fn($r) => [
                    'campaign_id'     => (string) $r->campaign_id, // important key
                    'targeting_id'    => (string) $r->targeting_id,
                    'targeting_text'  => $r->targeting_text,
                    'ad_group_id'     => (string) $r->ad_group_id,
                    'country'         => $r->country,
                    'campaign_type'   => $r->campaign_types,
                    'current_bid'     => $r->suggested_bid,

                    // 1d
                    'clicks'          => $r->clicks,
                    'impressions'     => $r->impressions,
                    'ctr'             => $r->ctr,
                    'cpc'             => $r->cpc,
                    'orders'          => $r->orders,
                    'total_spend'     => $r->total_spend,
                    'total_sales'     => $r->total_sales,
                    'conversion_rate' => $r->conversion_rate,
                    'acos'            => $r->acos,

                    // 7d
                    'total_spend_7d'  => $r->total_spend_7d,
                    'total_sales_7d'  => $r->total_sales_7d,
                    'acos_7d'         => $r->acos_7d,

                    // 14d
                    'total_spend_14d' => $r->total_spend_14d,
                    'total_sales_14d' => $r->total_sales_14d,
                ])->toArray();

                // 🔹 Call OpenAI with correct prompt key
                $results = $openAi->sendBulkRecommendations($payload, 'target_bulk') ?? [];

                foreach ($rows as $rec) {
                    // ⬇ Match based on campaign_id (as per sendBulkRecommendations)
                    $data = $results[(string) $rec->campaign_id] ?? null;

                    $aiSuggestedBid = null;
                    if (!empty($data['suggested_value']) && is_numeric($data['suggested_value'])) {
                        $aiSuggestedBid = $data['suggested_value'];
                    }

                    $rec->update([
                        'ai_status'         => $data ? 'done' : 'failed',
                        'ai_suggested_bid'  => $aiSuggestedBid,
                        'ai_recommendation' => $data['recommendation'] ?? null,
                        'updated_at'        => now(),
                    ]);
                }

                Log::info('Target chunk processed', ['count' => $rows->count()]);
                // sleep(10);
            });
        Log::channel('ai')->info('✅ Target Ai Recommendation Completed');
    }
}
