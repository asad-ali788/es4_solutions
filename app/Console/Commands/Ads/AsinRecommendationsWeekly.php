<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsProducts;
use App\Models\AmzAdsProductsSb;
use App\Models\AmzCampaigns;
use App\Models\AsinRecommendation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsinRecommendationsWeekly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:asin-recommendations-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Generate Weekly ASIN Performance Recommendations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz  = config('timezone.market');
        // last full week (Mon → Sun)
        $lastWeekStart = Carbon::now($marketTz)->startOfWeek(Carbon::MONDAY)->subWeek();
        $lastWeekEnd   = Carbon::now($marketTz)->endOfWeek(Carbon::SUNDAY)->subWeek();

        $asins = AmzAdsProducts::query()
            ->select('amz_ads_products.asin', 'amz_ads_products.country', 'amz_ads_products.state')
            ->selectRaw('COUNT(DISTINCT amz_campaigns.campaign_id) as campaigns_count')
            ->selectRaw('COALESCE(SUM(report.cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(report.sales7d), 0) as total_sales7d')
            ->selectRaw('COALESCE(SUM(report.c_budget), 0) as total_c_budget')
            ->selectRaw('
                    CASE 
                        WHEN SUM(report.sales7d) > 0 
                        THEN ROUND((SUM(report.cost) / SUM(report.sales7d)) * 100, 2) 
                        ELSE 0 
                    END as acos
                ')
            ->selectRaw('COUNT(DISTINCT CASE WHEN report.c_status = "ENABLED" THEN report.campaign_id END) as enabled_campaigns_count')
            ->join('amz_campaigns', 'amz_ads_products.campaign_id', '=', 'amz_campaigns.campaign_id')
            ->leftJoin('amz_ads_campaign_performance_report as report', 'amz_ads_products.campaign_id', '=', 'report.campaign_id')
            ->where('amz_campaigns.campaign_state', 'ENABLED')
            ->where('amz_ads_products.state', 'ENABLED')
            ->whereBetween('report.c_date', [$lastWeekStart->toDateString(), $lastWeekEnd->toDateString()])
            // ->where('amz_ads_products.asin', 'B079DX9FQV') // optional filter
            ->groupBy('amz_ads_products.asin', 'amz_ads_products.country', 'amz_ads_products.state')
            ->get();
        // Example insert into asin_recommendations
        foreach ($asins as $asin) {
            $recommendation = '';
            if ($asin->acos < 30 && $asin->total_cost >= $asin->total_c_budget) {
                $recommendation = 'Increase budget 30%';
            } elseif ($asin->acos < 30 && $asin->total_cost < $asin->total_c_budget) {
                $recommendation = 'Keep same budget';
            } elseif ($asin->acos >= 30 && $asin->acos <= 40) {
                $recommendation = 'Keep same budget (optimize keywords/placements)';
            } elseif ($asin->acos > 40) {
                $recommendation = 'Reduce budget by 20% (campaign inefficient)';
            }

            // Save or update
            AsinRecommendation::updateOrCreate(
                [
                    'asin'           => $asin->asin,
                    'country'        => $asin->country,
                    'campaign_types' => 'SP',
                    'report_week'    => $lastWeekStart->toDateString(),
                ],
                [
                    'active_campaigns'        => $asin->campaigns_count,
                    'enabled_campaigns_count' => $asin->enabled_campaigns_count,
                    'total_daily_budget'      => $asin->total_c_budget,
                    'total_spend'             => $asin->total_cost,
                    'total_sales'             => $asin->total_sales7d,
                    'acos'                    => $asin->acos,
                    'country'                 => $asin->country,
                    'campaign_types'          => 'SP',
                    'recommendation'          => $recommendation,
                ]
            );
        }

        $asinsSb = AmzAdsProductsSb::query()
            ->select('amz_ads_products_sb.asin', 'amz_ads_products_sb.country', 'amz_ads_products_sb.state')
            ->selectRaw('COUNT(DISTINCT amz_campaigns_sb.campaign_id) as campaigns_count')
            ->selectRaw('COALESCE(SUM(report.cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(report.sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(report.c_budget), 0) as total_c_budget')
            ->selectRaw('
                    CASE 
                        WHEN SUM(report.sales) > 0 
                        THEN ROUND((SUM(report.cost) / SUM(report.sales)) * 100, 2) 
                        ELSE 0 
                    END as acos
                ')
            ->selectRaw('COUNT(DISTINCT CASE WHEN report.c_status = "ENABLED" THEN report.campaign_id END) as enabled_campaigns_count')
            ->join('amz_campaigns_sb', 'amz_ads_products_sb.campaign_id', '=', 'amz_campaigns_sb.campaign_id')
            ->leftJoin('amz_ads_campaign_performance_reports_sb as report', 'amz_ads_products_sb.campaign_id', '=', 'report.campaign_id')
            ->where('amz_campaigns_sb.campaign_state', 'ENABLED')
            ->where('amz_ads_products_sb.state', 'ENABLED')
            ->whereBetween(DB::raw('DATE(report.date)'), [
                $lastWeekStart->toDateString(),
                $lastWeekEnd->toDateString()
            ])
            ->groupBy('amz_ads_products_sb.asin', 'amz_ads_products_sb.country', 'amz_ads_products_sb.state')
            ->get();

        // Insert into asin_recommendations
        foreach ($asinsSb as $asin) {
            $recommendation = '';
            if ($asin->acos < 30 && $asin->total_cost >= $asin->total_c_budget) {
                $recommendation = 'Increase budget 30%';
            } elseif ($asin->acos < 30 && $asin->total_cost < $asin->total_c_budget) {
                $recommendation = 'Keep same budget';
            } elseif ($asin->acos >= 30 && $asin->acos <= 40) {
                $recommendation = 'Keep same budget (optimize keywords/placements)';
            } elseif ($asin->acos > 40) {
                $recommendation = 'Reduce budget by 20% (campaign inefficient)';
            }

            AsinRecommendation::updateOrCreate(
                [
                    'asin'           => $asin->asin ?? 'NA',
                    'country'        => $asin->country,
                    'campaign_types' => 'SB',
                    'report_week'    => $lastWeekStart->toDateString(),
                ],
                [
                    'active_campaigns'        => $asin->campaigns_count,
                    'enabled_campaigns_count' => $asin->enabled_campaigns_count,
                    'total_daily_budget'      => $asin->total_c_budget,
                    'total_spend'             => $asin->total_cost,
                    'total_sales'             => $asin->total_sales,
                    'acos'                    => $asin->acos,
                    'country'                 => $asin->country,
                    'campaign_types'          => 'SB',
                    'recommendation'          => $recommendation,
                ]
            );
        }
    }
}
