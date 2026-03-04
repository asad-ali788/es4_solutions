<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Ai\GenerateAiRecommendation;
use App\Models\AmzAdsKeywordPerformanceReport;
use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordSb;
use App\Models\AmzAdsTargetsPerformanceReportSb;
use App\Models\AmzAdsTargetsPerformanceReportSd;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzKeywordRecommendation;
use App\Models\AmzTargetingClauses;
use App\Models\AmzTargetingClausesSb;
use App\Models\AmzTargetRecommendation;
use App\Models\AmzTargetsSd;
use App\Models\CampaignRecommendations;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiRecommendationController extends Controller
{
    public function keywordgenerate($id)
    {
        try {
            $keyword = AmzKeywordRecommendation::findOrFail($id);

            // Try to fetch related keyword record safely
            $query = null;
            if ($keyword->campaign_types === "SP") {
                $query = AmzAdsKeywords::where('keyword_id', $keyword->keyword_id)->first();
            } elseif ($keyword->campaign_types === "SB") {
                $query = AmzAdsKeywordSb::where('keyword_id', $keyword->keyword_id)->first();
            }

            // Update AI status
            $keyword->update([
                'ai_status'         => 'pending',
                'ai_recommendation' => null,
            ]);

            // Build JSON-safe metrics (fallbacks to "N/A" if missing)
            $metrics = [
                // 'keyword'         => $keyword->keyword ?? 'N/A',
                'current_bid'     => $query->bid ?? 'N/A',
                'clicks'          => $keyword->clicks ?? 0,
                'cpc'             => $keyword->cpc ?? 0,
                'ctr'             => $keyword->ctr ?? 0,
                'orders'          => $keyword->orders ?? 0,
                'total_spend'     => $keyword->total_spend ?? 0,
                'total_sales'     => $keyword->total_sales ?? 0,
                'conversion_rate' => $keyword->conversion_rate ?? 0,
                'acos'            => $keyword->acos ?? 0,
                'campaign_types'  => $keyword->campaign_types ?? 'N/A',
                // 'recommendation'  => $keyword->recommendation ?? 'N/A',
                'impressions'     => $keyword->impressions ?? 0,
                'type'            => 'keywords',
            ];

            // Instruction for AI
            $instruction = "Here are the campaign metrics:\n" . json_encode($metrics, JSON_PRETTY_PRINT);

            if (!empty($metrics['current_bid'])) {
                $instruction .= "\n\nPlease analyze the performance and suggest whether the current bid ({$metrics['current_bid']}) should be increased, decreased, or kept the same to improve results.";
            } else {
                $instruction .= "\n\nNote: No current bid is available. Please suggest an appropriate bid adjustment strategy based on the performance metrics.";
            }
            // Dispatch job to AI queue
            GenerateAiRecommendation::dispatch(
                'keyword',
                AmzKeywordRecommendation::class,
                $keyword->id,
                $instruction,
                'ai_suggested_bid'
            )->onQueue('ai');

            return response()->json(['status' => 'queued']);
        } catch (\Throwable $e) {
            Log::error("Error in keywordgenerate(): " . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'keyword' => $id,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while queueing AI recommendation.'
            ], 500);
        }
    }

    public function keywordStatus($id)
    {
        $keyword = AmzKeywordRecommendation::select('id', 'ai_status', 'ai_recommendation', 'ai_suggested_bid')
            ->findOrFail($id);

        return response()->json($keyword);
    }

    // public function campaigngenerate($id)
    // {
    //     try {
    //         $campaign = CampaignRecommendations::findOrFail($id);

    //         // Try to fetch related keyword record safely
    //         $query = null;
    //         if ($campaign->campaign_types === "SP") {
    //             $query = AmzCampaigns::where('campaign_id', $campaign->campaign_id)->first();
    //         } elseif ($campaign->campaign_types === "SB") {
    //             $query = AmzCampaignsSb::where('campaign_id', $campaign->campaign_id)->first();
    //         }

    //         // Update AI status
    //         $campaign->update([
    //             'ai_status'         => 'pending',
    //             'ai_recommendation' => null,
    //         ]);

    //         // Build JSON-safe metrics
    //         $metrics = [
    //             'campaign_id'         => (string) $campaign->campaign_id ?? 'N/A',
    //             'country'             => $campaign->country ?? 'N/A',
    //             'campaign_types'      => $campaign->campaign_types ?? 'N/A',
    //             'daily_budget'        => $query->daily_budget ?? $campaign->total_daily_budget ?? 0,
    //             'yesterday_spend'     => $campaign->total_spend ?? 0,
    //             'yesterday_sales'     => $campaign->total_sales ?? 0,
    //             'yesterday_purchases' => $campaign->purchases7d ?? 0,
    //             'total_spend_7d'      => $campaign->total_spend_7d ?? 0,
    //             'total_sales_7d'      => $campaign->total_sales_7d ?? 0,
    //             'purchases7d_7d'      => $campaign->purchases7d_7d ?? 0,
    //             'acos_7d'             => $campaign->acos_7d ?? 0,
    //             'total_spend_14d'     => $campaign->total_spend_14d ?? 0,
    //             'total_sales_14d'     => $campaign->total_sales_14d ?? 0,
    //             'purchases7d_14d'     => $campaign->purchases7d_14d ?? 0,
    //             'type'                => 'campaign',
    //         ];
    //         // Instruction for AI
    //         $instruction = "Here are the campaign metrics:\n" . json_encode($metrics, JSON_PRETTY_PRINT);

    //         if (!empty($metrics['daily_budget'])) {
    //             $instruction .= "\n\nPlease analyze the performance and suggest whether the current bid ({$metrics['daily_budget']}) should be increased, decreased, or kept the same to improve results.";
    //         } else {
    //             $instruction .= "\n\nNote: No current bid is available. Please suggest an appropriate bid adjustment strategy based on the performance metrics.";
    //         }
    //         // Dispatch job to AI queue
    //         GenerateAiRecommendation::dispatch(
    //             'campaign',
    //             CampaignRecommendations::class,
    //             $campaign->id,
    //             $instruction,
    //             'ai_suggested_budget'
    //         )->onQueue('ai');

    //         return response()->json(['status' => 'queued']);
    //     } catch (\Throwable $e) {
    //         Log::error("Error in keywordgenerate(): " . $e->getMessage(), [
    //             'trace'   => $e->getTraceAsString(),
    //             'keyword' => $id,
    //         ]);

    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Something went wrong while queueing AI recommendation.'
    //         ], 500);
    //     }
    // }
    // public function campaignStatus($id)
    // {
    //     $keyword = CampaignRecommendations::select('id', 'ai_status', 'ai_recommendation', 'ai_suggested_budget')
    //         ->findOrFail($id);

    //     return response()->json($keyword);
    // }

    public function targetgenerate($id)
    {
        try {
            $target = AmzTargetRecommendation::findOrFail($id);

            $metrics = [
                'clicks' => $target->clicks ?? 0,
                'impressions' => $target->impressions ?? 0,
                'total_spend' => $target->total_spend ?? 0,
                'conversion_rate' => $target->conversion_rate ?? 0,
                'ctr' => $target->ctr ?? 0,
                'cpc' => $target->cpc ?? 0,
                'campaign_types' => $target->campaign_types ?? 'N/A',
                'type' => 'targets',
            ];

            if ($target->campaign_types === 'SP') {
                $sp = AmzTargetingClauses::where('target_id', $target->targeting_id)->first();
                if ($sp) {
                    $perf = AmzAdsKeywordPerformanceReport::where('keyword_id', $sp->target_id)->first();
                    $metrics['current_bid'] = $sp->bid ?? 'N/A';
                    $metrics['total_sales'] = $perf->sales7d ?? 0;
                    $metrics['orders'] = $perf->purchases7d ?? 0;
                }
            } elseif ($target->campaign_types === 'SB') {
                $sb = AmzTargetingClausesSb::where('target_id', $target->targeting_id)->first();
                if ($sb) {
                    $perf = AmzAdsTargetsPerformanceReportSb::where('targeting_id', $sb->target_id)->first();
                    $metrics['current_bid'] = $sb->bid ?? 'N/A';
                    $metrics['total_sales'] = $perf->sales ?? 0;
                    $metrics['orders'] = $perf->purchases ?? 0;
                }
            } elseif ($target->campaign_types === 'SD') {
                $sdPerf = AmzAdsTargetsPerformanceReportSd::select(
                    'amz_ads_targets_performance_report_sd.targeting_id',
                    'amz_ads_targets_performance_report_sd.sales',
                    'amz_ads_targets_performance_report_sd.purchases',
                    'amz_ads_groups_sd.default_bid'
                )
                    ->join('amz_ads_groups_sd', 'amz_ads_targets_performance_report_sd.campaign_id', '=', 'amz_ads_groups_sd.campaign_id')
                    ->where('amz_ads_targets_performance_report_sd.targeting_id', $target->targeting_id)
                    ->where('amz_ads_targets_performance_report_sd.ad_group_id', $target->ad_group_id)
                    ->where('amz_ads_targets_performance_report_sd.campaign_id', $target->campaign_id)
                    ->where('amz_ads_groups_sd.default_bid', '>', 0)
                    // ->whereDate('amz_ads_targets_performance_report_sd.c_date', now()->subDay()->toDateString()) // yesterday's data
                    ->where('amz_ads_targets_performance_report_sd.cost', '>', 0)
                    ->latest('amz_ads_targets_performance_report_sd.c_date')
                    ->first();

                if ($sdPerf) {
                    $metrics['current_bid'] = $sdPerf->default_bid ?? 'N/A';
                    $metrics['total_sales'] = $sdPerf->sales ?? 0;
                    $metrics['orders'] = $sdPerf->purchases ?? 0;
                } else {
                    $metrics['current_bid'] = 'N/A';
                    $metrics['total_sales'] = 0;
                    $metrics['orders'] = 0;
                }
            }

            $instruction = "Here are the campaign metrics:\n" . json_encode($metrics, JSON_PRETTY_PRINT);

            if (!empty($metrics['current_bid']) && $metrics['current_bid'] !== 'N/A') {
                $instruction .= "\n\nPlease analyze the performance and suggest whether the current bid ({$metrics['current_bid']}) should be increased, decreased, or kept the same to improve results.";
            } else {
                $instruction .= "\n\nNote: No current bid is available. Please suggest an appropriate bid adjustment strategy based on the performance metrics.";
            }

            // Dispatch AI job
            GenerateAiRecommendation::dispatch(
                'target',
                AmzTargetRecommendation::class,
                $target->id,
                $instruction,
                'ai_suggested_bid'
            )->onQueue('ai');

            return response()->json(['status' => 'queued']);
        } catch (\Throwable $e) {
            Log::error("Error in targetgenerate(): " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'target' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while queueing AI recommendation.'
            ], 500);
        }
    }



    public function targetStatus($id)
    {
        $target = AmzTargetRecommendation::select('id', 'ai_status', 'ai_recommendation', 'ai_suggested_bid')
            ->findOrFail($id);

        return response()->json($target);
    }
}
