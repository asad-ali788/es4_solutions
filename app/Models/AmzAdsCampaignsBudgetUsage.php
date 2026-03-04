<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignsBudgetUsage extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_campaigns_budget_usages';

    protected $fillable = [
        'campaign_id',
        'campaign_type',
        'budget',
        'budget_usage_percent',
        'usage_updated_at',
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
