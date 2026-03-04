<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsKeywordPerformanceReport extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_keyword_performance_report';

    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'keyword_id',
        'cost',
        'sales1d',
        'sales7d',
        'sales30d',
        'purchases1d',
        'purchases7d',
        'purchases30d',
        'clicks',
        'impressions',
        'keyword_bid',
        'targeting',
        'keyword_text',
        'match_type',
        'c_date',
        'country',
        'added',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'c_date',
        'added',
    ];
}
