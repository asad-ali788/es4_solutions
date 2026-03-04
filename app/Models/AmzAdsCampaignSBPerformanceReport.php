<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignSBPerformanceReport extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_campaign_performance_reports_sb';

    protected $fillable = [
        'campaign_id',
        'impressions',
        'clicks',
        'unitsSold',
        'purchases',
        'cost',
        'c_budget',
        'budget_gap',
        'c_currency',
        'c_status',
        'sales',
        'country',
        'date',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(AmzCampaignsSb::class, 'campaign_id', 'campaign_id');
    }
}
