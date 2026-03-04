<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsinRecommendation extends Model
{
    //
    use SoftDeletes;

    protected $table = 'asin_recommendations';

    protected $fillable = [
        'asin',
        'report_week',
        'country',
        'active_campaigns',
        'enabled_campaigns_count',
        'total_daily_budget',
        'total_spend',
        'total_sales',
        'acos',
        'campaign_types',
        'recommendation',
        'rule_applied',
    ];


    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
