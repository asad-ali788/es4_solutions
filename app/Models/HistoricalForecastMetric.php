<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricalForecastMetric extends Model
{
    //
    use SoftDeletes;

    protected $table = 'historical_forecast_metrics';

    protected $fillable = [
        'product_id',
        'product_sku',
        'asin1',
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
