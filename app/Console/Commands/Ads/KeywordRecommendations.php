<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsKeywordPerformanceReport;
use App\Models\AmzAdsKeywordPerformanceReportSb;
use App\Models\AmzKeywordRecommendation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\AmzAdsKeywordSb;
use App\Models\AmzAdsGroups;
use App\Models\AmzAdsKeywords;
use App\Models\KeywordBidRecommendationRule;
use App\Models\SpKeywordSugByAdgroup;
use Illuminate\Support\Facades\DB;

class KeywordRecommendations extends Command
{
    protected $signature = 'app:keyword-recommendations';
    protected $description = 'Amz Ads Keyword Performance Daily Report';

    public function handle()
    {
        $this->processRecommendations(
            AmzAdsKeywordPerformanceReport::where('cost', '>', 0),
            'SP'
        );

        // Run for SB
        $this->processRecommendations(
            AmzAdsKeywordPerformanceReportSb::where('cost', '>', 0),
            'SB'
        );

        $this->info('✅ Keyword recommendations updated successfully for SP & SB.');
    }

    /**
     * Process recommendations for a given model and campaign type
     */
    private function processRecommendations($baseQuery, string $campaignType): void
    {
        $marketTz  = config('timezone.market');
        $dayStart  = Carbon::now($marketTz)->startOfDay()->subDay();
        $yesterday = $dayStart->toDateString();
        $start7d   = $dayStart->copy()->subDays(6)->toDateString();
        $start14d  = $dayStart->copy()->subDays(13)->toDateString();
        $start30d  = $dayStart->copy()->subDays(29)->toDateString();

        $salesCol    = $campaignType === 'SB' ? 'sales7d' : 'sales1d';
        $purchaseCol = $campaignType === 'SB' ? 'purchases7d' : 'purchases1d';

        $query = $baseQuery->selectRaw("
            keyword_id,
            campaign_id,
            country,
            keyword_text,

            -- 1d
            SUM(CASE WHEN c_date = ? THEN cost ELSE 0 END)               as total_spend,
            SUM(CASE WHEN c_date = ? THEN {$salesCol} ELSE 0 END)        as total_sales,
            SUM(CASE WHEN c_date = ? THEN {$purchaseCol} ELSE 0 END)     as purchases1d,
            SUM(CASE WHEN c_date = ? THEN clicks ELSE 0 END)             as clicks_1d,
            SUM(CASE WHEN c_date = ? THEN impressions ELSE 0 END)        as impressions_1d,

            -- 7d
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN cost ELSE 0 END)               as total_spend_7d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN {$salesCol} ELSE 0 END)        as total_sales_7d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN {$purchaseCol} ELSE 0 END)     as purchases1d_7d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN clicks ELSE 0 END)             as clicks_7d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN impressions ELSE 0 END)        as impressions_7d,

            -- 14d
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN cost ELSE 0 END)               as total_spend_14d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN {$salesCol} ELSE 0 END)        as total_sales_14d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN {$purchaseCol} ELSE 0 END)     as purchases1d_14d,

            -- 30d
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN cost ELSE 0 END)           as total_spend_30d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN {$salesCol} ELSE 0 END)    as total_sales_30d,
            SUM(CASE WHEN c_date BETWEEN ? AND ? THEN {$purchaseCol} ELSE 0 END) as purchases7d_30d

        ", [
            // 1d
            $yesterday,
            $yesterday,
            $yesterday,
            $yesterday,
            $yesterday,

            // 7d
            $start7d,
            $yesterday,
            $start7d,
            $yesterday,
            $start7d,
            $yesterday,
            $start7d,
            $yesterday,
            $start7d,
            $yesterday,

            // 14d
            $start14d,
            $yesterday,
            $start14d,
            $yesterday,
            $start14d,
            $yesterday,

            // 30d
            $start30d,
            $yesterday,
            $start30d,
            $yesterday,
            $start30d,
            $yesterday,

        ])
            ->whereBetween('c_date', [$start30d, $yesterday])
            ->groupBy('keyword_id', 'campaign_id', 'country', 'keyword_text')
            ->orderBy('keyword_id');


        $rules = KeywordBidRecommendationRule::where('is_active', 1)
            ->orderBy('priority')
            ->get();

        $query->chunk(1000, function ($chunk) use ($campaignType, $yesterday, $rules) {
            $keywordIds = $chunk->pluck('keyword_id')->filter()->unique();

            // Load keyword & ad group data
            $keywords = $campaignType === 'SP'
                ? AmzAdsKeywords::whereIn('keyword_id', $keywordIds)->get(['keyword_id', 'bid', 'ad_group_id'])
                : AmzAdsKeywordSb::whereIn('keyword_id', $keywordIds)->get(['keyword_id', 'bid', 'ad_group_id']);

            $keywordMap = $keywords->keyBy('keyword_id');
            $adGroupIds = $keywords->pluck('ad_group_id')->filter()->unique();
            $groups     = AmzAdsGroups::whereIn('ad_group_id', $adGroupIds)->get(['ad_group_id', 'default_bid']);
            $groupMap   = $groups->keyBy('ad_group_id');
            
            // Load bid suggestions for SP campaigns
            $bidSuggestions = [];
            if ($campaignType === 'SP') {
                $suggestions = SpKeywordSugByAdgroup::whereIn('keyword_id', $keywordIds)
                    ->orderBy('keyword_id')
                    ->orderByDesc('created_at') // Get latest first
                    ->get(['keyword_id', 'bid_start', 'bid_suggestion', 'bid_end']);
                
                $bidSuggestions = [];
                foreach ($suggestions as $sug) {
                    // Keep only the first (latest) record per keyword_id
                    if (!isset($bidSuggestions[$sug->keyword_id])) {
                        $bidSuggestions[$sug->keyword_id] = $sug;
                    }
                }
            }

            $data = $chunk->map(function ($row) use ($campaignType, $keywordMap, $groupMap, $bidSuggestions, $yesterday, $rules) {
                // --- Yesterday (1d) ---
                $clicks1d      = max(1, $row->clicks_1d);
                $impressions1d = max(1, $row->impressions_1d);

                $conversionRate1d = $row->purchases1d > 0
                    ? round(($row->purchases1d / $clicks1d) * 100, 2)
                    : 0;

                $ctr1d = round(($clicks1d / $impressions1d) * 100, 2);
                $cpc1d = $row->clicks_1d > 0
                    ? round($row->total_spend / $row->clicks_1d, 2)
                    : 0;
                $acos1d = $row->total_sales > 0
                    ? round(($row->total_spend / $row->total_sales) * 100, 2)
                    : 0;

                // --- 7d ---
                $clicks7d      = max(1, $row->clicks_7d);
                $impressions7d = max(1, $row->impressions_7d);

                $conversionRate7d = $row->purchases1d_7d > 0
                    ? round(($row->purchases1d_7d / $clicks7d) * 100, 2)
                    : 0;

                $ctr7d = round(($clicks7d / $impressions7d) * 100, 2);
                $cpc7d = $row->clicks_7d > 0
                    ? round($row->total_spend_7d / $row->clicks_7d, 2)
                    : 0;
                $acos7d = $row->total_sales_7d > 0
                    ? round(($row->total_spend_7d / $row->total_sales_7d) * 100, 2)
                    : 0;

                // --- 14d ---
                $acos14d = $row->total_sales_14d > 0
                    ? round(($row->total_spend_14d / $row->total_sales_14d) * 100, 2)
                    : 0;

                // --- 30d ---
                $acos30d = $row->total_sales_30d > 0
                    ? round(($row->total_spend_30d / $row->total_sales_30d) * 100, 2)
                    : 0;

                // --- Bid resolution ---
                $bid = 0;
                if ($keywordMap->has($row->keyword_id)) {
                    $kw  = $keywordMap[$row->keyword_id];
                    $bid = $kw->bid;
                    if (empty($bid) || $bid == 0.00) {
                        $bid = $groupMap[$kw->ad_group_id]->default_bid ?? 0;
                    }
                }

                // Get bid suggestion data for SP
                $bidMin = 0;
                $bidRange = 0;
                $bidMax = 0;
                if ($campaignType === 'SP' && isset($bidSuggestions[$row->keyword_id])) {
                    $suggestion = $bidSuggestions[$row->keyword_id];
                    $bidMin = $suggestion->bid_start;
                    $bidMax = $suggestion->bid_end;
                    $bidRange = $suggestion->bid_suggestion; // This is the suggested bid
                }

                $reco = self::getRecommendation([
                    'ctr'            => $ctr1d,
                    'conversionRate' => $conversionRate1d,
                    'acos'           => $acos7d,
                    'clicks'         => $row->clicks_1d,
                    'sales'          => $row->total_sales_7d,
                    'impressions'    => $row->impressions_1d,
                    'bid'            => $bid,
                ], $rules);


                return [
                    'keyword_id'        => $row->keyword_id ?? "N/A",
                    'campaign_id'       => $row->campaign_id ?? "N/A",
                    'keyword'           => $row->keyword_text,
                    'date'              => $yesterday,
                    'country'           => $row->country,
                    'campaign_types'    => $campaignType,

                    // 1d
                    'total_spend'       => $row->total_spend,
                    'total_sales'       => $row->total_sales,
                    'purchases1d'       => $row->purchases1d,
                    'clicks'            => $row->clicks_1d,
                    'impressions'       => $row->impressions_1d,
                    'conversion_rate'   => $conversionRate1d,
                    'ctr'               => $ctr1d,
                    'cpc'               => $cpc1d,
                    'acos'              => $acos1d,

                    // 7d
                    'total_spend_7d'     => $row->total_spend_7d,
                    'total_sales_7d'     => $row->total_sales_7d,
                    'purchases1d_7d'     => $row->purchases1d_7d,
                    'clicks_7d'          => $row->clicks_7d,
                    'impressions_7d'     => $row->impressions_7d,
                    'conversion_rate_7d' => $conversionRate7d,
                    'ctr_7d'             => $ctr7d,
                    'cpc_7d'             => $cpc7d,
                    'acos_7d'            => $acos7d,

                    // 14d
                    'total_spend_14d'   => $row->total_spend_14d,
                    'total_sales_14d'   => $row->total_sales_14d,
                    'purchases1d_14d'   => $row->purchases1d_14d,
                    'acos_14d'          => $acos14d,

                    'total_spend_30d'    => $row->total_spend_30d,
                    'total_sales_30d'    => $row->total_sales_30d,
                    'purchases7d_30d'    => $row->purchases7d_30d,
                    'acos_30d'           => $acos30d,

                    // Recommendation
                    'bid'              => $bid,
                    'suggested_bid'    => $reco['new_bid'],
                    'recommendation'   => $reco['message'],
                    
                    // Bid suggestions (SP only)
                    's_bid_min'        => $bidMin,
                    's_bid_range'      => $bidRange,
                    's_bid_max'        => $bidMax,
                ];
            })->toArray();

            AmzKeywordRecommendation::upsert(
                $data,
                ['keyword_id', 'date', 'country', 'campaign_types', 'campaign_id'],
                [
                    'campaign_id',
                    'keyword',
                    'total_spend',
                    'total_sales',
                    'purchases1d',
                    'clicks',
                    'impressions',
                    'conversion_rate',
                    'ctr',
                    'cpc',
                    'acos',
                    'total_spend_7d',
                    'total_sales_7d',
                    'purchases1d_7d',
                    'clicks_7d',
                    'impressions_7d',
                    'conversion_rate_7d',
                    'ctr_7d',
                    'cpc_7d',
                    'acos_7d',
                    'total_spend_14d',
                    'total_sales_14d',
                    'purchases1d_14d',
                    'acos_14d',
                    'total_spend_30d',
                    'total_sales_30d',
                    'purchases7d_30d',
                    'acos_30d',
                    'bid',
                    'suggested_bid',
                    'recommendation',
                    's_bid_min',
                    's_bid_range',
                    's_bid_max',
                ]
            );
        });
    }


    /**
     * Generate keyword optimization recommendation
     */
    public static function getRecommendation(array $metrics, $rules): array
    {
        // Cast metrics to float/int as needed
        $ctr            = $metrics['ctr'];
        $conversionRate = $metrics['conversionRate'];
        $acos           = $metrics['acos'];
        $clicks         = $metrics['clicks'];
        $sales          = $metrics['sales'];
        $impressions    = $metrics['impressions'];
        $bid            = $metrics['bid'] ?? 0;

        // Rule 1
        $rule = $rules[0];
        if ($ctr > (float)$rule['ctr_condition'] && $conversionRate > (float)$rule['conversion_condition'] && $acos < (float)$rule['acos_condition']) {
            $result['message'] = $rule['action_label'];
            $result['new_bid'] = $rule['bid_adjustment'] === '❌ Pause'
                ? '❌ Pause'
                : round($bid * (float)$rule['bid_adjustment'], 2);
            return $result;
        }

        // Rule 2
        $rule = $rules[1];
        if ($ctr > (float)$rule['ctr_condition'] && $conversionRate < (float)$rule['conversion_condition'] && $acos > (float)$rule['acos_condition']) {
            $result['message'] = $rule['action_label'];
            $result['new_bid'] = $rule['bid_adjustment'] === '❌ Pause'
                ? '❌ Pause'
                : round($bid * (float)$rule['bid_adjustment'], 2);
            return $result;
        }

        // Rule 3
        $rule = $rules[2];
        if ($ctr < (float)$rule['ctr_condition'] && $sales == (float)$rule['sales_condition']) {
            $result['message'] = $rule['action_label'];
            $result['new_bid'] = $rule['bid_adjustment']; // Pause or numeric handled directly
            return $result;
        }

        // Rule 4
        $rule = $rules[3];
        if (
            $ctr >= (float)$rule['ctr_condition'] && $ctr <= 1
            && $conversionRate >= (float)$rule['conversion_condition'] && $conversionRate <= 15
            && $acos > (float)$rule['acos_condition']
        ) {
            $result['message'] = $rule['action_label'];
            $result['new_bid'] = $rule['bid_adjustment'] === '❌ Pause'
                ? '❌ Pause'
                : round($bid * (float)$rule['bid_adjustment'], 2);
            return $result;
        }

        // Rule 5
        $rule = $rules[4];
        if ($impressions > (float)$rule['impressions_condition'] && $ctr < (float)$rule['ctr_condition']) {
            return [
                'message' => $rule['action_label'],
                'new_bid' => $rule['bid_adjustment'],
            ];
        }

        // Rule 6
        $rule = $rules[5];
        if ($ctr > (float)$rule['ctr_condition'] && $sales > (float)$rule['sales_condition'] && $acos > (float)$rule['acos_condition']) {
            $result['message'] = $rule['action_label'];
            $result['new_bid'] = $rule['bid_adjustment'] === '❌ Pause'
                ? '❌ Pause'
                : round($bid * (float)$rule['bid_adjustment'], 2);
            return $result;
        }

        // Rule 7
        $rule = $rules[6];
        if ($clicks > (float)$rule['click_condition'] && $sales == (float)$rule['sales_condition']) {
            return [
                'message' => $rule['action_label'],
                'new_bid' => $rule['bid_adjustment'],
            ];
        }

        // Rule 8: Default / No action needed
        $rule = $rules[7] ?? null;
        return [
            'message' => $rule['action_label'] ?? "✅ No action needed, keyword performing within acceptable limits.",
            'new_bid' => $bid,
        ];

        return $result;
    }
}
