<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

class SpSearchTermSummaryReport extends Model
{
    protected $table = 'sp_search_term_summary_reports';

    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'keyword_id',
        'country',
        'date',
        'keyword',
        'search_term',
        'product_name',
        'asin',
        'keyword_name',
        'campaign_name',
        'impressions',
        'clicks',
        'cost_per_click',
        'cost',
        'purchases_1d',
        'purchases_7d',
        'purchases_14d',
        'sales_1d',
        'sales_7d',
        'sales_14d',
        'campaign_budget_amount',
        'keyword_bid',
        'keyword_type',
        'match_type',
        'targeting',
        'ad_keyword_status',
        'start_date',
        'end_date',
    ];
}
