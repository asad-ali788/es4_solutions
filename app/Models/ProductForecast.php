<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductForecast extends Model
{
    //
    use SoftDeletes;

    protected $table = 'product_forecasts';

    protected $fillable = [
        'product_id',
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
