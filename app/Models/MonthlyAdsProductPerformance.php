<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyAdsProductPerformance extends Model
{
    use SoftDeletes;

    // Table name
    protected $table = 'monthly_ads_product_performances';

    // Mass assignable columns
    protected $fillable = [
        'sku',
        'asin',
        'month',
        'sold',
        'revenue',
        'ad_spend',
        'ad_sales',
        'acos',
        'tacos',
        'ad_units',
    ];

    // Dates
    protected $dates = [
        'month',       // Monthly performance date
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Type casting
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
