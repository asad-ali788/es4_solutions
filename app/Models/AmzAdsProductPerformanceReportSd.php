<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsProductPerformanceReportSd extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_ads_product_performance_report_sd';

    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'ad_id',
        'sku',
        'asin',
        'clicks',
        'impressions',
        'cost',
        'sales',
        'purchases',
        'units_sold',
        'date',
        'country',
        'added',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'added',
        'date',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
