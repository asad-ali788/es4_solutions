<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KeywordRankReport360Bi;
use App\Models\TopSearchBi;
use App\Models\BrandAnalytics2024Bi;
use App\Models\CompetitorRank360Bi;
use App\Models\BrandAnalyticsWeeklyDataBi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandAnalyticsController extends Controller
{
    /**
     * Display the main Brand Analytics (Joined) view.
     */
    public function index(Request $request)
    {
        $brand = $request->filled('brand') ? $request->brand : 'EcoNour';
        $keywordFilter = $request->get('keyword');
        $asinFilter = $request->get('asin');
        $sort = $request->get('sort', 'rank_value');
        $direction = $request->get('direction', 'ASC');

        // Optimized Join Logic for Brand Analytics
        $latestTSDate = DB::table('top_search_bis')->max('reporting_date');

        $query1 = DB::table('top_search_bis')
            ->select('search_term', 'top_clicked_product_1_asin as target_asin', DB::raw('1 as target_slot'), 'top_clicked_category_1 as target_category',
                     'top_clicked_brand_1', 'top_clicked_brand_2', 'top_clicked_brand_3',
                     'top_clicked_product_1_asin', 'top_clicked_product_2_asin', 'top_clicked_product_3_asin')
            ->where('top_clicked_brand_1', $brand)
            ->where('reporting_date', $latestTSDate);

        $query2 = DB::table('top_search_bis')
            ->select('search_term', 'top_clicked_product_2_asin as target_asin', DB::raw('2 as target_slot'), 'top_clicked_category_2 as target_category',
                     'top_clicked_brand_1', 'top_clicked_brand_2', 'top_clicked_brand_3',
                     'top_clicked_product_1_asin', 'top_clicked_product_2_asin', 'top_clicked_product_3_asin')
            ->where('top_clicked_brand_2', $brand)
            ->where('reporting_date', $latestTSDate);

        $query3 = DB::table('top_search_bis')
            ->select('search_term', 'top_clicked_product_3_asin as target_asin', DB::raw('3 as target_slot'), 'top_clicked_category_3 as target_category',
                     'top_clicked_brand_1', 'top_clicked_brand_2', 'top_clicked_brand_3',
                     'top_clicked_product_1_asin', 'top_clicked_product_2_asin', 'top_clicked_product_3_asin')
            ->where('top_clicked_brand_3', $brand)
            ->where('reporting_date', $latestTSDate);

        $subQuery = $query1->union($query2)->union($query3);

        $query = KeywordRankReport360Bi::query()
            ->joinSub($subQuery, 'top_data', function ($join) {
                $join->on('keyword_rank_report_360_bi.asin', '=', 'top_data.target_asin');
                $join->on('keyword_rank_report_360_bi.keyword', '=', 'top_data.search_term');
            })
            ->select([
                'keyword', 'match_type', 'search_volume', 'report_date', 'rank_value', 'asin',
                'top_data.target_slot', 'top_data.target_category',
                'top_data.top_clicked_brand_1', 'top_data.top_clicked_brand_2', 'top_data.top_clicked_brand_3',
                'top_data.top_clicked_product_1_asin', 'top_data.top_clicked_product_2_asin', 'top_data.top_clicked_product_3_asin'
            ]);

        if ($keywordFilter) $query->where('keyword', 'LIKE', "%{$keywordFilter}%");
        if ($asinFilter) $query->where('asin', 'LIKE', "%{$asinFilter}%");

        $latestReportDate = KeywordRankReport360Bi::whereNotNull('report_date')->latest('report_date')->value('report_date');
        if ($latestReportDate) $query->where('report_date', $latestReportDate);

        $results = $query->orderBy($sort, $direction)->paginate(50)->withQueryString();

        return view('pages.admin.amzAds.brandAnalytics.index', [
            'results' => $results,
            'brand' => $brand,
            'type' => 'brand_analytics'
        ]);
    }

    /**
     * Display Competitor Ranking view.
     */
    public function competitorRank(Request $request)
    {
        $brand = $request->get('brand', 'EcoNour');
        $keywordFilter = $request->get('keyword');
        $asinFilter = $request->get('asin');
        $sort = $request->get('sort', 'rank_value');
        $direction = $request->get('direction', 'ASC');

        $query = CompetitorRank360Bi::query();
        if ($keywordFilter) $query->where('keyword', 'LIKE', "%{$keywordFilter}%");
        if ($asinFilter) $query->where('asin', 'LIKE', "%{$asinFilter}%");
        
        $results = $query->orderBy($sort, $direction)->paginate(50)->withQueryString();

        return view('pages.admin.amzAds.brandAnalytics.index', [
            'results' => $results,
            'brand' => $brand,
            'type' => 'competitor_rank'
        ]);
    }

    /**
     * Display Brand Analytics 2024 view.
     */
    public function analytics2024(Request $request)
    {
        $brand = $request->get('brand', 'EcoNour');
        $keywordFilter = $request->get('keyword');
        $asinFilter = $request->get('asin');
        $sort = $request->get('sort', 'reporting_date');
        $direction = $request->get('direction', 'DESC');

        $query = BrandAnalytics2024Bi::query();
        if ($keywordFilter) $query->where('search_query', 'LIKE', "%{$keywordFilter}%");
        if ($asinFilter) $query->where('asin', 'LIKE', "%{$asinFilter}%");
        
        $results = $query->orderBy($sort, $direction)->paginate(50)->withQueryString();

        return view('pages.admin.amzAds.brandAnalytics.index', [
            'results' => $results,
            'brand' => $brand,
            'type' => 'brand_analytics_2024'
        ]);
    }

    /**
     * Display Brand Analytics Weekly view.
     */
    public function weeklyAnalytics(Request $request)
    {
        $brand = $request->get('brand', 'EcoNour');
        $keywordFilter = $request->get('keyword');
        $asinFilter = $request->get('asin');
        $sort = $request->get('sort', 'impressions');
        $direction = $request->get('direction', 'DESC');

        // Logic to find "Week -12" as requested or absolute latest
        $latestYear = BrandAnalyticsWeeklyDataBi::max('week_year');
        $latestWeek = "Week -12"; // User specifically mentioned Week -12

        // If Week -12 of the latest year doesn't exist, try to find the absolute latest
        $exists = BrandAnalyticsWeeklyDataBi::where('week_year', $latestYear)->where('week_number', $latestWeek)->exists();
        if (!$exists) {
            $latestWeek = BrandAnalyticsWeeklyDataBi::where('week_year', $latestYear)
                ->orderByRaw('CAST(REPLACE(REPLACE(week_number, "Week -", ""), "Week-", "") AS UNSIGNED) DESC')
                ->value('week_number');
        }

        $query = BrandAnalyticsWeeklyDataBi::query();
        if ($latestYear) $query->where('week_year', $latestYear);
        if ($latestWeek) $query->where('week_number', $latestWeek);

        if ($asinFilter) $query->where('asin', 'LIKE', "%{$asinFilter}%");
        // No keyword column in this table based on research

        $results = $query->orderBy($sort, $direction)->paginate(50)->withQueryString();

        return view('pages.admin.amzAds.brandAnalytics.index', [
            'results' => $results,
            'brand' => $brand,
            'type' => 'brand_analytics_weekly'
        ]);
    }
}
