<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsTargetsPerformanceReportSb extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_ads_targets_performance_report_sb';

    protected $fillable = [
        'targeting_id',
        'targeting_text',
        'targeting_expression',
        'campaign_id',
        'ad_group_id',
        'clicks',
        'impressions',
        'cost',
        'sales',
        'purchases',
        'units_sold',
        'c_date',
        'country',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'c_date',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
