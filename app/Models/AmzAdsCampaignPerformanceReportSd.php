<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignPerformanceReportSd extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_ads_campaign_performance_report_sd';

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
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
