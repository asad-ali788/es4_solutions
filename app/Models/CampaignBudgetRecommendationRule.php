<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignBudgetRecommendationRule extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_budget_recommendation_rules';

    protected $fillable = [
        'min_acos',
        'max_acos',
        'spend_condition',
        'action_label',
        'adjustment_type',
        'adjustment_value',
        'is_active',
        'priority',
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
