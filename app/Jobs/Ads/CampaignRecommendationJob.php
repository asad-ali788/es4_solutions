<?php

namespace App\Jobs\Ads;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CampaignBudgetRecommendationRule;
use App\Models\CampaignRecommendations;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Batch size for upserts to avoid memory spikes.
     */
    protected int $upsertBatch = 500;

    /**
     * Common columns used in all CampaignRecommendations::upsert() calls.
     */
    protected array $upsertColumns = [
        'campaign_name',
        'country',
        'enabled_campaigns_count',
        'total_daily_budget',
        'total_spend',
        'total_sales',
        'acos',
        'purchases7d',

        'total_spend_7d',
        'total_sales_7d',
        'purchases7d_7d',
        'acos_7d',

        'total_spend_14d',
        'total_sales_14d',
        'purchases7d_14d',
        'acos_14d',

        'total_spend_30d',
        'total_sales_30d',
        'purchases7d_30d',
        'acos_30d',

        'suggested_budget',
        'recommendation',

        'from_group',
        'to_group',
    ];

    public function handle()
    {
        $marketTz      = config('timezone.market') ?: config('app.timezone');
        $dayStart      = Carbon::now($marketTz)->subDay()->startOfDay(); // yesterday
        $day7Start     = $dayStart->copy()->subDays(6);   // last 7 days including yesterday
        $day14Start    = $dayStart->copy()->subDays(13);  // last 14 days including yesterday
        $day30Start    = $dayStart->copy()->subDays(29);  // last 30 days including yesterday
        $yesterdayDate = $dayStart->toDateString();

        $rules = CampaignBudgetRecommendationRule::where('is_active', 1)
            ->orderBy('priority')
            ->get()
            ->toArray();

        if (empty($rules)) {
            Log::channel('ads')->warning('CampaignRecommendationJob: no active recommendation rules found — aborting.');
            return;
        }

        Log::channel('ads')->info("▶️ CampaignRecommendationJob started for report date {$yesterdayDate}");

        // Check each dataset independently, and run the corresponding processor if report exists.
        $existsSp = DB::table('amz_ads_campaign_performance_report')
            ->whereDate('c_date', $yesterdayDate)
            ->exists();

        $existsSb = DB::table('amz_ads_campaign_performance_reports_sb')
            ->whereDate('date', $yesterdayDate)
            ->exists();

        $existsSd = DB::table('amz_ads_campaign_performance_report_sd')
            ->whereDate('c_date', $yesterdayDate)
            ->exists();

        if ($existsSp) {
            $this->processSp($dayStart, $day7Start, $day14Start, $day30Start, $rules);
        } else {
            Log::channel('ads')->info("SP report not found for {$yesterdayDate}, skipping SP processing.");
        }

        if ($existsSb) {
            $this->processSb($dayStart, $day7Start, $day14Start, $day30Start, $rules);
        } else {
            Log::channel('ads')->info("SB report not found for {$yesterdayDate}, skipping SB processing.");
        }

        if ($existsSd) {
            $this->processSd($dayStart, $day7Start, $day14Start, $day30Start, $rules);
        } else {
            Log::channel('ads')->info("SD report not found for {$yesterdayDate}, skipping SD processing.");
        }

        Log::channel('ads')->info("✅ CampaignRecommendationJob finished for date {$yesterdayDate}");
    }

    /**
     * Shared helper: flush batched rows to DB and clear the array.
     */
    protected function flushUpserts(array &$rowsToUpsert): void
    {
        if (empty($rowsToUpsert)) {
            return;
        }

        CampaignRecommendations::upsert(
            $rowsToUpsert,
            ['campaign_id', 'report_week', 'campaign_types'],
            $this->upsertColumns
        );

        // clear for reuse
        $rowsToUpsert = [];
    }

    protected function computeGroup(float $acos, float $spend, float $sales): int
    {
        $acos  = max(0, (float) $acos);
        $spend = max(0, (float) $spend);
        $sales = max(0, (float) $sales);

        if ($spend == 0.0 && $sales == 0.0) return 4;
        if ($spend > 0.0 && $acos == 0.0)   return 3;
        if ($spend > 0.0 && $acos > 0.0 && $acos <= 30.0) return 1;
        if ($spend > 0.0 && $acos > 30.0)   return 2;

        // fallback (edge case)
        return 0;
    }

    protected function processSp(Carbon $dayStart, Carbon $day7Start, Carbon $day14Start, Carbon $day30Start, array $rules)
    {
        $dateYesterday = $dayStart->toDateString();
        Log::channel('ads')->info("▶️ Processing SP recommendations for {$dateYesterday}");

        $prevDate = $dayStart->copy()->subDay()->toDateString();

        $prevGroups = DB::table('campaign_recommendations')
            ->whereDate('report_week', $prevDate)
            ->where('campaign_types', 'SP')
            ->pluck('to_group', 'campaign_id')
            ->toArray();

        // --- Reusable Date Condition Variables (Optimization) ---
        $d1  = "daily.c_date = '{$dateYesterday}'";
        $d7  = "daily.c_date BETWEEN '{$day7Start->toDateString()}' AND '{$dateYesterday}'";
        $d14 = "daily.c_date BETWEEN '{$day14Start->toDateString()}' AND '{$dateYesterday}'";
        $d30 = "daily.c_date BETWEEN '{$day30Start->toDateString()}' AND '{$dateYesterday}'";
        // --------------------------------------------------------

        $metricsQuery = DB::table('amz_ads_campaign_performance_report as daily')
            ->select([
                'daily.campaign_id',

                // --- 1-Day Metrics (Combined Sums) ---
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.c_budget ELSE 0 END) as total_daily_budget,
                         SUM(CASE WHEN {$d1} THEN daily.cost ELSE 0 END) as total_spend,
                         SUM(CASE WHEN {$d1} THEN daily.sales7d ELSE 0 END) as total_sales,
                         SUM(CASE WHEN {$d1} THEN daily.purchases7d ELSE 0 END) as purchases7d"),

                // 1d ACOS
                DB::raw("CASE WHEN SUM(CASE WHEN {$d1} THEN daily.sales7d ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d1} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d1} THEN daily.sales7d ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_1d"),

                // --- 7-Day Metrics (Combined Sums + Budget Gap) ---
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.cost ELSE 0 END) as total_spend_7d,
                         SUM(CASE WHEN {$d7} THEN daily.sales7d ELSE 0 END) as total_sales_7d,
                         SUM(CASE WHEN {$d7} THEN daily.purchases7d ELSE 0 END) as purchases7d_7d,
                         CASE WHEN SUM(CASE WHEN {$d7} AND daily.budget_gap = 1 THEN 1 ELSE 0 END) > 4
                              THEN 1 ELSE 0 END as budget_gap"),

                // 7d ACOS
                DB::raw("CASE WHEN SUM(CASE WHEN {$d7} THEN daily.sales7d ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d7} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d7} THEN daily.sales7d ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_7d"),

                // --- 14-Day Metrics (Combined Sums) ---
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.cost ELSE 0 END) as total_spend_14d,
                         SUM(CASE WHEN {$d14} THEN daily.sales7d ELSE 0 END) as total_sales_14d,
                         SUM(CASE WHEN {$d14} THEN daily.purchases7d ELSE 0 END) as purchases7d_14d"),

                // 14d ACOS
                DB::raw("CASE WHEN SUM(CASE WHEN {$d14} THEN daily.sales7d ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d14} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d14} THEN daily.sales7d ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_14d"),

                // --- 30-Day Metrics (Combined Sums) ---
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.cost ELSE 0 END) as total_spend_30d,
                         SUM(CASE WHEN {$d30} THEN daily.sales7d ELSE 0 END) as total_sales_30d,
                         SUM(CASE WHEN {$d30} THEN daily.purchases7d ELSE 0 END) as purchases7d_30d"),

                // 30d ACOS
                DB::raw("CASE WHEN SUM(CASE WHEN {$d30} THEN daily.sales7d ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d30} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d30} THEN daily.sales7d ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_30d"),
            ])
            ->groupBy('daily.campaign_id');

        $campaigns = DB::table('amz_campaigns as campaigns')
            ->joinSub($metricsQuery, 'metrics', function ($join) {
                $join->on('metrics.campaign_id', '=', 'campaigns.campaign_id');
            })
            // ->whereRaw("
            //     COALESCE(metrics.total_spend,0) +
            //     COALESCE(metrics.total_sales,0) +
            //     COALESCE(metrics.total_spend_7d,0) +
            //     COALESCE(metrics.total_sales_7d,0) +
            //     COALESCE(metrics.total_spend_14d,0) +
            //     COALESCE(metrics.total_sales_14d,0) +
            //     COALESCE(metrics.total_spend_30d,0) +
            //     COALESCE(metrics.total_sales_30d,0)
            //     > 0
            // ")
            ->select(
                'campaigns.campaign_id',
                'campaigns.campaign_name',
                'campaigns.country',
                'campaigns.campaign_type',
                'metrics.total_daily_budget',
                'metrics.total_spend',
                'metrics.total_sales',
                'metrics.acos_1d',
                'metrics.purchases7d',
                'metrics.budget_gap',
                'metrics.total_spend_7d',
                'metrics.total_sales_7d',
                'metrics.purchases7d_7d',
                'metrics.acos_7d',
                'metrics.total_spend_14d',
                'metrics.total_sales_14d',
                'metrics.purchases7d_14d',
                'metrics.acos_14d',
                'metrics.total_spend_30d',
                'metrics.total_sales_30d',
                'metrics.purchases7d_30d',
                'metrics.acos_30d'
            )
            ->get();

        $rowsToUpsert = [];

        foreach ($campaigns as $campaign) {
            if ($campaign->total_daily_budget <= 0) {
                continue;
            }

            $metricPack = [
                'acos'               => $campaign->acos_7d,
                'spend'              => $campaign->total_spend_7d,
                'total_daily_budget' => $campaign->total_daily_budget,
                'budget_gap'         => $campaign->budget_gap,
            ];

            $reco = self::getRecommendation($metricPack, $rules);
            // get the group of today and previous
            $campaignId = (int) $campaign->campaign_id;

            $toGroup = $this->computeGroup(
                (float) ($campaign->acos_1d ?? 0),
                (float) ($campaign->total_spend ?? 0),
                (float) ($campaign->total_sales ?? 0),
            );
            $fromGroup = isset($prevGroups[$campaignId]) ? (int) $prevGroups[$campaignId] : 0;

            $rowsToUpsert[] = [
                'campaign_id'             => $campaign->campaign_id,
                'report_week'             => $dayStart->toDateString(),
                'campaign_types'          => $campaign->campaign_type,
                'campaign_name'           => $campaign->campaign_name,
                'country'                 => $campaign->country,
                'enabled_campaigns_count' => $campaign->enabled_campaigns_count ?? 1,

                // 1d
                'total_daily_budget'      => $campaign->total_daily_budget,
                'total_spend'             => $campaign->total_spend,
                'total_sales'             => $campaign->total_sales,
                'acos'                    => $campaign->acos_1d,
                'purchases7d'             => $campaign->purchases7d,

                // 7d
                'total_spend_7d'          => $campaign->total_spend_7d,
                'total_sales_7d'          => $campaign->total_sales_7d,
                'purchases7d_7d'          => $campaign->purchases7d_7d,
                'acos_7d'                 => $campaign->acos_7d,

                // 14d
                'total_spend_14d'         => $campaign->total_spend_14d,
                'total_sales_14d'         => $campaign->total_sales_14d,
                'purchases7d_14d'         => $campaign->purchases7d_14d,
                'acos_14d'                => $campaign->acos_14d,

                // 30d
                'total_spend_30d'         => $campaign->total_spend_30d,
                'total_sales_30d'         => $campaign->total_sales_30d,
                'purchases7d_30d'         => $campaign->purchases7d_30d,
                'acos_30d'                => $campaign->acos_30d,

                'suggested_budget'        => $reco['new_budget'],
                'recommendation'          => $reco['message'],

                'from_group'              => $fromGroup,  // yesterday's to_group
                'to_group'                => $toGroup,    // calculated for today
            ];

            if (count($rowsToUpsert) >= $this->upsertBatch) {
                $this->flushUpserts($rowsToUpsert);
            }
        }

        // final flush
        $this->flushUpserts($rowsToUpsert);

        Log::channel('ads')->info("✅ SP processing completed for {$dateYesterday}");
    }

    protected function processSb(Carbon $dayStart, Carbon $day7Start, Carbon $day14Start, Carbon $day30Start, array $rules)
    {
        $dateYesterday = $dayStart->toDateString();
        Log::channel('ads')->info("▶️ Processing SB recommendations for {$dateYesterday}");

        $prevDate = $dayStart->copy()->subDay()->toDateString();
        $prevGroups = DB::table('campaign_recommendations')
            ->whereDate('report_week', $prevDate)
            ->where('campaign_types', 'SB')
            ->pluck('to_group', 'campaign_id')
            ->toArray();

        // --- Reusable Date Condition Variables (same style as SP) ---
        $d1  = "daily.date = '{$dateYesterday}'";
        $d7  = "daily.date BETWEEN '{$day7Start->toDateString()}' AND '{$dateYesterday}'";
        $d14 = "daily.date BETWEEN '{$day14Start->toDateString()}' AND '{$dateYesterday}'";
        $d30 = "daily.date BETWEEN '{$day30Start->toDateString()}' AND '{$dateYesterday}'";
        // ------------------------------------------------------------

        $metricsQuery = DB::table('amz_ads_campaign_performance_reports_sb as daily')
            ->select([
                'daily.campaign_id',

                // 1d metrics
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.c_budget   ELSE 0 END) as total_daily_budget"),
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.cost      ELSE 0 END) as total_spend"),
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.sales     ELSE 0 END) as total_sales"),
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.unitsSold ELSE 0 END) as units_sold"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d1} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d1} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d1} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_1d"),

                // 7d metrics
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.cost      ELSE 0 END) as total_spend_7d"),
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.sales     ELSE 0 END) as total_sales_7d"),
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.unitsSold ELSE 0 END) as units_sold_7d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d7} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d7} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d7} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_7d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d7} AND daily.budget_gap = 1 THEN 1 ELSE 0 END) > 4
                              THEN 1 ELSE 0 END as budget_gap"),

                // 14d metrics
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.cost      ELSE 0 END) as total_spend_14d"),
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.sales     ELSE 0 END) as total_sales_14d"),
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.unitsSold ELSE 0 END) as units_sold_14d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d14} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d14} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d14} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_14d"),

                // 30d metrics
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.cost      ELSE 0 END) as total_spend_30d"),
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.sales     ELSE 0 END) as total_sales_30d"),
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.unitsSold ELSE 0 END) as units_sold_30d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d30} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d30} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d30} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_30d"),
            ])
            ->groupBy('daily.campaign_id');

        $campaignsSb = DB::table('amz_campaigns_sb as campaigns')
            ->joinSub($metricsQuery, 'metrics', function ($join) {
                $join->on('metrics.campaign_id', '=', 'campaigns.campaign_id');
            })
            // ->whereRaw("
            //     COALESCE(metrics.total_spend,0) +
            //     COALESCE(metrics.total_sales,0) +
            //     COALESCE(metrics.total_spend_7d,0) +
            //     COALESCE(metrics.total_sales_7d,0) +
            //     COALESCE(metrics.total_spend_14d,0) +
            //     COALESCE(metrics.total_sales_14d,0) +
            //     COALESCE(metrics.total_spend_30d,0) +
            //     COALESCE(metrics.total_sales_30d,0)
            //     > 0
            // ")
            ->select(
                'campaigns.campaign_id',
                'campaigns.campaign_name',
                'campaigns.country',
                'campaigns.campaign_type',

                'metrics.total_daily_budget',
                'metrics.total_spend',
                'metrics.total_sales',
                'metrics.units_sold',
                'metrics.acos_1d',

                'metrics.budget_gap',

                'metrics.total_spend_7d',
                'metrics.total_sales_7d',
                'metrics.units_sold_7d',
                'metrics.acos_7d',

                'metrics.total_spend_14d',
                'metrics.total_sales_14d',
                'metrics.units_sold_14d',
                'metrics.acos_14d',

                'metrics.total_spend_30d',
                'metrics.total_sales_30d',
                'metrics.units_sold_30d',
                'metrics.acos_30d'
            )
            ->get();

        $rowsToUpsert = [];

        foreach ($campaignsSb as $campaignSb) {
            if ($campaignSb->total_daily_budget <= 0) {
                continue;
            }

            // For rule engine we still use 7d data
            $metricPack = [
                'acos'               => $campaignSb->acos_7d,
                'spend'              => $campaignSb->total_spend_7d,
                'total_daily_budget' => $campaignSb->total_daily_budget,
                'budget_gap'         => $campaignSb->budget_gap,
            ];

            $reco = self::getRecommendation($metricPack, $rules);
            // get the group of today and previous
            $campaignId = (int) $campaignSb->campaign_id;

            $toGroup = $this->computeGroup(
                (float) ($campaignSb->acos_1d ?? 0),
                (float) ($campaignSb->total_spend ?? 0),
                (float) ($campaignSb->total_sales ?? 0),
            );

            $fromGroup = isset($prevGroups[$campaignId]) ? (int) $prevGroups[$campaignId] : 0;

            $rowsToUpsert[] = [
                'campaign_id'             => $campaignSb->campaign_id,
                'report_week'             => $dayStart->toDateString(),
                'campaign_types'          => $campaignSb->campaign_type,
                'campaign_name'           => $campaignSb->campaign_name,
                'country'                 => $campaignSb->country,
                'enabled_campaigns_count' => 1,

                // 1d
                'total_daily_budget'      => $campaignSb->total_daily_budget,
                'total_spend'             => $campaignSb->total_spend,
                'total_sales'             => $campaignSb->total_sales,
                'acos'                    => $campaignSb->acos_1d,
                'purchases7d'             => $campaignSb->units_sold,

                // 7d
                'total_spend_7d'          => $campaignSb->total_spend_7d,
                'total_sales_7d'          => $campaignSb->total_sales_7d,
                'purchases7d_7d'          => $campaignSb->units_sold_7d,
                'acos_7d'                 => $campaignSb->acos_7d,

                // 14d
                'total_spend_14d'         => $campaignSb->total_spend_14d,
                'total_sales_14d'         => $campaignSb->total_sales_14d,
                'purchases7d_14d'         => $campaignSb->units_sold_14d,
                'acos_14d'                => $campaignSb->acos_14d,

                // 30d
                'total_spend_30d'         => $campaignSb->total_spend_30d,
                'total_sales_30d'         => $campaignSb->total_sales_30d,
                'purchases7d_30d'         => $campaignSb->units_sold_30d,
                'acos_30d'                => $campaignSb->acos_30d,

                'suggested_budget'        => $reco['new_budget'],
                'recommendation'          => $reco['message'],

                'from_group'              => $fromGroup,  // yesterday's to_group
                'to_group'                => $toGroup,    // calculated for today
            ];

            if (count($rowsToUpsert) >= $this->upsertBatch) {
                $this->flushUpserts($rowsToUpsert);
            }
        }

        // final flush
        $this->flushUpserts($rowsToUpsert);

        Log::channel('ads')->info("✅ SB processing completed for {$dateYesterday}");
    }

    protected function processSd(Carbon $dayStart, Carbon $day7Start, Carbon $day14Start, Carbon $day30Start, array $rules)
    {
        $dateYesterday = $dayStart->toDateString();
        Log::channel('ads')->info("▶️ Starting SD recommendations processing for {$dateYesterday}");

        $prevDate = $dayStart->copy()->subDay()->toDateString();

        $prevGroups = DB::table('campaign_recommendations')
            ->whereDate('report_week', $prevDate)
            ->where('campaign_types', 'SD')
            ->pluck('to_group', 'campaign_id')
            ->toArray();

        // Reusable date snippets (same style as SP/SB)
        $d1  = "daily.c_date = '{$dateYesterday}'";
        $d7  = "daily.c_date BETWEEN '{$day7Start->toDateString()}' AND '{$dateYesterday}'";
        $d14 = "daily.c_date BETWEEN '{$day14Start->toDateString()}' AND '{$dateYesterday}'";
        $d30 = "daily.c_date BETWEEN '{$day30Start->toDateString()}' AND '{$dateYesterday}'";

        $metricsQuery = DB::table('amz_ads_campaign_performance_report_sd as daily')
            ->select([
                'daily.campaign_id',

                // 1d metrics
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.campaign_budget_amount ELSE 0 END) as total_daily_budget"),
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.cost                ELSE 0 END) as total_spend"),
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.sales               ELSE 0 END) as total_sales"),
                DB::raw("SUM(CASE WHEN {$d1} THEN daily.units_sold          ELSE 0 END) as units_sold"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d1} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d1} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d1} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_1d"),

                // 7d metrics
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.cost       ELSE 0 END) as total_spend_7d"),
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.sales      ELSE 0 END) as total_sales_7d"),
                DB::raw("SUM(CASE WHEN {$d7} THEN daily.units_sold ELSE 0 END) as units_sold_7d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d7} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d7} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d7} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_7d"),

                // 14d metrics
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.cost       ELSE 0 END) as total_spend_14d"),
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.sales      ELSE 0 END) as total_sales_14d"),
                DB::raw("SUM(CASE WHEN {$d14} THEN daily.units_sold ELSE 0 END) as units_sold_14d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d14} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d14} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d14} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_14d"),

                // 30d metrics
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.cost       ELSE 0 END) as total_spend_30d"),
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.sales      ELSE 0 END) as total_sales_30d"),
                DB::raw("SUM(CASE WHEN {$d30} THEN daily.units_sold ELSE 0 END) as units_sold_30d"),
                DB::raw("CASE WHEN SUM(CASE WHEN {$d30} THEN daily.sales ELSE 0 END) > 0
                              THEN ROUND(SUM(CASE WHEN {$d30} THEN daily.cost ELSE 0 END)
                                         / SUM(CASE WHEN {$d30} THEN daily.sales ELSE 0 END) * 100, 2)
                              ELSE 0 END as acos_30d"),
            ])
            ->groupBy('daily.campaign_id');

        Log::channel('ads')->info("📊 Metrics query built for SD campaigns.");

        $campaigns = DB::table('amz_campaigns_sd as campaigns')
            ->joinSub($metricsQuery, 'metrics', function ($join) {
                $join->on('metrics.campaign_id', '=', 'campaigns.campaign_id');
            })
            // ->whereRaw("
            //     COALESCE(metrics.total_spend,0) +
            //     COALESCE(metrics.total_sales,0) +
            //     COALESCE(metrics.total_spend_7d,0) +
            //     COALESCE(metrics.total_sales_7d,0) +
            //     COALESCE(metrics.total_spend_14d,0) +
            //     COALESCE(metrics.total_sales_14d,0) +
            //     COALESCE(metrics.total_spend_30d,0) +
            //     COALESCE(metrics.total_sales_30d,0)
            //     > 0
            // ")
            ->select(
                'campaigns.campaign_id',
                'campaigns.campaign_name',
                'campaigns.country',
                'campaigns.campaign_type',

                'metrics.total_daily_budget',
                'metrics.total_spend',
                'metrics.total_sales',
                'metrics.units_sold',
                'metrics.acos_1d',

                'metrics.total_spend_7d',
                'metrics.total_sales_7d',
                'metrics.units_sold_7d',
                'metrics.acos_7d',

                'metrics.total_spend_14d',
                'metrics.total_sales_14d',
                'metrics.units_sold_14d',
                'metrics.acos_14d',

                'metrics.total_spend_30d',
                'metrics.total_sales_30d',
                'metrics.units_sold_30d',
                'metrics.acos_30d'
            )
            ->get();

        Log::channel('ads')->info("✅ Fetched " . count($campaigns) . " SD campaigns for processing.");

        $rowsToUpsert = [];

        foreach ($campaigns as $sd) {
            if ($sd->total_daily_budget <= 0) {
                // Log::channel('ads')->info("⏭ Skipping campaign {$sd->campaign_id} due to zero daily budget.");
                continue;
            }

            // Rule engine still uses 7d metrics (same idea as SP/SB)
            $metricPack = [
                'acos'               => $sd->acos_7d,
                'spend'              => $sd->total_spend_7d,
                'total_daily_budget' => $sd->total_daily_budget,
            ];

            $reco = self::getRecommendation($metricPack, $rules);
            // get the group of today and previous
            $campaignId = (int) $sd->campaign_id;

            $toGroup = $this->computeGroup(
                (float) ($sd->acos_1d ?? 0),
                (float) ($sd->total_spend ?? 0),
                (float) ($sd->total_sales ?? 0),
            );

            $fromGroup = isset($prevGroups[$campaignId]) ? (int) $prevGroups[$campaignId] : 0;

            $rowsToUpsert[] = [
                'campaign_id'             => $sd->campaign_id,
                'report_week'             => $dayStart->toDateString(),
                'campaign_types'          => $sd->campaign_type,
                'campaign_name'           => $sd->campaign_name,
                'country'                 => $sd->country,
                'enabled_campaigns_count' => 1,

                // 1d
                'total_daily_budget'      => $sd->total_daily_budget,
                'total_spend'             => $sd->total_spend,
                'total_sales'             => $sd->total_sales,
                'acos'                    => $sd->acos_1d,
                'purchases7d'             => $sd->units_sold,

                // 7d
                'total_spend_7d'          => $sd->total_spend_7d,
                'total_sales_7d'          => $sd->total_sales_7d,
                'purchases7d_7d'          => $sd->units_sold_7d,
                'acos_7d'                 => $sd->acos_7d,

                // 14d
                'total_spend_14d'         => $sd->total_spend_14d,
                'total_sales_14d'         => $sd->total_sales_14d,
                'purchases7d_14d'         => $sd->units_sold_14d,
                'acos_14d'                => $sd->acos_14d,

                // 30d
                'total_spend_30d'         => $sd->total_spend_30d,
                'total_sales_30d'         => $sd->total_sales_30d,
                'purchases7d_30d'         => $sd->units_sold_30d,
                'acos_30d'                => $sd->acos_30d,

                'suggested_budget'        => $reco['new_budget'],
                'recommendation'          => $reco['message'],

                'from_group'              => $fromGroup,  // yesterday's to_group
                'to_group'                => $toGroup,    // calculated for today
            ];

            if (count($rowsToUpsert) >= $this->upsertBatch) {
                $this->flushUpserts($rowsToUpsert);
            }
        }

        // final flush
        $this->flushUpserts($rowsToUpsert);

        Log::channel('ads')->info("✅ Completed SD recommendations processing for {$dateYesterday}");
    }

    public static function getRecommendation(array $metrics, $rules): array
    {
        $acos       = $metrics['acos'] ?? 0.0;
        $spend      = $metrics['spend'] ?? 0.0;
        $budget     = $metrics['total_daily_budget'] ?? 0.0;
        $budget_gap = $metrics['budget_gap'] ?? false;

        $result = [
            'message'    => '✅ No Action',
            'new_budget' => $budget,
        ];

        // Rule 1
        if ($acos > $rules[0]['min_acos'] && $acos <= $rules[0]['max_acos'] && $budget_gap) {
            $result['message']    = $rules[0]['action_label'];
            $result['new_budget'] = round($budget * (1 + $rules[0]['adjustment_value'] / 100), 2);
            return $result;
        }

        // Rule 2
        if ($acos > $rules[1]['min_acos'] && $acos <= $rules[1]['max_acos'] && $spend <= $budget) {
            $result['message']    = $rules[1]['action_label'];
            $result['new_budget'] = round($budget, 2);
            return $result;
        }

        // Rule 3
        if ($acos > $rules[2]['min_acos'] && $acos <= $rules[2]['max_acos']) {
            $result['message']    = $rules[2]['action_label'];
            $result['new_budget'] = round($budget, 2);
            return $result;
        }

        // Rule 4
        if ($acos > $rules[3]['min_acos']) {
            $result['message']    = $rules[3]['action_label'];
            $result['new_budget'] = round($budget * (1 - $rules[3]['adjustment_value'] / 100), 2);
            return $result;
        }

        // Rule 5
        if ($acos == $rules[4]['min_acos'] && $spend > 0) {
            $result['message']    = $rules[4]['action_label'];
            $result['new_budget'] = round($budget * (1 - $rules[4]['adjustment_value'] / 100), 2);
            return $result;
        }

        return $result;
    }
}
