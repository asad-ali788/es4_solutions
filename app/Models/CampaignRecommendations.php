<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignRecommendations extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_recommendations';

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'report_week',
        'campaign_types',
        'enabled_campaigns_count',
        'country',
        'total_daily_budget',
        'total_spend',
        'total_sales',
        'purchases7d',
        'acos',
        'total_spend_7d',
        'total_sales_7d',
        'purchases7d_7d',
        'acos_7d',
        'total_spend_14d',
        'total_sales_14d',
        'purchases7d_14d',
        'acos_14d',
        'total_spend_30d',
        'total_sales_30d',
        'purchases7d_30d',
        'acos_30d',
        'suggested_budget',
        'manual_budget',
        'old_budget',
        'run_update',
        'run_status',
        'ai_recommendation',
        'ai_status',
        'ai_suggested_budget',
        'recommendation',
        'rule_applied',
        'from_group',
        'to_group',
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
