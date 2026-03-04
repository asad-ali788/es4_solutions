<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeywordBidRecommendationRule extends Model
{
    use SoftDeletes;

    protected $table = 'keyword_bid_recommendation_rules';

    protected $fillable = [
        'ctr_condition',
        'conversion_condition',
        'acos_condition',
        'click_condition',
        'sales_condition',
        'impressions_condition',
        'bid_adjustment',
        'action_label',
        'priority',
        'is_active',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function getConditionTextAttribute(): string
    {
        $parts = [];

        if ($this->priority == 1) {
            if (!is_null($this->ctr_condition)) $parts[] = "CTR > {$this->ctr_condition}%";
            if (!is_null($this->conversion_condition)) $parts[] = "Conversion Rate > {$this->conversion_condition}%";
            if (!is_null($this->acos_condition)) $parts[] = "ACoS < {$this->acos_condition}";
        } elseif ($this->priority == 2) {
            if (!is_null($this->ctr_condition)) $parts[] = "CTR > {$this->ctr_condition}%";
            if (!is_null($this->conversion_condition)) $parts[] = "Conversion Rate < {$this->conversion_condition}%";
            if (!is_null($this->acos_condition)) $parts[] = "ACoS > {$this->acos_condition}";
        } elseif ($this->priority == 3) {
            if (!is_null($this->ctr_condition)) $parts[] = "CTR < {$this->ctr_condition}%";
            if (!is_null($this->sales_condition)) $parts[] = "Sales = {$this->sales_condition}";
        } elseif ($this->priority == 4) {
            $parts[] = "CTR between 0.3%–1%";
            $parts[] = "Conversion Rate between 5%–15%";
            if (!is_null($this->acos_condition)) $parts[] = "ACoS > {$this->acos_condition}";
        } elseif ($this->priority == 5) {
            if (!is_null($this->impressions_condition)) $parts[] = "Impressions > {$this->impressions_condition}";
            if (!is_null($this->ctr_condition)) $parts[] = "CTR < {$this->ctr_condition}%";
        } elseif ($this->priority == 6) {
            if (!is_null($this->ctr_condition)) $parts[] = "CTR > {$this->ctr_condition}%";
            $parts[] = "Sales > 0";
            if (!is_null($this->acos_condition)) $parts[] = "ACoS > {$this->acos_condition}";
        } elseif ($this->priority == 7) {
            if (!is_null($this->click_condition)) $parts[] = "Clicks > {$this->click_condition}";
            if (!is_null($this->sales_condition)) $parts[] = "Sales = {$this->sales_condition}";
        }

        return implode(' AND ', $parts);
    }
}
