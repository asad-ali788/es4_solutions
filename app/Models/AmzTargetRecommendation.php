<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzTargetRecommendation extends Model
{
    use SoftDeletes;

    protected $table = 'amz_target_recommendations';

    protected $fillable = [
        'targeting_id',
        'targeting_text',
        'country',
        'campaign_id',
        'ad_group_id',
        'date',
        'clicks',
        'cpc',
        'ctr',
        'orders',
        'total_spend',
        'total_sales',
        'conversion_rate',
        'acos',
        'total_spend_7d',
        'total_sales_7d',
        'acos_7d',
        'total_spend_14d',
        'total_sales_14d',
        'purchases1d',
        'purchases1d_7d',
        'purchases1d_14d',
        'campaign_types', // SD or SB
        'recommendation',
        'recommendation',
        'impressions',
        'suggested_bid',
        'ai_suggested_bid',
        's_bid_min',
        's_bid_range',
        's_bid_max',
        'ai_recommendation',
        'ai_status',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'date',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
