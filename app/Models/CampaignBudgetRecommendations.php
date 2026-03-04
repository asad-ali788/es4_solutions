<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignBudgetRecommendations extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_budget_recommendations';

    protected $fillable = [
        'campaign_id',
        'campaign_type',
        'rule_id',
        'rule_name',
        'suggested_budget',
        'suggested_budget_increase_percent',
        'seven_days_start_date',
        'seven_days_end_date',
        'estimated_missed_sales_lower',
        'percent_time_in_budget',
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
