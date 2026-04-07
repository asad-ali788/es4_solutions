<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpSearchTermSummaryReport extends Model
{

    protected $table = 'sp_search_term_summary_reports';

    protected $fillable = [
        'country',
        'date',
        'campaign_id',
        'ad_group_id',
        'keyword_id',
        'keyword',
        'search_term',
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

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
