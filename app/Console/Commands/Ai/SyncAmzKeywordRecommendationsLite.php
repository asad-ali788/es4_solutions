<?php

namespace App\Console\Commands\Ai;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync campaign keyword recommendations to amz_keyword_recommendations_lite table
 * 
 * This command stores lightweight, denormalized keyword recommendation data
 * for faster AI querying without needing to join multiple tables.
 * 
 * Uses upsert to update or create records - no date-based partitioning.
 * 
 * Data sources:
 * - Base: campaign_keyword_recommendations (keywords, bids, match types)
 * - Add: amz_campaigns (campaign_name, campaign_type)
 * - Add: amz_ads_products (asin)
 */
class SyncAmzKeywordRecommendationsLite extends Command
{
    protected $signature = 'app:sync-campaign-keyword-recommendations-lite';

    protected $description = 'Sync campaign keyword recommendations to lite table - upserts all data';

    public function handle(): void
    {
        try {
            $startTime = microtime(true);
            $this->info("📅 Starting campaign keyword recommendations sync...");

            // Sync all keywords (no date filtering)
            $result = $this->syncAllKeywords();

            if ($result['error']) {
                $this->error("❌ Error syncing: {$result['error']}");
            } else {
                $this->info("✅ {$result['synced']} keywords synced in {$result['duration']}s");
                $this->info("\n🎉 Sync completed successfully!");
            }
        } catch (\Throwable $e) {
            Log::error('SyncAmzKeywordRecommendationsLite failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("❌ Fatal error: {$e->getMessage()}");
        }
    }

    /**
     * Sync all keyword recommendations - uses chunked approach with smaller batch size
     */
    protected function syncAllKeywords(): array
    {
        $startTime = microtime(true);
        $totalSynced = 0;
        $batchSize = 1000; // Smaller batch size to avoid memory issues
        $chunkSize = 100;  // Even smaller chunks for upsert operations

        try {
            $this->info("📡 Starting to sync keywords in batches...");
            
            // Get total count first
            $totalCount = DB::table('campaign_keyword_recommendations as ckr')
                ->leftJoin('amz_campaigns as ac', 'ckr.campaign_id', '=', 'ac.campaign_id')
                ->leftJoin('amz_ads_products as aap', 'ckr.campaign_id', '=', 'aap.campaign_id')
                ->select(DB::raw('COUNT(DISTINCT CONCAT(ckr.campaign_id, "|", ckr.keyword, "|", ckr.match_type, "|", ckr.country)) as total'))
                ->value('total');
            
            $this->info("📊 Found {$totalCount} unique keyword combinations to sync");
            
            if ($totalCount === 0) {
                $this->warn("⚠️  No keywords found");
                return [
                    'synced' => 0,
                    'duration' => round(microtime(true) - $startTime, 2),
                    'error' => null,
                ];
            }

            // Use chunk() method which is memory-efficient
            $batchNumber = 0;
            $currentTimestamp = now();
            
            DB::table('campaign_keyword_recommendations as ckr')
                ->leftJoin('amz_campaigns as ac', 'ckr.campaign_id', '=', 'ac.campaign_id')
                ->leftJoin('amz_ads_products as aap', 'ckr.campaign_id', '=', 'aap.campaign_id')
                ->select(
                    'ckr.campaign_id',
                    'ac.campaign_name',
                    'ac.campaign_type',
                    'ckr.keyword',
                    'ckr.match_type',
                    DB::raw('MIN(aap.asin) as asin'),
                    'ckr.bid as current_bid',
                    'ckr.bid_start as bid_suggestion_start',
                    'ckr.bid_suggestion as bid_suggestion_mid',
                    'ckr.bid_end as bid_suggestion_end',
                    'ckr.country',
                    'ckr.ad_group_id'
                )
                ->groupBy(
                    'ckr.campaign_id',
                    'ckr.ad_group_id',
                    'ckr.keyword',
                    'ckr.match_type',
                    'ckr.country',
                    'ac.campaign_name',
                    'ac.campaign_type',
                    'ckr.bid',
                    'ckr.bid_start',
                    'ckr.bid_suggestion',
                    'ckr.bid_end'
                )
                ->orderBy('ckr.campaign_id')
                ->chunk($batchSize, function ($keywords) use (&$batchNumber, &$totalSynced, $chunkSize, $currentTimestamp) {
                    $batchNumber++;
                    $this->line("📦 Processing batch {$batchNumber}: " . $keywords->count() . " keywords");

                    // Prepare insert data
                    $insertData = [];
                    foreach ($keywords as $keyword) {
                        $insertData[] = [
                            'campaign_id' => $keyword->campaign_id,
                            'campaign_name' => $keyword->campaign_name,
                            'campaign_type' => $keyword->campaign_type ?? 'SP',
                            'keyword' => trim($keyword->keyword),
                            'match_type' => $keyword->match_type,
                            'asin' => $keyword->asin,
                            'current_bid' => $keyword->current_bid ?? 0,
                            'bid_suggestion_start' => $keyword->bid_suggestion_start ?? 0,
                            'bid_suggestion_mid' => $keyword->bid_suggestion_mid ?? 0,
                            'bid_suggestion_end' => $keyword->bid_suggestion_end ?? 0,
                            'country' => $keyword->country,
                            'ad_group_id' => $keyword->ad_group_id,
                            'synced_at' => $currentTimestamp,
                        ];
                    }

                    // Split into smaller chunks for upsert to avoid SQL size limits
                    $chunks = array_chunk($insertData, $chunkSize);
                    foreach ($chunks as $chunkIndex => $chunk) {
                        try {
                            DB::table('campaign_keyword_recommendations_lite')
                                ->upsert($chunk, ['campaign_id', 'keyword', 'match_type', 'country'], [
                                    'campaign_name',
                                    'campaign_type',
                                    'asin',
                                    'current_bid',
                                    'bid_suggestion_start',
                                    'bid_suggestion_mid',
                                    'bid_suggestion_end',
                                    'ad_group_id',
                                    'synced_at',
                                ]);
                            
                            $totalSynced += count($chunk);
                        } catch (\Throwable $e) {
                            $this->error("❌ Chunk " . ($chunkIndex + 1) . " failed: {$e->getMessage()}");
                            throw $e;
                        }
                    }

                    $this->line("  ✓ Batch {$batchNumber} completed (Total synced: {$totalSynced})");
                });

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Sync completed! Total: {$totalSynced} keywords in {$duration}s");

            return [
                'synced' => $totalSynced,
                'duration' => $duration,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::error("SyncAmzKeywordRecommendationsLite failed", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'synced' => 0,
                'duration' => round(microtime(true) - $startTime, 2),
                'error' => $e->getMessage(),
            ];
        }
    }
}
