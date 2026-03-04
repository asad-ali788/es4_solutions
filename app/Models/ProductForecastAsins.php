<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductForecastAsins extends Model
{
    //
    use SoftDeletes;

    protected $table = 'product_forecast_asins';

    protected $fillable = [
        'product_asin',
        'forecast_month',
        'forecast_half',
        'forecast_units',
        'actual_units_sold',
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
