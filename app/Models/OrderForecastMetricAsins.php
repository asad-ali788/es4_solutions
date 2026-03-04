<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderForecastMetricAsins extends Model
{
    //
    use SoftDeletes;

    protected $table = 'order_forecast_metric_asins';

    protected $fillable = [
        'product_asin',
        'metrics_by_month',
        'is_not_ready',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'metrics_by_month' => 'array', // JSON auto-cast
        'is_not_ready' => 'boolean',
    ];
}
