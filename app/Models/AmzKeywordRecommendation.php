<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzKeywordRecommendation extends Model
{
    use SoftDeletes;

    protected $table = 'amz_keyword_recommendations';

    protected $fillable = [
        'keyword_id',
        'campaign_id',
        'target_id',
        'keyword',
        'date',
        'clicks',
        'cpc',
        'ctr',
        'bid',
        'total_spend',
        'total_sales',
        'country',
        'conversion_rate',
        'clicks_7d',
        'cpc_7d',
        'ctr_7d',
        'conversion_rate_7d',
        'acos',
        'campaign_types',
        'impressions',
        'purchases1d',
        'total_spend_7d',
        'total_sales_7d',
        'purchases1d_7d',
        'acos_7d',
        'total_spend_14d',
        'total_sales_14d',
        'purchases1d_14d',
        'recommendation',
        'suggested_bid',
        'ai_suggested_bid',
        'ai_recommendation',
        'ai_status',
        's_bid_min',
        's_bid_range',
        's_bid_max',
        'manual_bid',
        'old_bid',
        'run_update',
        'run_status',
        'acos_14d',
        'total_spend_30d',
        'total_sales_30d',
        'purchases7d_30d',
        'acos_30d',
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
