<?php

namespace App\Traits;

use App\Models\SpSearchTermSummaryReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait SearchTermsTrait
{
    protected function getSearchTermsQuery(Request $request)
    {
        $productsSub = DB::table('amz_ads_products')
            ->select(
                'campaign_id',
                DB::raw('MIN(asin) as asin')
            )
            ->groupBy('campaign_id');

        $query = SpSearchTermSummaryReport::query()
            ->leftJoinSub($productsSub, 'p', function ($join) {
                $join->on(
                    'sp_search_term_summary_reports.campaign_id',
                    '=',
                    'p.campaign_id'
                );
            })
            // Join campaigns to get campaign_name
            ->leftJoin('amz_campaigns as c', function ($join) {
                $join->on(
                    'sp_search_term_summary_reports.campaign_id',
                    '=',
                    'c.campaign_id'
                )
                    ->whereNull('c.deleted_at'); // if using soft deletes
            })

            ->leftJoin('product_categorisations as pc', function ($join) {
                $join->on('pc.child_asin', '=', 'p.asin')
                    ->whereNull('pc.deleted_at');
                // ->where('pc.marketplace', 'US'); // optional
            });

        $marketTz     = config('timezone.market');
        $selectedDate = $request->date ?? Carbon::now($marketTz)->subDay()->toDateString();

        // ---------------- Filters ----------------
        if ($request->country && $request->country !== 'all') {
            $query->where('sp_search_term_summary_reports.country', $request->country);
        }

        if ($request->search) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('sp_search_term_summary_reports.keyword', 'LIKE', "%{$search}%")
                    ->orWhere('sp_search_term_summary_reports.search_term', 'LIKE', "%{$search}%")
                    ->orWhere('sp_search_term_summary_reports.campaign_id', 'LIKE', "%{$search}%")
                    ->orWhere('sp_search_term_summary_reports.keyword_type', 'LIKE', "%{$search}%")
                    ->orWhere('sp_search_term_summary_reports.keyword_id', 'LIKE', "%{$search}%")
                    ->orWhere('p.asin', 'LIKE', "%{$search}%")
                    ->orWhere('c.campaign_name', 'LIKE', "%{$search}%")
                    ->orWhere('pc.child_short_name', 'LIKE', "%{$search}%");
            });
        }

        // ---------------- Keyword Match / Not Match ----------------
        if ($request->keyword_match_type === 'matching') {
            $query->whereColumn(
                'sp_search_term_summary_reports.search_term',
                '=',
                'sp_search_term_summary_reports.keyword'
            );
        }

        if ($request->keyword_match_type === 'not_matching') {
            $query->whereColumn(
                'sp_search_term_summary_reports.search_term',
                '!=',
                'sp_search_term_summary_reports.keyword'
            );
        }

        // ---------------- Positive / Negative ----------------
        if ($request->search_term_type === 'positive') {
            $query->where('sp_search_term_summary_reports.sales_1d', '>', 0);
        }

        if ($request->search_term_type === 'negative') {
            $query->where('sp_search_term_summary_reports.sales_1d', 0);
        }

        // ---------------- ASIN Filter (Single / Multiple) ----------------
        if ($request->filled('asins')) {
            $asins = array_filter((array) $request->asins);

            if (!empty($asins)) {
                $query->whereExists(function ($q) use ($asins) {
                    $q->select(DB::raw(1))
                        ->from('amz_ads_products as ap')
                        ->whereColumn(
                            'ap.campaign_id',
                            'sp_search_term_summary_reports.campaign_id'
                        )
                        ->whereIn('ap.asin', $asins);
                });
            }
        }

        // ---------------- Date / Aggregation ----------------
        $days = $request->days ? (int) $request->days : null;

        // ---------- 7 / 14 Days Aggregation ----------
        if ($days && in_array($days, [7, 14])) {

            $startDate = Carbon::parse($selectedDate)
                ->subDays($days - 1)
                ->toDateString();

            return $query
                ->whereBetween('sp_search_term_summary_reports.date', [$startDate, $selectedDate])
                ->selectRaw("
                sp_search_term_summary_reports.campaign_id,
                c.campaign_name,
                sp_search_term_summary_reports.keyword_id,
                sp_search_term_summary_reports.country,
                p.asin,
                pc.child_short_name as product_name,
                sp_search_term_summary_reports.keyword,
                sp_search_term_summary_reports.keyword_type,
                AVG(sp_search_term_summary_reports.keyword_bid) AS keyword_bid,
                SUM(sp_search_term_summary_reports.impressions) AS impressions,
                SUM(sp_search_term_summary_reports.clicks) AS clicks,
                AVG(sp_search_term_summary_reports.cost_per_click) AS cost_per_click,
                SUM(sp_search_term_summary_reports.cost) AS cost,
                SUM(sp_search_term_summary_reports.purchases_7d) AS purchases_7d,
                SUM(sp_search_term_summary_reports.sales_7d) AS sales_7d,
                GROUP_CONCAT(
                    sp_search_term_summary_reports.search_term
                    ORDER BY sp_search_term_summary_reports.impressions DESC
                    SEPARATOR ' | '
                ) AS search_term,
                MAX(sp_search_term_summary_reports.date) AS last_seen
            ")
                ->groupBy(
                    'sp_search_term_summary_reports.campaign_id',
                    'c.campaign_name',
                    'sp_search_term_summary_reports.keyword_id',
                    'sp_search_term_summary_reports.country',
                    'p.asin',
                    'pc.child_short_name',
                    'sp_search_term_summary_reports.keyword',
                    'sp_search_term_summary_reports.keyword_type'
                )
                ->orderByDesc('last_seen');
        }

        // ---------- Daily ----------
        return $query
            ->whereDate('sp_search_term_summary_reports.date', $selectedDate)
            ->selectRaw("
            sp_search_term_summary_reports.id,
            sp_search_term_summary_reports.country,
            DATE_FORMAT(sp_search_term_summary_reports.date, '%d-%m-%Y') AS formatted_date,
            sp_search_term_summary_reports.campaign_id,
            c.campaign_name,
            pc.child_short_name as product_name,
            sp_search_term_summary_reports.ad_group_id,
            sp_search_term_summary_reports.keyword_id,
            TRIM(BOTH '\"' FROM REPLACE(sp_search_term_summary_reports.keyword, 'asin=', '')) AS keyword,
            sp_search_term_summary_reports.search_term,
            sp_search_term_summary_reports.impressions,
            sp_search_term_summary_reports.clicks,
            sp_search_term_summary_reports.cost_per_click,
            sp_search_term_summary_reports.cost,
            sp_search_term_summary_reports.purchases_1d,
            sp_search_term_summary_reports.purchases_7d,
            sp_search_term_summary_reports.purchases_14d,
            sp_search_term_summary_reports.sales_1d,
            sp_search_term_summary_reports.sales_7d,
            sp_search_term_summary_reports.sales_14d,
            sp_search_term_summary_reports.campaign_budget_amount,
            sp_search_term_summary_reports.keyword_bid,
            sp_search_term_summary_reports.keyword_type,
            sp_search_term_summary_reports.match_type,
            sp_search_term_summary_reports.targeting,
            sp_search_term_summary_reports.ad_keyword_status,
            sp_search_term_summary_reports.start_date,
            sp_search_term_summary_reports.end_date,
            p.asin
        ");
    }
}
