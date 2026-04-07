<?php

namespace App\Jobs\Ads;

use App\Services\Api\AmazonAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductsKeywords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $country) {}

    public function handle(AmazonAdsService $amazonAdsService): void
    {
        ini_set('memory_limit', '3072M');

        $profileId = config("amazon_ads.profiles.{$this->country}");
        if (!$profileId) {
            Log::channel('ads')->error("❌ Missing profile ID for {$this->country}");
            return;
        }

        $filter = [
            'stateFilter' => ['include' => ['ENABLED', 'PAUSED']],
            'includeExtendedDataFields' => true,
            'maxResults' => 1000,
        ];

        $nextToken = null;
        $emptyResponseCount = 0;
        $maxEmptyResponses = 5;

        $totalFetched = 0;
        $chunk = [];

        // Tune this based on your DB:
        $batchSize = 5000;

        Log::channel('ads')->info("🚀 Syncing Sponsored Products Keywords for {$this->country}");

        do {
            if ($nextToken) {
                $filter['nextToken'] = $nextToken;
            } else {
                unset($filter['nextToken']);
            }

            try {
                $response = $amazonAdsService->listKeywords($filter, $profileId);
            } catch (\Throwable $e) {
                Log::channel('ads')->error("❌ API error for {$this->country}", [
                    'message' => $e->getMessage(),
                ]);
                throw $e; // let queue retry
            }

            $responseBody = $response['response'] ?? null;

            if (empty($responseBody)) {
                if (++$emptyResponseCount >= $maxEmptyResponses) break;
                continue;
            }

            $data = is_array($responseBody) ? $responseBody : json_decode($responseBody, true);

            if (!is_array($data)) {
                Log::channel('ads')->warning("⚠️ Invalid response format for {$this->country}");
                return;
            }

            $emptyResponseCount = 0;

            $keywords = $data['keywords'] ?? [];
            $nextToken = $data['nextToken'] ?? null;

            if (empty($keywords)) continue;

            $ts = now(); // single timestamp per page/batch

            foreach ($keywords as $k) {
                // Avoid warnings if key missing
                $keywordId = $k['keywordId'] ?? null;
                if (!$keywordId) continue;

                $chunk[] = [
                    'country'      => $this->country,
                    'keyword_id'   => $keywordId,
                    'campaign_id'  => $k['campaignId'] ?? null,
                    'ad_group_id'  => $k['adGroupId'] ?? null,
                    'keyword_text' => $k['keywordText'] ?? null,
                    'match_type'   => $k['matchType'] ?? null,
                    'state'        => $k['state'] ?? null,
                    'bid'          => $k['bid'] ?? null,

                    // If you truly need "added", consider making it nullable and only set on insert.
                    'added'        => $ts,

                    'created_at'   => $ts,
                    'updated_at'   => $ts,
                ];

                if (count($chunk) >= $batchSize) {
                    $this->flushUpsert($chunk);
                    $chunk = [];
                }
            }

            $totalFetched += count($keywords);

            // Remove this unless you are actually hitting rate limits.
            // usleep(200000);

        } while (!empty($nextToken));

        if (!empty($chunk)) {
            $this->flushUpsert($chunk);
        }

        Log::channel('ads')->info("✅ Completed keywords sync for {$this->country}. Total: {$totalFetched}");
    }

    private function flushUpsert(array $rows): void
    {
        // One transaction per batch
        DB::transaction(function () use ($rows) {
            DB::table('amz_ads_keywords')->upsert(
                $rows,
                ['keyword_id', 'country'],
                ['campaign_id', 'ad_group_id', 'keyword_text', 'match_type', 'state', 'bid', 'updated_at']
            );
        }, 1);
    }
}
