<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempAmzCampaignSDPerformanceReport extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_amz_campaign_sd_performance_report';

    protected $fillable = [
        'campaign_id',
        'campaign_status',
        'campaign_budget_amount',
        'campaign_budget_currency_code',
        'impressions',
        'clicks',
        'cost',
        'sales',
        'purchases',
        'units_sold',
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
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
