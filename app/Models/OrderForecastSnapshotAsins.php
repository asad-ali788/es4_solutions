<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderForecastSnapshotAsins extends Model
{
    //
    use SoftDeletes;

    protected $table = 'order_forecast_snapshot_asins';

    protected $fillable = [
        'order_forecast_id',
        'product_asin',
        'product_title',
        'product_price',
        'country',
        'amazon_stock',
        'warehouse_stock',
        'routes',
        'shipment_in_transit',
        'ytd_sales',
        'sales_by_month_last_3_months',
        'sales_by_month_last_12_months',
        'input_data_by_month_12_months',
        'sold_values_by_month',
        'product_img',
        'order_amount',
        'ai_recommendation_data_by_month_12_months',
        'run_update',
        'run_status',
        'last12_total_sold',
        'ai_fc12_total',
        'system_fc12_total',
        'forecast_month',

    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'product_price' => 'array',
        'routes' => 'array',
        'shipment_in_transit' => 'array',
        'sales_by_month_last_3_months' => 'array',
        'sales_by_month_last_12_months' => 'array',
        'input_data_by_month_12_months' => 'array',
        'sold_values_by_month' => 'array',
        'ai_recommendation_data_by_month_12_months' => 'array',
    ];

    public function orderForecast()
    {
        return $this->belongsTo(OrderForecast::class, 'order_forecast_id');
    }

}
