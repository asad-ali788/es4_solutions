<?php

namespace App\Traits;

use App\Models\AmzKeywordRecommendation;
use App\Models\CampaignRecommendations;
use App\Models\CampaignBudgetRecommendationRule;
use App\Models\KeywordBidRecommendationRule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait HasFilteredAdsPerformance
{

    // Dependancy in the campaign performance and Ads Overview page
    public function getFilteredCampaignsQuery($filters)
    {
        // Use subquery joins for product_sp and product_sb (one asin/related_asins per campaign)
        $productSpSub = DB::table('amz_ads_products')
            ->select('campaign_id', DB::raw('MIN(asin) as asin'))
            ->groupBy('campaign_id');
        $productSbSub = DB::table('amz_ads_products_sb')
            ->select('campaign_id', DB::raw('MIN(related_asins) as related_asins'))
            ->groupBy('campaign_id');

        $query = CampaignRecommendations::query()
            ->leftJoin('amz_campaigns as sp', 'campaign_recommendations.campaign_id', '=', 'sp.campaign_id')
            ->leftJoin('amz_campaigns_sb as sb', 'campaign_recommendations.campaign_id', '=', 'sb.campaign_id')
            ->leftJoin('amz_campaigns_sd as sd', 'campaign_recommendations.campaign_id', '=', 'sd.campaign_id')
            ->leftJoinSub($productSpSub, 'product_sp', function ($join) {
                $join->on('campaign_recommendations.campaign_id', '=', 'product_sp.campaign_id');
            })
            ->leftJoinSub($productSbSub, 'product_sb', function ($join) {
                $join->on('campaign_recommendations.campaign_id', '=', 'product_sb.campaign_id');
            })
            ->leftJoin('amz_ads_products_sd as product_sd', 'campaign_recommendations.campaign_id', '=', 'product_sd.campaign_id')
            ->leftJoin('product_categorisations as pc_sp', function ($join) {
                $join->on('pc_sp.child_asin', '=', 'product_sp.asin')
                    ->whereNull('pc_sp.deleted_at');
            })
            ->leftJoin('product_categorisations as pc_sd', function ($join) {
                $join->on('pc_sd.child_asin', '=', 'product_sd.asin')
                    ->whereNull('pc_sd.deleted_at');
            });

        $query->select(
            'campaign_recommendations.*',
            'sp.campaign_state as sp_campaign_state',
            'sp.targeting_type as sp_targeting_type',
            'sb.campaign_state as sb_campaign_state',
            'sd.campaign_state as sd_campaign_state',
            'product_sp.asin',
            'product_sb.related_asins',
            'product_sd.asin as sd_asin',
            DB::raw('pc_sp.child_short_name as sp_product_name'),
            DB::raw('pc_sd.child_short_name as sd_product_name')
        )->distinct(['campaign_recommendations.campaign_id']);

        $marketTz = config('timezone.market');

        // Date filter
        $selectedWeek = $filters->input('date', Carbon::now($marketTz)->subDay()->toDateString());
        $query->where('campaign_recommendations.report_week', $selectedWeek);

        // Search (move ASIN resolution outside closure, use global cache)
        if ($filters->filled('search')) {
            $term   = trim($filters->input('search'));
            $search = "%{$term}%";

            // Use global cache for product_categorisations asin/name mapping
            $asinToName = cache()->remember('product_categorisations_asin_to_name', 60 * 24 * 30, function () {
                return DB::table('product_categorisations')
                    ->whereNull('deleted_at')
                    ->pluck('child_short_name', 'child_asin')
                    ->toArray();
            });

            // Find all asins whose name matches the search
            $matchedAsins = collect($asinToName)
                ->filter(function ($name) use ($search) {
                    // $search is a SQL LIKE pattern, so convert to regex
                    $pattern = '/' . str_replace('%', '.*', preg_quote(str_replace(['%', '_'], ['.*', '.'], $search), '/')) . '/i';
                    return preg_match($pattern, $name);
                })
                ->keys()
                ->all();

            $query->where(function ($sub) use ($search, $matchedAsins) {
                $sub->where('campaign_recommendations.campaign_id', 'like', $search)
                    ->orWhere('campaign_recommendations.campaign_name', 'like', $search)
                    ->orWhere('sd.campaign_state', 'like', $search)
                    ->orWhere('pc_sp.child_short_name', 'like', $search)
                    ->orWhere('pc_sd.child_short_name', 'like', $search)
                    ->orWhere('product_sd.asin', 'like', $search);

                if (!empty($matchedAsins)) {
                    $sub->orWhereIn('product_sp.asin', $matchedAsins)
                        ->orWhereIn('product_sd.asin', $matchedAsins)
                        ->orWhere(function ($sb) use ($matchedAsins) {
                            foreach ($matchedAsins as $asin) {
                                $sb->orWhereJsonContains('product_sb.related_asins', $asin);
                            }
                        });
                }
            });
        }
        // ACOS Sorting (supports acos, acos_14d, acos_30d, acos_7d)
        if ($filters->filled('sort_acos')) {
            $acosMap = [
                'acos' => 'campaign_recommendations.acos',
                'acos_14d' => 'campaign_recommendations.acos_14d',
                'acos_30d' => 'campaign_recommendations.acos_30d',
                'acos_7d' => 'campaign_recommendations.acos_7d',
            ];
            $sortColumn = $filters->sort_acos;
            $direction = 'asc';
            if (strpos($sortColumn, ':') !== false) {
                [$sortColumn, $dir] = explode(':', $sortColumn);
                $direction = strtolower($dir) === 'desc' ? 'desc' : 'asc';
            } else if (strtolower($filters->sort_direction ?? '') === 'desc') {
                $direction = 'desc';
            }
            if (isset($acosMap[$sortColumn])) {
                $query->orderBy($acosMap[$sortColumn], $direction);
            }
        }

        // Country
        $query->when($filters->filled('country') && $filters['country'] !== 'all', function ($q) use ($filters) {
            $q->where('campaign_recommendations.country', $filters['country']);
        });

        // Run status
        $query->when($filters->filled('run_status') && $filters['run_status'] !== 'all', function ($q) use ($filters) {
            $q->where('campaign_recommendations.run_status', $filters['run_status']);
        });

        // run_update
        $query->when($filters->filled('run_update') && $filters['run_update'] !== 'all', function ($q) use ($filters) {
            $q->where('campaign_recommendations.run_update', (bool) $filters['run_update']);
        });

        // Campaign type (SP/SB/SD)
        $query->when($filters->filled('campaign') && $filters['campaign'] !== 'all', function ($q) use ($filters) {
            $q->where('campaign_recommendations.campaign_types', $filters['campaign']);
        });
        // Campaign Targeting Type (SP) 
        $query->when(
            $filters->filled('sp_targeting_type') && $filters['sp_targeting_type'] !== 'all',
            function ($q) use ($filters) {
                $q->where('sp.targeting_type', $filters['sp_targeting_type']);
            }
        );

        // Rules filter
        $query->when($filters->filled('rules'), function ($q) use ($filters) {
            $labels = CampaignBudgetRecommendationRule::whereIn('id', $filters['rules'])
                ->pluck('action_label')
                ->toArray();

            $q->where(function ($sub) use ($labels) {
                foreach ($labels as $label) {
                    $sub->orWhere('campaign_recommendations.recommendation', 'LIKE', "%{$label}%");
                }
            });
        });

        // ASIN filter (SP/SB/SD) - move subqueries outside closure
        if ($filters->filled('asins')) {
            $asins = $filters['asins'];

            $spIds = DB::table('amz_ads_products')->whereIn('asin', $asins)->pluck('campaign_id')->toArray();
            $sbIds = DB::table('amz_ads_products_sb')
                ->where(function ($sub) use ($asins) {
                    foreach ($asins as $asin) {
                        $sub->orWhereJsonContains('related_asins', $asin);
                    }
                })
                ->pluck('campaign_id')->toArray();
            $sdIds = DB::table('amz_ads_products_sd')->whereIn('asin', $asins)->pluck('campaign_id')->toArray();

            $ids = array_unique(array_merge($spIds, $sbIds, $sdIds));
            $query->whereIn('campaign_recommendations.campaign_id', $ids);
        }

        // Campaign State (SP+SB+SD)
        $query->when($filters->filled('campaign_state') && $filters['campaign_state'] !== 'all', function ($q) use ($filters) {
            $state = $filters->input('campaign_state');
            $q->where(function ($sub) use ($state) {
                $sub->where('sp.campaign_state', $state)
                    ->orWhere('sb.campaign_state', $state)
                    ->orWhere('sd.campaign_state', $state);
            });
        });

        // ACOS Filter
        $hasPeriod = $filters->filled('period');
        $query->when(
            $filters->filled('acos') && $filters['acos'] !== 'all',
            function ($q) use ($filters, $hasPeriod) {
                $acosFilter = $filters['acos'];
                $period     = $filters->get('period', '7d'); // default 7d

                // Map period => column names
                switch ($period) {
                    case '1d':
                        $acosColumn  = 'campaign_recommendations.acos';
                        $spendColumn = 'campaign_recommendations.total_spend';
                        break;
                    case '14d':
                        $acosColumn  = 'campaign_recommendations.acos_14d';
                        $spendColumn = 'campaign_recommendations.total_spend_14d';
                        break;
                    case '30d':
                        $acosColumn  = 'campaign_recommendations.acos_30d';
                        $spendColumn = 'campaign_recommendations.total_spend_30d';
                        break;
                    case '7d':
                    default:
                        $acosColumn  = 'campaign_recommendations.acos_7d';
                        $spendColumn = 'campaign_recommendations.total_spend_7d';
                        break;
                }

                $q->where(function ($sub) use ($acosFilter, $acosColumn, $spendColumn, $hasPeriod) {

                    // CASE 1: NO period filter → keep full original behavior
                    if (! $hasPeriod) {
                        if ($acosFilter === '0') {
                            // ACOS = 0, but spend > 0
                            $sub->where($acosColumn, 0)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '30') {
                            // ACOS <= 30, spend > 0
                            $sub->where($acosColumn, '<=', 30)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '31') {
                            // ACOS > 30, spend > 0
                            $sub->where($acosColumn, '>', 30)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '3045') {
                            // 30 < ACOS < 45, spend > 0
                            $sub->where($acosColumn, '>', 30)
                                ->where($acosColumn, '<', 45)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '45') {
                            // ACOS >= 45, spend > 0
                            $sub->where($acosColumn, '>=', 45)
                                ->where($spendColumn, '>', 0);
                        }

                        return;
                    }

                    // CASE 2: period IS present → only 2 buckets
                    if ($acosFilter === '30') {
                        // ACOS <= 30, spend > 0
                        $sub->where($acosColumn, '<=', 30)
                            ->where($acosColumn, '>', 0)
                            ->where($spendColumn, '>', 0);
                    } elseif ($acosFilter === '31') {
                        // ACOS > 30, spend > 0
                        $sub->where($acosColumn, '>', 30)
                            ->where($spendColumn, '>', 0);
                    } elseif ($acosFilter === '0') {
                        // ACOS = 0 , spend > 0
                        $sub->where($acosColumn, 0)
                            ->where($spendColumn, '>', 0);
                    } elseif ($acosFilter === 'none') {
                        // ACOS = 0 , spend > 0
                        $sub->where($acosColumn, 0)
                            ->where($spendColumn, 0);
                    }
                    // For other filters (0, 3045, 45) when period exists → do nothing
                });
            }
        );

        return $query;
    }


    public function getFilteredKeywordsQuery($filters)
    {

        $marketTz = config('timezone.market');
        $selectedDate = $filters->input('date') ?? Carbon::now($marketTz)->subDay()->toDateString();

        // Use index-friendly date range
        $dateStart = $selectedDate;
        $dateEnd = Carbon::parse($selectedDate)->addDay()->toDateString();

        // Derived table for ads (one asin per campaign)
        $adsSub = DB::table('amz_ads_products')
            ->select('campaign_id', DB::raw('MIN(asin) as asin'))
            ->groupBy('campaign_id');

        // Derived table for ads_sb (one related_asins per campaign)
        $adsSbSub = DB::table('amz_ads_products_sb')
            ->select('campaign_id', DB::raw('MIN(related_asins) as related_asins'))
            ->groupBy('campaign_id');

        $query = AmzKeywordRecommendation::query()
            ->leftJoinSub($adsSub, 'ads', function ($join) {
                $join->on('amz_keyword_recommendations.campaign_id', '=', 'ads.campaign_id');
            })
            ->leftJoinSub($adsSbSub, 'ads_sb', function ($join) {
                $join->on('amz_keyword_recommendations.campaign_id', '=', 'ads_sb.campaign_id');
            })
            ->leftJoin('product_categorisations as pc', function ($join) {
                $join->on('pc.child_asin', '=', 'ads.asin')
                    ->whereNull('pc.deleted_at');
            })
            ->leftJoin('amz_campaigns as campaigns', 'amz_keyword_recommendations.campaign_id', '=', 'campaigns.campaign_id')
            ->leftJoin('amz_campaigns_sb as campaigns_sb', 'amz_keyword_recommendations.campaign_id', '=', 'campaigns_sb.campaign_id')
            ->leftJoin('amz_ads_keywords as sp_kw', 'amz_keyword_recommendations.keyword_id', '=', 'sp_kw.keyword_id')
            ->leftJoin('amz_ads_keyword_sb as sb_kw', 'amz_keyword_recommendations.keyword_id', '=', 'sb_kw.keyword_id');

        $query->select(
            'amz_keyword_recommendations.*',
            'sp_kw.state as sp_state',
            'sb_kw.state as sb_state',
            'ads.asin as asin',
            'ads_sb.related_asins as related_asin',
            'campaigns.campaign_name as c_name',
            'campaigns_sb.campaign_name as sb_c_name',
            DB::raw('pc.child_short_name as product_name')
        );

        // Index-friendly date filter
        $query->where('amz_keyword_recommendations.date', '>=', $dateStart)
            ->where('amz_keyword_recommendations.date', '<', $dateEnd);

        // 🔍 Search filter (move ASIN resolution outside closure)
        if ($filters->filled('search')) {
            $term = trim($filters->search);
            $search = '%' . $term . '%';

            $matchedAsins = DB::table('product_categorisations')
                ->whereNull('deleted_at')
                ->where('child_short_name', 'like', $search)
                ->pluck('child_asin')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $query->where(function ($q) use ($search, $matchedAsins) {
                $q->where('amz_keyword_recommendations.campaign_id', 'like', $search)
                    ->orWhere('amz_keyword_recommendations.keyword_id', 'like', $search)
                    ->orWhere('amz_keyword_recommendations.keyword', 'like', $search)
                    ->orWhere('amz_keyword_recommendations.total_sales', 'like', $search)
                    ->orWhere('amz_keyword_recommendations.acos', 'like', $search)
                    ->orWhere('campaigns.targeting_type', 'like', $search)
                    ->orWhere('ads.asin', 'like', $search)
                    ->orWhere('sp_kw.state', 'like', $search)
                    ->orWhere('campaigns.campaign_name', 'like', $search)
                    ->orWhere('campaigns_sb.campaign_name', 'like', $search)
                    ->orWhere('sb_kw.state', 'like', $search)
                    ->orWhere('pc.child_short_name', 'like', $search);

                if (!empty($matchedAsins)) {
                    $q->orWhere(function ($sb) use ($matchedAsins) {
                        foreach ($matchedAsins as $asin) {
                            $sb->orWhereJsonContains('ads_sb.related_asins', $asin);
                        }
                    });
                    $q->orWhereIn('ads.asin', $matchedAsins);
                }
            });
        }


        // 🌍 Country filter
        if ($filters->filled('country') && $filters->country !== 'all') {
            $query->where('amz_keyword_recommendations.country', $filters->country);
        }

        // 🏷️ Campaign type filter
        if ($filters->filled('campaign') && $filters->campaign !== 'all') {
            $query->where('amz_keyword_recommendations.campaign_types', $filters->campaign);
        }

        // 🎯 Targeting type filter
        if ($filters->filled('targeting_type') && $filters->targeting_type !== 'all') {
            $query->where('campaigns.targeting_type', $filters->targeting_type);
        }

        // ⚙️ Rule filter
        if ($filters->filled('rules')) {
            $labels = KeywordBidRecommendationRule::whereIn('id', $filters->rules)
                ->pluck('action_label')
                ->toArray();
            $query->where(function ($q) use ($labels) {
                foreach ($labels as $label) {
                    $q->orWhere('amz_keyword_recommendations.recommendation', 'LIKE', "%{$label}%");
                }
            });
        }

        // run_status filter
        if ($filters->filled('run_status') && $filters->run_status !== 'all') {
            $query->where('amz_keyword_recommendations.run_status', $filters->run_status);
        }
        if ($filters->filled('run_update') && $filters->run_update !== 'all') {
            $dateStart = $selectedDate;
            $dateEnd = Carbon::parse($selectedDate)->addDay()->toDateString();
        }
        if ($filters->filled('sort_acos')) {
            $acosMap = [
                'acos' => 'amz_keyword_recommendations.acos',
                'acos_14d' => 'amz_keyword_recommendations.acos_14d',
                'acos_30d' => 'amz_keyword_recommendations.acos_30d',
                'acos_7d' => 'amz_keyword_recommendations.acos_7d',
            ];
            $sortColumn = $filters->sort_acos;
            $direction = 'asc';
            if (strpos($sortColumn, ':') !== false) {
                [$sortColumn, $dir] = explode(':', $sortColumn);
                $direction = strtolower($dir) === 'desc' ? 'desc' : 'asc';
            } else if (strtolower($filters->sort_direction ?? '') === 'desc') {
                $direction = 'desc';
            }
            if (isset($acosMap[$sortColumn])) {
                $query->orderBy($acosMap[$sortColumn], $direction);
            }
        }
        // 🏷️ ASIN filter (move subqueries outside closure)
        if ($filters->filled('asins') && is_array($filters->asins) && count($filters->asins) > 0) {
            $asins = $filters->asins;

            $spCampaignIds = DB::table('amz_ads_products')
                ->whereIn('asin', $asins)
                ->pluck('campaign_id')
                ->toArray();

            $sbCampaignIds = DB::table('amz_ads_products_sb')
                ->where(function ($q) use ($asins) {
                    foreach ($asins as $asin) {
                        $q->orWhereJsonContains('related_asins', $asin);
                    }
                })
                ->pluck('campaign_id')
                ->toArray();

            $campaignIds = array_unique(array_merge($spCampaignIds, $sbCampaignIds));

            if (!empty($campaignIds)) {
                $query->whereIn('amz_keyword_recommendations.campaign_id', $campaignIds);
            }
        }

        // 🧩 Keyword State filter
        if ($filters->filled('keyword_state') && $filters->keyword_state !== 'all') {
            $query->where(function ($q) use ($filters) {
                if ($filters->keyword_state === 'na') {
                    $q->whereNull('sp_kw.state')
                        ->orWhere('sp_kw.state', '')
                        ->orWhereNull('sb_kw.state')
                        ->orWhere('sb_kw.state', '');
                } else {
                    // ✅ Normal filter for specific states
                    $q->where('sp_kw.state', $filters->keyword_state)
                        ->orWhere('sb_kw.state', $filters->keyword_state);
                }
            });
        }

        // ACOS Filter 
        $hasPeriod = $filters->filled('period');

        $query->when(
            $filters->filled('acos') && $filters->acos !== 'all',
            function ($q) use ($filters, $hasPeriod) {

                $acosFilter = $filters->acos;
                $period     = $filters->get('period', '7d'); // default 7d

                // Map period → keyword columns
                switch ($period) {
                    case '1d':
                        $acosColumn  = 'amz_keyword_recommendations.acos';
                        $spendColumn = 'amz_keyword_recommendations.total_spend';
                        break;

                    case '14d':
                        $acosColumn  = 'amz_keyword_recommendations.acos_14d';
                        $spendColumn = 'amz_keyword_recommendations.total_spend_14d';
                        break;

                    case '30d':
                        $acosColumn  = 'amz_keyword_recommendations.acos_30d';
                        $spendColumn = 'amz_keyword_recommendations.total_spend_30d';
                        break;

                    case '7d':
                    default:
                        $acosColumn  = 'amz_keyword_recommendations.acos_7d';
                        $spendColumn = 'amz_keyword_recommendations.total_spend_7d';
                        break;
                }

                $q->where(function ($sub) use ($acosFilter, $acosColumn, $spendColumn, $hasPeriod) {

                    /*
            |--------------------------------------------------------------------------
            | CASE 1: NO period filter → full legacy behavior
            |--------------------------------------------------------------------------
            */
                    if (! $hasPeriod) {

                        if ($acosFilter === '0') {
                            // ACOS = 0, spend > 0
                            $sub->where($acosColumn, 0)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '30') {
                            // ACOS <= 30, spend > 0
                            $sub->where($acosColumn, '<=', 30)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '31') {
                            // ACOS > 30, spend > 0
                            $sub->where($acosColumn, '>', 30)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '3045') {
                            // 30 < ACOS < 45, spend > 0
                            $sub->where($acosColumn, '>', 30)
                                ->where($acosColumn, '<', 45)
                                ->where($spendColumn, '>', 0);
                        } elseif ($acosFilter === '45') {
                            // ACOS >= 45, spend > 0
                            $sub->where($acosColumn, '>=', 45)
                                ->where($spendColumn, '>', 0);
                        }

                        return;
                    }

                    /*
            |--------------------------------------------------------------------------
            | CASE 2: period IS present → simplified buckets
            |--------------------------------------------------------------------------
            */
                    if ($acosFilter === '30') {
                        // ACOS <= 30, spend > 0
                        $sub->where($acosColumn, '<=', 30)
                            ->where($acosColumn, '>', 0)
                            ->where($spendColumn, '>', 0);
                    } elseif ($acosFilter === '31') {
                        // ACOS > 30, spend > 0
                        $sub->where($acosColumn, '>', 30)
                            ->where($spendColumn, '>', 0);
                    } elseif ($acosFilter === '0') {
                        // ACOS = 0, spend > 0
                        $sub->where($acosColumn, 0)
                            ->where($spendColumn, '>', 0);
                    } elseif ($acosFilter === 'none') {
                        // ACOS = 0, spend = 0
                        $sub->where($acosColumn, 0)
                            ->where($spendColumn, 0);
                    }
                });
            }
        );

        return $query;
    }

    /**
     * Returns campaigns summed for last 7 days (yesterday back 6 days).
     * NO UI filters applied.
     */
    public function getLast7DaysCampaignsQuery()
    {
        $marketTz = config('timezone.market');
        $endDate   = Carbon::now($marketTz)->startOfDay()->subDay();
        $startDate = (clone $endDate)->subDays(6);

        // 1) Subquery: aggregate only from campaign_recommendations
        $agg = DB::table('campaign_recommendations')
            ->select([
                'campaign_id',
                DB::raw('MAX(campaign_name) as campaign_name'),
                DB::raw('MAX(country) as country'),
                DB::raw('MAX(campaign_types) as campaign_types'),
                DB::raw('MAX(total_daily_budget) as total_daily_budget'),

                DB::raw('SUM(total_spend) as total_spend'),
                DB::raw('SUM(total_sales) as total_sales'),
                DB::raw('SUM(purchases7d) as purchases7d'),

                DB::raw('SUM(total_spend_7d) as total_spend_7d'),
                DB::raw('SUM(total_sales_7d) as total_sales_7d'),
                DB::raw('SUM(purchases7d_7d) as purchases7d_7d'),

                DB::raw('CASE 
            WHEN SUM(total_sales_7d) > 0
            THEN (SUM(total_spend_7d) / SUM(total_sales_7d)) * 100
            ELSE 0
        END as acos_7d'),

                DB::raw('SUM(total_spend_14d) as total_spend_14d'),
                DB::raw('SUM(total_sales_14d) as total_sales_14d'),
                DB::raw('SUM(purchases7d_14d) as purchases7d_14d'),

                DB::raw('MAX(report_week) as report_week'),
            ])
            ->whereBetween('report_week', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('deleted_at')
            ->groupBy('campaign_id');

        // 2) Main query: join other tables to the aggregated result
        $query = DB::query()->fromSub($agg, 'cr')
            ->leftJoin('amz_campaigns as sp', 'cr.campaign_id', '=', 'sp.campaign_id')
            ->leftJoin('amz_campaigns_sb as sb', 'cr.campaign_id', '=', 'sb.campaign_id')
            ->leftJoin('amz_campaigns_sd as sd', 'cr.campaign_id', '=', 'sd.campaign_id')
            ->leftJoin('amz_ads_products as product_sp', 'cr.campaign_id', '=', 'product_sp.campaign_id')
            ->leftJoin('amz_ads_products_sb as product_sb', 'cr.campaign_id', '=', 'product_sb.campaign_id')
            ->leftJoin('amz_ads_products_sd as product_sd', 'cr.campaign_id', '=', 'product_sd.campaign_id')
            ->select([
                'cr.*',
                DB::raw('MAX(sp.campaign_state) as sp_campaign_state'),
                DB::raw('MAX(sb.campaign_state) as sb_campaign_state'),
                DB::raw('MAX(sd.campaign_state) as sd_campaign_state'),
                DB::raw('MAX(product_sp.asin) as asin'),
                DB::raw('MAX(product_sb.related_asins) as related_asins'),
                DB::raw('MAX(product_sd.asin) as sd_asin'),
            ])
            ->groupBy('cr.campaign_id');
        return $query;
    }

    public function getLast7DaysKeywordsQuery()
    {
        $marketTz = config('timezone.market');

        $endDate   = Carbon::now($marketTz)->startOfDay()->subDay(); // yesterday
        $startDate = (clone $endDate)->subDays(6);                  // last 7 days

        /**
         * 1) Aggregate only from amz_keyword_recommendations
         *    (so joins won't duplicate sums)
         */
        $agg = DB::table('amz_keyword_recommendations')
            ->select([
                'keyword_id',
                'campaign_id',

                DB::raw('MAX(keyword) as keyword'),
                DB::raw('MAX(country) as country'),
                DB::raw('MAX(campaign_types) as campaign_types'),

                // ---------- 1d totals across 7 days ----------
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(total_spend) as total_spend'),
                DB::raw('SUM(total_sales) as total_sales'),
                DB::raw('SUM(purchases1d) as purchases1d'),

                // weighted CPC = spend / clicks
                DB::raw('CASE WHEN SUM(clicks) > 0 THEN SUM(total_spend)/SUM(clicks) ELSE 0 END as cpc'),

                // weighted CTR = clicks / impressions
                DB::raw('CASE WHEN SUM(impressions) > 0 THEN SUM(clicks)/SUM(impressions) ELSE 0 END as ctr'),

                // weighted CVR = purchases / clicks  (adjust if your CVR definition differs)
                DB::raw('CASE WHEN SUM(clicks) > 0 THEN SUM(purchases1d)/SUM(clicks) ELSE 0 END as conversion_rate'),

                // weighted ACoS = spend / sales
                DB::raw('CASE WHEN SUM(total_sales) > 0 THEN (SUM(total_spend)/SUM(total_sales))*100 ELSE 0 END as acos'),

                DB::raw('MAX(bid) as bid'), // or AVG(bid) if you prefer

                // ---------- 7d metrics ----------
                DB::raw('SUM(clicks_7d) as clicks_7d'),
                DB::raw('SUM(impressions_7d) as impressions_7d'),
                DB::raw('SUM(total_spend_7d) as total_spend_7d'),
                DB::raw('SUM(total_sales_7d) as total_sales_7d'),
                DB::raw('SUM(purchases1d_7d) as purchases1d_7d'),

                DB::raw('CASE WHEN SUM(clicks_7d) > 0 THEN SUM(total_spend_7d)/SUM(clicks_7d) ELSE 0 END as cpc_7d'),
                DB::raw('CASE WHEN SUM(impressions_7d) > 0 THEN SUM(clicks_7d)/SUM(impressions_7d) ELSE 0 END as ctr_7d'),
                DB::raw('CASE WHEN SUM(clicks_7d) > 0 THEN SUM(purchases1d_7d)/SUM(clicks_7d) ELSE 0 END as conversion_rate_7d'),
                DB::raw('CASE WHEN SUM(total_sales_7d) > 0 THEN (SUM(total_spend_7d)/SUM(total_sales_7d))*100 ELSE 0 END as acos_7d'),

                // ---------- 14d metrics ----------
                DB::raw('SUM(total_spend_14d) as total_spend_14d'),
                DB::raw('SUM(total_sales_14d) as total_sales_14d'),
                DB::raw('SUM(purchases1d_14d) as purchases1d_14d'),
                DB::raw('CASE WHEN SUM(total_sales_14d) > 0 THEN (SUM(total_spend_14d)/SUM(total_sales_14d))*100 ELSE 0 END as acos_14d'),

                // recommendations / ai (keep same columns)
                DB::raw('MAX(recommendation) as recommendation'),
                DB::raw('MAX(suggested_bid) as suggested_bid'),
                DB::raw('MAX(ai_suggested_bid) as ai_suggested_bid'),
                DB::raw('MAX(ai_recommendation) as ai_recommendation'),

                // date label for export
                DB::raw("'" . $startDate->toDateString() . " to " . $endDate->toDateString() . "' as date_range"),
            ])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('deleted_at')
            ->groupBy('keyword_id', 'campaign_id');

        /**
         * 2) Join meta tables AFTER aggregation
         */
        return DB::query()->fromSub($agg, 'kr')
            ->leftJoin('amz_campaigns as campaigns', 'kr.campaign_id', '=', 'campaigns.campaign_id')
            ->leftJoin('amz_campaigns_sb as campaigns_sb', 'kr.campaign_id', '=', 'campaigns_sb.campaign_id')
            ->leftJoin('amz_ads_products as ads', 'kr.campaign_id', '=', 'ads.campaign_id')
            ->leftJoin('amz_ads_products_sb as ads_sb', 'kr.campaign_id', '=', 'ads_sb.campaign_id')
            ->leftJoin('amz_ads_keywords as sp_kw', 'kr.keyword_id', '=', 'sp_kw.keyword_id')
            ->leftJoin('amz_ads_keyword_sb as sb_kw', 'kr.keyword_id', '=', 'sb_kw.keyword_id')
            ->select([
                'kr.*',
                DB::raw('MAX(campaigns.campaign_name) as c_name'),
                DB::raw('MAX(campaigns_sb.campaign_name) as sb_c_name'),
                DB::raw('MAX(ads.asin) as asin'),
                DB::raw('MAX(ads_sb.related_asins) as related_asin'),
                DB::raw('MAX(sp_kw.state) as sp_state'),
                DB::raw('MAX(sb_kw.state) as sb_state'),
            ])
            ->groupBy('kr.keyword_id', 'kr.campaign_id');
    }

    private function mergeCampaignAsins($campaigns)
    {
        return $campaigns->groupBy('campaign_id')->map(function ($group) {
            $first = $group->first();

            $allAsins = $group->pluck('asin')->filter()->unique()->values()->all();

            $related = $group->pluck('related_asins')->filter()->map(function ($item) {
                if (is_array($item)) {
                    return array_map(function ($v) {
                        return json_decode($v, true) ?: $v; // decode if possible
                    }, $item);
                } elseif (is_string($item)) {
                    return json_decode($item, true) ?: [$item]; // decode JSON string
                }
                return [];
            })->flatten()->unique()->values()->all();

            $first->related_asins = array_values(array_unique(array_merge($allAsins, $related)));

            return $first;
        })->values();
    }
}
