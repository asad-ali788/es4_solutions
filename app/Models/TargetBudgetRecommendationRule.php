<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetBudgetRecommendationRule extends Model
{
    use SoftDeletes;

    // Table name
    protected $table = 'target_budget_recommendation_rule';

    // Fillable columns
    protected $fillable = [
        'min_ctr',
        'max_ctr',
        'min_conversion_rate',
        'max_conversion_rate',
        'min_acos',
        'max_acos',
        'min_clicks',
        'min_sales',
        'min_impressions',
        'action_label',      // Recommendation text
        'adjustment_type',   // increase, decrease, keep
        'adjustment_value',  // percentage if applicable
        'is_active',
        'priority',
    ];

    // Dates
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Casts
    protected $casts = [
        'options' => 'array',
    ];

    public function getConditionTextAttribute(): string
    {
        $parts = [];

        if (!is_null($this->min_ctr)) $parts[] = "CTR ≥ " . number_format($this->min_ctr, 2) . "%";
        if (!is_null($this->max_ctr)) $parts[] = "CTR ≤ " . number_format($this->max_ctr, 2) . "%";
        if (!is_null($this->min_conversion_rate)) $parts[] = "Conv ≥ " . number_format($this->min_conversion_rate, 2) . "%";
        if (!is_null($this->max_conversion_rate)) $parts[] = "Conv ≤ " . number_format($this->max_conversion_rate, 2) . "%";
        if (!is_null($this->min_acos)) $parts[] = "ACoS ≥ " . number_format($this->min_acos, 2) . "%";
        if (!is_null($this->max_acos)) $parts[] = "ACoS ≤ " . number_format($this->max_acos, 2) . "%";
        if (!is_null($this->min_clicks)) $parts[] = "Clicks ≥ " . number_format($this->min_clicks);
        if (!is_null($this->min_sales)) $parts[] = "Sales ≥ " . number_format($this->min_sales);
        if (!is_null($this->min_impressions)) $parts[] = "Impressions ≥ " . number_format($this->min_impressions);

        return implode(' + ', $parts);
    }
}
