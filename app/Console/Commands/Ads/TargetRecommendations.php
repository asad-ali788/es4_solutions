<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsKeywordPerformanceReport;
use App\Models\AmzAdsTargetsPerformanceReportSb;
use App\Models\AmzAdsTargetsPerformanceReportSd;
use App\Models\AmzTargetRecommendation;
use App\Models\TargetBudgetRecommendationRule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TargetRecommendations extends Command
{
    protected $signature = 'app:target-recommendations';
    protected $description = 'ADS: Generate SD/SP Target Performance Recommendations';

    public function handle()
    {
        $marketTz = config('timezone.market');
        $dayStart = Carbon::now($marketTz)->startOfDay()->subDay();
        $rules = TargetBudgetRecommendationRule::where('is_active', 1)
            ->orderBy('priority')
            ->get();
        // $dayStart = Carbon::parse("2025-09-25");
        // SP
        $spQuery = AmzAdsKeywordPerformanceReport::where('cost', '>', 0)
            ->where('c_date', $dayStart->toDateString())
            ->join('amz_targeting_clauses', 'amz_targeting_clauses.target_id', '=', 'amz_ads_keyword_performance_report.keyword_id')
            ->select(
                'amz_ads_keyword_performance_report.keyword_id',
                'amz_ads_keyword_performance_report.campaign_id',
                'amz_ads_keyword_performance_report.ad_group_id',
                'amz_ads_keyword_performance_report.keyword_text',
                'amz_ads_keyword_performance_report.targeting',
                'amz_ads_keyword_performance_report.clicks',
                'amz_ads_keyword_performance_report.impressions',
                'amz_ads_keyword_performance_report.cost',
                'amz_ads_keyword_performance_report.sales7d',
                'amz_ads_keyword_performance_report.purchases7d',
                'amz_ads_keyword_performance_report.c_date',
                'amz_ads_keyword_performance_report.country',
                'amz_targeting_clauses.bid'
            );

        $this->processRecommendations($spQuery, 'SP', $rules);

        $sbQuery = AmzAdsTargetsPerformanceReportSb::where('cost', '>', 0)
            ->where('c_date', $dayStart->toDateString())
            ->join('amz_targeting_clauses_sb', 'amz_targeting_clauses_sb.target_id', '=', 'amz_ads_targets_performance_report_sb.targeting_id')
            ->select(
                'amz_ads_targets_performance_report_sb.targeting_id',
                'amz_ads_targets_performance_report_sb.campaign_id',
                'amz_ads_targets_performance_report_sb.ad_group_id',
                'amz_ads_targets_performance_report_sb.targeting',
                'amz_ads_targets_performance_report_sb.clicks',
                'amz_ads_targets_performance_report_sb.impressions',
                'amz_ads_targets_performance_report_sb.cost',
                'amz_ads_targets_performance_report_sb.sales',
                'amz_ads_targets_performance_report_sb.purchases',
                'amz_ads_targets_performance_report_sb.c_date',
                'amz_ads_targets_performance_report_sb.country',
                'amz_targeting_clauses_sb.bid'
            );


        $this->processRecommendations($sbQuery, 'SB', $rules);

        // SD
        $this->processRecommendations(AmzAdsTargetsPerformanceReportSd::where('cost', '>', 0)->where('c_date', $dayStart->toDateString()), 'SD', $rules);

        $this->info('✅ Target recommendations updated successfully.');
    }

    private function processRecommendations($query, string $campaignType, $rules): void
    {
        $query->chunk(1000, function ($chunk) use ($campaignType, $rules) {
            $data = $chunk->map(function ($row) use ($campaignType, $rules) {
                $targetingId = $row->targeting_id ?? $row->keyword_id;
                if (!$targetingId) {
                    return null;
                }

                $sales = ($campaignType === 'SP') ? $row->sales7d : $row->sales;
                $purchases = ($campaignType === 'SP') ? $row->purchases7d : $row->purchases;

                $clicks = max(1, $row->clicks);
                $impressions = max(1, $row->impressions);

                $conversionRate = ($purchases > 0) ? round(($purchases / $clicks) * 100, 2) : 0;
                $ctr = round(($row->clicks / $impressions) * 100, 2);
                $cpc = ($row->clicks > 0) ? round($row->cost / $row->clicks, 2) : 0;
                $acos = ($sales > 0) ? round(($row->cost / $sales) * 100, 2) : 0;

                $recommendation = self::getRecommendation([
                    'ctr' => $ctr,
                    'conversionRate' => $conversionRate,
                    'acos' => $acos,
                    'clicks' => $row->clicks,
                    'sales' => $sales,
                    'impressions' => $row->impressions,
                    'bid' => $row->bid ?? 0,
                ], $rules);

                return [
                    'targeting_id'   => $targetingId,
                    'targeting_text' => $row->targeting_text ?? $row->targeting,
                    'campaign_id'    => $row->campaign_id,
                    'ad_group_id'    => $row->ad_group_id,
                    'date'           => $row->c_date,
                    'country'        => $row->country,
                    'campaign_types' => $campaignType,

                    'total_spend'      => $row->cost,
                    'total_sales'      => $sales,
                    'orders'           => $purchases,
                    'clicks'           => $row->clicks,
                    'impressions'      => $row->impressions,
                    'conversion_rate'  => $conversionRate,
                    'acos'             => $acos,
                    'ctr'              => $ctr,
                    'cpc'              => $cpc,
                    'recommendation'   => $recommendation['message'] ?? null,
                    'suggested_bid'    => is_numeric($recommendation['new_bid']) ? $recommendation['new_bid'] : null,
                ];
            })->toArray();
            AmzTargetRecommendation::upsert(
                $data,
                ['targeting_id', 'date', 'country', 'campaign_types'],
                [
                    'campaign_id',
                    'ad_group_id',
                    'targeting_text',
                    'total_spend',
                    'total_sales',
                    'orders',
                    'clicks',
                    'impressions',
                    'conversion_rate',
                    'acos',
                    'ctr',
                    'cpc',
                    'recommendation',
                    'suggested_bid',
                ]
            );
        });
    }

    public static function getRecommendation(array $metrics, $rules): array
    {
        $ctr            = $metrics['ctr'];
        $conversionRate = $metrics['conversionRate'];
        $acos           = $metrics['acos'];
        $clicks         = $metrics['clicks'];
        $sales          = $metrics['sales'];
        $impressions    = $metrics['impressions'];
        $bid            = $metrics['bid'] ?? 0;
        $targetAcos     = 30;

        // Default result
        $result = [
            'message' => "✅ No action needed, target performing within acceptable limits.",
            'new_bid' => $bid,
        ];

        // Helper function to calculate new bid
        $calculateBid = function ($bid, $rule) {
            if ($rule->adjustment_type === 'pause') {
                return null;
            }
            if ($rule->adjustment_type === 'increase') {
                return round($bid * (1 + ((float)$rule->adjustment_value / 100)), 2);
            }
            if ($rule->adjustment_type === 'decrease') {
                return round($bid * (1 - ((float)$rule->adjustment_value / 100)), 2);
            }
            return $bid;
        };

        // Rule 0
        $rule = $rules[0];
        if ($ctr > $rule['min_ctr'] && $conversionRate > $rule['min_conversion_rate'] && $acos < $targetAcos) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        // Rule 1
        $rule = $rules[1];
        if ($ctr > $rule['min_ctr'] && $conversionRate < $rule['max_conversion_rate'] && $acos > $targetAcos) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        // Rule 2
        $rule = $rules[2];
        if ($ctr < $rule['max_ctr'] && $sales == $rule['min_sales']) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        // Rule 3
        $rule = $rules[3];
        if ($ctr >= $rule['min_ctr'] && $ctr <= $rule['max_ctr'] && $conversionRate >= $rule['min_conversion_rate'] && $conversionRate <= $rule['max_conversion_rate'] && $acos > $targetAcos) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        // Rule 4
        $rule = $rules[4];
        if ($impressions > $rule['min_impressions'] && $ctr < $rule['max_ctr']) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        // Rule 5
        $rule = $rules[5];
        if ($ctr > $rule['min_ctr'] && $sales > $rule['min_sales'] && $acos > $targetAcos) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        // Rule 6
        $rule = $rules[6];
        if ($clicks > $rule['min_clicks'] && $sales == $rule['min_sales']) {
            $result['message'] = $rule->action_label;
            $result['new_bid'] = $calculateBid($bid, $rule);
            return $result;
        }

        return $result;
    }
}
