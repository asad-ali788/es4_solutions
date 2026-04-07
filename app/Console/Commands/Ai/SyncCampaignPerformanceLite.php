<?php

namespace App\Console\Commands\Ai;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sync campaign performance data to campaign_performance_lite table
 * 
 * This command stores filtered campaign data for faster querying
 * and reads campaign metrics from source report tables.
 */
class SyncCampaignPerformanceLite extends Command implements ShouldQueue
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
    protected $signature = 'app:sync-campaign-performance-lite 
                            {--date= : Specific date to sync (YYYY-MM-DD)}
                            {--from= : Start date for range sync (YYYY-MM-DD)}
                            {--to= : End date for range sync (YYYY-MM-DD)}
                            {--force : Force re-sync by deleting existing data first}';

    protected $description = 'Sync campaign performance data to lite table - upserts to avoid partial data';

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
            $forceOption = false;

            if (isset($this->input)) {
                $dateOption = $this->option('date');
                $fromOption = $this->option('from');
                $toOption = $this->option('to');
                $forceOption = $this->option('force');
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
                    $this->logError("❌ From date cannot be after To date");
                    return;
                }

                $this->logMessage("📅 Date range mode: syncing {$fromDate} to {$toDate}");

                while ($from->lte($to)) {
                    $datesToSync[] = $from->toDateString();
                    $from->addDay();
                }
            } elseif ($fromOption || $toOption) {
                $this->logError("❌ Both --from and --to options are required for range sync");
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
                $result = $this->syncDate($dateToSync, $forceOption);
                $totalSynced += $result['synced'];

                if ($result['error']) {
                    $this->logError("❌ Error syncing {$dateToSync}: {$result['error']}");
                } else {
                    $this->logMessage("✅ {$dateToSync}: {$result['synced']} campaigns synced in {$result['duration']}s");
                }
            }

            $totalDuration = round(microtime(true) - $totalStartTime, 2);
            $this->logMessage("\n🎉 Total: {$totalSynced} campaigns across " . count($datesToSync) . " date(s) in {$totalDuration}s");
        } catch (\Throwable $e) {
            Log::error('SyncCampaignPerformanceLite failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->logError("❌ Fatal error: {$e->getMessage()}");
        }
    }

    /**
     * Sync data for a single date
     */
    protected function syncDate(string $date, bool $force = false): array
    {
        $startTime = microtime(true);

        try {
            // Delete existing data for this date if force flag is set
            if ($force) {
                DB::table('campaign_performance_lite')
                    ->where('report_date', $date)
                    ->delete();
                $this->logMessage("   Cleared existing data for {$date}");
            }

            $campaigns = collect(DB::select(<<<'SQL'
                SELECT
                    src.campaign_id,
                    src.campaign_name,
                    src.report_date,
                    src.campaign_types,
                    src.country,
                    src.total_daily_budget,
                    src.total_spend,
                    src.total_sales,
                    src.purchases7d,
                    src.acos,
                    src.campaign_state,
                    src.sp_targeting_type,
                    src.asin
                FROM (
                    SELECT
                        r.campaign_id,
                        COALESCE(sp.campaign_name, CONCAT('SP-', r.campaign_id)) AS campaign_name,
                        DATE(r.c_date) AS report_date,
                        'SP' AS campaign_types,
                        COALESCE(r.country, sp.country) AS country,
                        COALESCE(MAX(sp.daily_budget), 0) AS total_daily_budget,
                        COALESCE(SUM(r.cost), 0) AS total_spend,
                        COALESCE(SUM(r.sales7d), 0) AS total_sales,
                        COALESCE(SUM(r.purchases7d), 0) AS purchases7d,
                        CASE
                            WHEN COALESCE(SUM(r.sales7d), 0) > 0 THEN (COALESCE(SUM(r.cost), 0) / COALESCE(SUM(r.sales7d), 0)) * 100
                            ELSE 0
                        END AS acos,
                        UPPER(MAX(sp.campaign_state)) AS campaign_state,
                        MAX(sp.targeting_type) AS sp_targeting_type,
                        r.asin AS asin
                    FROM amz_ads_product_performance_report r
                    LEFT JOIN amz_campaigns sp ON r.campaign_id = sp.campaign_id AND sp.deleted_at IS NULL
                    WHERE r.deleted_at IS NULL AND r.campaign_id IS NOT NULL AND r.c_date IS NOT NULL
                    GROUP BY r.campaign_id, DATE(r.c_date), COALESCE(r.country, sp.country), COALESCE(sp.campaign_name, CONCAT('SP-', r.campaign_id)), r.asin

                    UNION ALL

                    SELECT
                        r.campaign_id,
                        COALESCE(sd.campaign_name, CONCAT('SD-', r.campaign_id)) AS campaign_name,
                        r.date AS report_date,
                        'SD' AS campaign_types,
                        COALESCE(r.country, sd.country) AS country,
                        COALESCE(MAX(sd.daily_budget), 0) AS total_daily_budget,
                        COALESCE(SUM(r.cost), 0) AS total_spend,
                        COALESCE(SUM(r.sales), 0) AS total_sales,
                        COALESCE(SUM(r.purchases), 0) AS purchases7d,
                        CASE
                            WHEN COALESCE(SUM(r.sales), 0) > 0 THEN (COALESCE(SUM(r.cost), 0) / COALESCE(SUM(r.sales), 0)) * 100
                            ELSE 0
                        END AS acos,
                        UPPER(MAX(sd.campaign_state)) AS campaign_state,
                        NULL AS sp_targeting_type,
                        r.asin AS asin
                    FROM amz_ads_product_performance_report_sd r
                    LEFT JOIN amz_campaigns_sd sd ON r.campaign_id = sd.campaign_id AND sd.deleted_at IS NULL
                    WHERE r.deleted_at IS NULL AND r.campaign_id IS NOT NULL AND r.date IS NOT NULL
                    GROUP BY r.campaign_id, r.date, COALESCE(r.country, sd.country), COALESCE(sd.campaign_name, CONCAT('SD-', r.campaign_id)), r.asin

                    UNION ALL

                    SELECT
                        r.campaign_id,
                        COALESCE(r.campaign_name, sb.campaign_name, CONCAT('SB-', r.campaign_id)) AS campaign_name,
                        DATE(r.c_date) AS report_date,
                        'SB' AS campaign_types,
                        COALESCE(r.country, sb.country) AS country,
                        COALESCE(MAX(sb.daily_budget), 0) AS total_daily_budget,
                        0 AS total_spend,
                        COALESCE(SUM(r.sales14d), 0) AS total_sales,
                        COALESCE(SUM(r.orders14d), 0) AS purchases7d,
                        0 AS acos,
                        UPPER(MAX(sb.campaign_state)) AS campaign_state,
                        NULL AS sp_targeting_type,
                        r.asin AS asin
                    FROM amz_ads_sb_purchased_product_reports r
                    LEFT JOIN amz_campaigns_sb sb ON r.campaign_id = sb.campaign_id AND sb.deleted_at IS NULL
                    WHERE r.deleted_at IS NULL AND r.campaign_id IS NOT NULL AND r.c_date IS NOT NULL
                    GROUP BY r.campaign_id, DATE(r.c_date), COALESCE(r.country, sb.country), COALESCE(r.campaign_name, sb.campaign_name, CONCAT('SB-', r.campaign_id)), r.asin
                ) AS src
                WHERE src.report_date = ?
            SQL, [$date]));

            if ($campaigns->isEmpty()) {
                return [
                    'synced' => 0,
                    'duration' => round(microtime(true) - $startTime, 2),
                    'error' => null,
                    'message' => 'No campaigns found for this date',
                ];
            }

            // Prepare bulk insert data
            $insertData = [];
            foreach ($campaigns as $campaign) {
                $insertData[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'report_date' => $campaign->report_date,
                    'campaign_types' => $campaign->campaign_types,
                    'country' => $campaign->country,
                    'asin' => $campaign->asin,
                    'total_daily_budget' => $campaign->total_daily_budget ?? 0,
                    'total_spend' => $campaign->total_spend ?? 0,
                    'total_sales' => $campaign->total_sales ?? 0,
                    'purchases7d' => $campaign->purchases7d ?? 0,
                    'acos' => round((float) ($campaign->acos ?? 0), 2),
                    'campaign_state' => isset($campaign->campaign_state)
                        ? strtoupper((string) $campaign->campaign_state)
                        : null,
                    'sp_targeting_type' => $campaign->sp_targeting_type,
                ];
            }

            // Bulk upsert - updates if exists, inserts if new
            // Chunks to avoid memory issues with large datasets
            $upsertChunkSize = 1000;
            foreach (array_chunk($insertData, $upsertChunkSize) as $chunk) {
                DB::table('campaign_performance_lite')
                    ->upsert($chunk, ['campaign_id', 'report_date', 'campaign_types', 'country', 'asin'], [
                        'campaign_name',
                        'campaign_types',
                        'country',
                        'asin',
                        'total_daily_budget',
                        'total_spend',
                        'total_sales',
                        'purchases7d',
                        'acos',
                        'campaign_state',
                        'sp_targeting_type',
                    ]);
            }

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'synced' => count($insertData),
                'duration' => $duration,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::error("SyncCampaignPerformanceLite failed for date {$date}", [
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

    /**
     * Validate date format
     */
    protected function validateDateFormat(string $date): void
    {
        try {
            Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            $this->logError("❌ Invalid date format: {$date}. Expected: YYYY-MM-DD");
            if (isset($this->input)) {
                exit(1);
            } else {
                throw new \Exception("Invalid date format: {$date}");
            }
        }
    }
    
    protected function logMessage($message)
    {
        if (isset($this->output)) {
            $this->line($message);
        } else {
            Log::channel('ads')->info("SyncCampaignPerformanceLite: {$message}");
        }
    }

    protected function logError($message)
    {
        if (isset($this->output)) {
            $this->error($message);
        } else {
            Log::channel('ads')->error("SyncCampaignPerformanceLite: {$message}");
        }
    }
}
