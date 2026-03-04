<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricalForecastMetricAsins extends Model
{
    //
    use SoftDeletes;

    protected $table = 'historical_forecast_metric_asins';

    protected $fillable = [
        'product_asin',
        'metrics',
        'metrics_key',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'metrics' => 'array',
    ];
}
