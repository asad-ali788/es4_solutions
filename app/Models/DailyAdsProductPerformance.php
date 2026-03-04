<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyAdsProductPerformance extends Model
{
    //
    use SoftDeletes;

    protected $table = 'daily_ads_product_performances';

    protected $fillable = [
        'sku',
        'asin',
        'sale_date',
        'sold',
        'revenue',
        'ad_spend',
        'ad_sales',
        'acos',
        'tacos',
        'ad_units',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'sale_date',
        // Add your date columns here
    ];

    protected $casts = [
        'sold'       => 'integer',
        'revenue'    => 'decimal:2',
        'ad_spend'   => 'decimal:2',
        'ad_sales'   => 'decimal:2',
        'acos'       => 'decimal:2',
        'tacos'      => 'decimal:2',
        'ad_units'   => 'integer',
    ];
}
