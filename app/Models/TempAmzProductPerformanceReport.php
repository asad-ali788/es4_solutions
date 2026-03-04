<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempAmzProductPerformanceReport extends Model
{
    use SoftDeletes;

    protected $table = 'temp_amz_ads_product_performance_report';

    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'ad_id',
        'cost',
        'sales1d',
        'sales7d',
        'sales30d',
        'purchases1d',
        'purchases7d',
        'purchases30d',
        'clicks',
        'impressions',
        'sku',
        'asin',
        'c_date',
        'country',
        'added',
    ];

    protected $dates = [
        'c_date',
        'added',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'cost'           => 'float',
        'sales1d'        => 'float',
        'sales7d'        => 'float',
        'sales30d'       => 'float',
        'purchases1d'    => 'integer',
        'purchases7d'    => 'integer',
        'purchases30d'   => 'integer',
        'clicks'         => 'integer',
        'impressions'    => 'integer',
    ];
}
