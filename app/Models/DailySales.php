<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailySales extends Model
{
    use SoftDeletes;

    protected $table = 'daily_sales';

    protected $fillable = [
        'sku',
        'asin',
        'product_listings_id',
        'marketplace_id',
        'sale_date',
        'sale_datetime',
        'total_units',
        'total_revenue',
        'total_cost',
        'currency',
        'total_profit',
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
