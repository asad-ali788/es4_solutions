<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PDOException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sync unified keyword + campaign performance data to SQLite lite table
 * 
 * Combines keyword recommendations with campaign data for unified querying
 * Groups by campaign_name with aggregated metrics (single day only)
 */
class SyncUnifiedPerformanceLite extends Command implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $targetDate = null;

    public function __construct($targetDate = null)
    {
        parent::__construct();
        if ($targetDate instanceof Carbon) {
            $this->targetDate = $targetDate->toDateString();
        } elseif (is_string($targetDate)) {
            $this->targetDate = $targetDate;
        }
    }
    protected $signature = 'app:sync-unified-performance-lite 
                            {--date= : Specific date to sync (YYYY-MM-DD)}
                            {--from= : Start date for range sync (YYYY-MM-DD)}
                            {--to= : End date for range sync (YYYY-MM-DD)}';
    protected $description = 'Sync keyword + campaign performance to unified table.';

    public function handle(): void
    {
        try {
            $marketTz = config('timezone.market') ?? 'America/Los_Angeles';
            $now = Carbon::now($marketTz);
            
            // Determine dates to sync
            $datesToSync = [];
            
            $dateOption = null;
            $fromOption = null;
            $toOption = null;

            if (isset($this->input)) {
                $dateOption = $this->option('date');
                $fromOption = $this->option('from');
                $toOption = $this->option('to');
            }

            if ($this->targetDate) {
                // Job dispatched mode
                $date = $this->targetDate;
                $this->validateDateFormat($date);
                $datesToSync[] = $date;
                $this->logMessage("📅 Job mode: syncing {$date}");
                
            } elseif ($dateOption) {
                // Single date mode
                $date = $dateOption;
                $this->validateDateFormat($date);
                $datesToSync[] = $date;
                $this->logMessage("📅 Single date mode: syncing {$date}");
                
            } elseif ($fromOption && $toOption) {
                // Date range mode
                $fromDate = $fromOption;
                $toDate = $toOption;
                
                $this->validateDateFormat($fromDate);
                $this->validateDateFormat($toDate);
                
                $from = Carbon::createFromFormat('Y-m-d', $fromDate);
                $to = Carbon::createFromFormat('Y-m-d', $toDate);
                
                if ($from->isAfter($to)) {
                    $this->logError("From date cannot be after To date");
                    return;
                }
                
                $this->logMessage("📅 Date range mode: syncing {$fromDate} to {$toDate}");
                
                while ($from->lte($to)) {
                    $datesToSync[] = $from->toDateString();
                    $from->addDay();
                }
                
            } elseif ($fromOption || $toOption) {
                $this->logError("Both --from and --to options are required for range sync");
                return;
                
            } else {
                // Default: sync yesterday
                $date = $now->copy()->subDay()->toDateString();
                $datesToSync[] = $date;
                $this->logMessage("📅 Default mode: syncing yesterday ({$date})");
            }
            
            // Process each date
            $totalSynced = 0;
            $totalStartTime = microtime(true);
            
            foreach ($datesToSync as $dateToSync) {
                $result = $this->syncDate($dateToSync);
                $totalSynced += $result['count'];
            }
            
            $totalTime = number_format(microtime(true) - $totalStartTime, 2);
            $this->logMessage("\n✅ Batch complete: {$totalSynced} total records synced in {$totalTime}s");
            
            Log::channel('ai')->info("SyncUnifiedPerformanceLite: Batch sync complete", [
                'dates_processed' => count($datesToSync),
                'total_records' => $totalSynced,
                'total_time' => $totalTime,
            ]);
            
        } catch (\Exception $e) {
            $this->logError("Error: {$e->getMessage()}");
            Log::channel('ai')->error("SyncUnifiedPerformanceLite failed: {$e->getMessage()}");
        }
    }

    /**
     * Validate date format
     */
    private function validateDateFormat(string $date): void
    {
        try {
            Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            if (isset($this->input)) {
                throw new \Exception("Invalid date format: {$date}. Use YYYY-MM-DD");
            } else {
                $this->logError("Invalid date format: {$date}. Use YYYY-MM-DD");
            }
        }
    }

    /**
     * Sync data for a specific date
     */
    private function syncDate(string $date): array
    {
        try {
            $marketTz = config('timezone.market') ?? 'America/Los_Angeles';
            $now = Carbon::now($marketTz);
            
            $this->logMessage("🔄 Syncing unified performance for: {$date}");
            $startTime = microtime(true);

            // Fetch keywords with campaign grouping - union all campaign types
            $keywords = DB::connection('mysql')
                ->table('amz_keyword_recommendations as kr')
                ->whereDate('kr.date', $date)
                ->leftJoin('amz_campaigns as camp_sp', 'kr.campaign_id', '=', 'camp_sp.campaign_id')
                ->leftJoin('amz_campaigns_sb as camp_sb', 'kr.campaign_id', '=', 'camp_sb.campaign_id')
                ->leftJoin('amz_campaigns_sd as camp_sd', 'kr.campaign_id', '=', 'camp_sd.campaign_id')
                ->leftJoin('amz_ads_products as sp', 'kr.campaign_id', '=', 'sp.campaign_id')
                ->leftJoin('amz_ads_products_sb as sb', 'kr.campaign_id', '=', 'sb.campaign_id')
                ->leftJoin('amz_ads_products_sd as sd', 'kr.campaign_id', '=', 'sd.campaign_id')
                ->leftJoin('product_categorisations as pc', function($join) {
                    $join->on('pc.child_asin', '=', DB::raw('COALESCE(sp.asin, sb.asin, sd.asin)'))
                        ->whereNull('pc.deleted_at');
                })
                ->select([
                    'kr.keyword',
                    'kr.campaign_id',
                    'kr.country',
                    'kr.campaign_types',
                    'kr.bid',
                    'camp_sp.campaign_name as camp_sp_name',
                    'camp_sp.campaign_state as camp_sp_state',
                    'camp_sp.daily_budget as camp_sp_budget',
                    'camp_sb.campaign_name as camp_sb_name',
                    'camp_sb.campaign_state as camp_sb_state',
                    'camp_sb.daily_budget as camp_sb_budget',
                    'camp_sd.campaign_name as camp_sd_name',
                    'camp_sd.campaign_state as camp_sd_state',
                    'camp_sd.daily_budget as camp_sd_budget',
                    'sp.asin as sp_asin',
                    'sb.asin as sb_asin',
                    'sd.asin as sd_asin',
                    'pc.child_short_name',
                    // Metrics
                    DB::raw('SUM(kr.total_spend) as total_spend'),
                    DB::raw('SUM(kr.total_sales) as total_sales'),
                    DB::raw('AVG(kr.acos) as acos'),
                    DB::raw('SUM(kr.purchases1d) as purchases'),
                    DB::raw('SUM(kr.clicks) as clicks'),
                    DB::raw('SUM(kr.impressions) as impressions'),
                    DB::raw('AVG(kr.cpc) as cpc'),
                    DB::raw('AVG(kr.ctr) as ctr'),
                ])
                ->groupBy('kr.keyword', 'kr.campaign_id', 'kr.country', 'kr.campaign_types', 'kr.bid', 
                    'camp_sp.campaign_name', 'camp_sp.campaign_state', 'camp_sp.daily_budget',
                    'camp_sb.campaign_name', 'camp_sb.campaign_state', 'camp_sb.daily_budget',
                    'camp_sd.campaign_name', 'camp_sd.campaign_state', 'camp_sd.daily_budget',
                    'sp.asin', 'sb.asin', 'sd.asin', 'pc.child_short_name')
                ->get();

            if ($keywords->isEmpty()) {
                $this->logWarning("⚠️  No keywords found for {$date}");
                return ['count' => 0, 'time' => 0];
            }

            $rowsToInsert = [];
            $now = Carbon::now($marketTz);

            foreach ($keywords as $keyword) {
                $campaignId = (string) $keyword->campaign_id;

                // Resolve campaign info from the three campaign tables
                $campaignName = $keyword->camp_sp_name ?? $keyword->camp_sb_name ?? $keyword->camp_sd_name ?? $campaignId;
                $campaignState = $keyword->camp_sp_state ?? $keyword->camp_sb_state ?? $keyword->camp_sd_state ?? 'ACTIVE';
                $dailyBudget = $keyword->camp_sp_budget ?? $keyword->camp_sb_budget ?? $keyword->camp_sd_budget ?? 0;

                // Resolve ASIN from the three product tables
                $asin = $keyword->sp_asin ?? $keyword->sb_asin ?? $keyword->sd_asin;

                // Calculate metrics
                $spend = (float) ($keyword->total_spend ?? 0);
                $sales = (float) ($keyword->total_sales ?? 0);
                $acos = $spend > 0 && $sales > 0 ? round(($spend / $sales) * 100, 4) : 0;
                $clicks = (int) ($keyword->clicks ?? 0);
                $purchases = (int) ($keyword->purchases ?? 0);

                $rowsToInsert[] = [
                    'keyword_text' => $keyword->keyword,
                    'campaign_id' => $campaignId,
                    'campaign_name' => $campaignName,
                    'campaign_state' => $campaignState,
                    'asin' => $asin,
                    'product_name' => $keyword->child_short_name,
                    'country' => $keyword->country,
                    'campaign_type' => $keyword->campaign_types,
                    'report_date' => $date,
                    'daily_budget' => (float) $dailyBudget,
                    'estimated_monthly_budget' => (float) $dailyBudget * 30,
                    
                    // 1-day metrics
                    'total_spend' => $spend,
                    'total_sales' => $sales,
                    'acos' => $acos,
                    'purchases' => $purchases,
                    'clicks' => $clicks,
                    'impressions' => (int) ($keyword->impressions ?? 0),
                    'cpc' => (float) ($keyword->cpc ?? 0),
                    'ctr' => (float) ($keyword->ctr ?? 0),
                    
                    // Calculated
                    'roas' => $acos > 0 ? round(100 / $acos, 4) : null,
                    'conversion_rate' => $clicks > 0 ? round(($purchases / $clicks) * 100, 4) : 0,
                    
                    // Keyword specific
                    'keyword_bid' => (float) ($keyword->bid ?? 0),
                    'keyword_state' => 'ACTIVE',
                    
                    'notes' => json_encode(['synced_at' => $now->toIso8601String()]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (empty($rowsToInsert)) {
                $this->logWarning("⚠️  No valid rows to insert for {$date}");
                return ['count' => 0, 'time' => 0];
            }

            // Batch insert with transaction
            $chunkSize = 200;
            $chunks = array_chunk($rowsToInsert, $chunkSize);
            $totalRecords = count($rowsToInsert);

            DB::transaction(function () use ($chunks, $totalRecords) {
                $processedCount = 0;
                $totalChunks = count($chunks);
                
                foreach ($chunks as $chunkIndex => $chunk) {
                    $this->batchInsertOrReplace($chunk);
                    $processedCount += count($chunk);
                    
                    if (($chunkIndex + 1) % 20 === 0 || $chunkIndex === $totalChunks - 1) {
                        $percent = round(($processedCount / $totalRecords) * 100);
                        $this->logMessage("   Progress: {$percent}% ({$processedCount}/{$totalRecords})");
                    }
                    
                    gc_collect_cycles();
                }
            });

            $executionTime = number_format(microtime(true) - $startTime, 2);
            $recordsPerSec = $totalRecords > 0 ? number_format($totalRecords / floatval($executionTime), 0) : 0;
            
            $this->logMessage("✅ Successfully synced {$totalRecords} keyword-campaign(s) for {$date}");
            $this->logMessage("   ⚡ Execution time: {$executionTime}s | Speed: {$recordsPerSec} records/sec");
            
            Log::channel('ai')->info("SyncUnifiedPerformanceLite: Synced {$totalRecords} records for {$date}", [
                'date' => $date,
                'record_count' => $totalRecords,
                'execution_time' => $executionTime,
                'records_per_sec' => $recordsPerSec,
            ]);
            
            return ['count' => $totalRecords, 'time' => $executionTime];

        } catch (PDOException $e) {
            $this->logError("   ⚠️  Database error for {$date}: {$e->getMessage()}");
            Log::channel('ai')->error("SyncUnifiedPerformanceLite DB failed for {$date}: {$e->getMessage()}");
            return ['count' => 0, 'time' => 0];
            
        } catch (\Exception $e) {
            $this->logError("   ⚠️  Error syncing {$date}: {$e->getMessage()}");
            Log::channel('ai')->error("SyncUnifiedPerformanceLite failed for {$date}: {$e->getMessage()}");
            return ['count' => 0, 'time' => 0];
        }
    }

    /**
     * Batch upsert records using the table's unique key.
     */
    private function batchInsertOrReplace(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        DB::table('keyword_campaign_performance_lites')->upsert(
            $rows,
            ['keyword_text', 'campaign_id', 'report_date', 'country'],
            [
                'campaign_name',
                'campaign_state',
                'asin',
                'product_name',
                'campaign_type',
                'daily_budget',
                'estimated_monthly_budget',
                'total_spend',
                'total_sales',
                'acos',
                'purchases',
                'clicks',
                'impressions',
                'cpc',
                'ctr',
                'roas',
                'conversion_rate',
                'keyword_bid',
                'keyword_state',
                'notes',
                'updated_at',
            ]
        );
    }

    protected function logMessage($message)
    {
        if (isset($this->output)) {
            $this->line($message);
        } else {
            Log::channel('ai')->info("SyncUnifiedPerformanceLite: {$message}");
        }
    }

    protected function logError($message)
    {
        if (isset($this->output)) {
            $this->error($message);
        } else {
            Log::channel('ai')->error("SyncUnifiedPerformanceLite: {$message}");
        }
    }

    protected function logWarning($message)
    {
        if (isset($this->output)) {
            $this->warn($message);
        } else {
            Log::channel('ai')->warning("SyncUnifiedPerformanceLite: {$message}");
        }
    }
}
