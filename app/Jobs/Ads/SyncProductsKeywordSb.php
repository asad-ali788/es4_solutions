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

class SyncProductsKeywordSb implements ShouldQueue
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

        $startIndex = 0;
        $count = 1000;

        $totalFetched = 0;
        $chunk = [];
        $batchSize = 4000; // tune: 10k-25k is usually best

        $maxEmptyResponses = 3;
        $emptyResponseCount = 0;

        Log::channel('ads')->info("🚀 Starting SB keywords sync for {$this->country}");

        while (true) {
            $filter = [
                'startIndex'  => $startIndex,
                'count'       => $count,
                'stateFilter' => 'enabled,paused',
            ];

            try {
                $response = $amazonAdsService->listSBKeywords($filter, $profileId);
            } catch (\Throwable $e) {
                Log::channel('ads')->error("❌ SB keywords API error for {$this->country}", [
                    'startIndex' => $startIndex,
                    'message' => $e->getMessage(),
                ]);
                throw $e; // let queue retry
            }

            $responseBody = $response['response'] ?? null;

            if (empty($responseBody)) {
                $emptyResponseCount++;

                if ($emptyResponseCount >= $maxEmptyResponses) {
                    Log::channel('ads')->warning("⚠️ Too many empty responses — stopping at startIndex {$startIndex}");
                    break;
                }

                // move forward anyway (some APIs return empty pages occasionally)
                $startIndex += $count;
                continue;
            }

            $data = is_array($responseBody) ? $responseBody : json_decode($responseBody, true);

            if (!is_array($data) || empty($data)) {
                $emptyResponseCount++;

                if ($emptyResponseCount >= $maxEmptyResponses) {
                    Log::channel('ads')->warning("⚠️ Invalid/empty payload — stopping at startIndex {$startIndex}");
                    break;
                }

                $startIndex += $count;
                continue;
            }

            $emptyResponseCount = 0;

            $ts = now(); // one timestamp per page

            foreach ($data as $keyword) {
                $keywordId = $keyword['keywordId'] ?? null;
                if (!$keywordId) continue;

                $chunk[] = [
                    'country'      => $this->country,
                    'keyword_id'   => $keywordId,
                    'campaign_id'  => $keyword['campaignId'] ?? null,
                    'ad_group_id'  => $keyword['adGroupId'] ?? null,
                    'keyword_text' => $keyword['keywordText'] ?? null,
                    'match_type'   => $keyword['matchType'] ?? null,
                    'state'        => $keyword['state'] ?? null,
                    'bid'          => $keyword['bid'] ?? null,
                    'added'        => $ts,
                    'created_at'   => $ts,
                    'updated_at'   => $ts,
                ];

                if (count($chunk) >= $batchSize) {
                    $this->flushUpsert($chunk);
                    $chunk = [];
                }
            }

            $totalFetched += count($data);

            $startIndex += $count;

            // Stop when we get less than requested. Usually means no more pages.
            if (count($data) < $count) {
                break;
            }

            // IMPORTANT: no unconditional sleep.
            // If you hit throttling, do backoff inside AmazonAdsService on 429 instead.
        }

        if (!empty($chunk)) {
            $this->flushUpsert($chunk);
        }

        Log::channel('ads')->info("✅ Completed SB keywords sync for {$this->country}. Total: {$totalFetched}");
    }

    private function flushUpsert(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            DB::table('amz_ads_keyword_sb')->upsert(
                $rows,
                ['keyword_id', 'country'],
                ['campaign_id', 'ad_group_id', 'keyword_text', 'match_type', 'state', 'bid', 'updated_at']
            );
        }, 1);
    }
}
