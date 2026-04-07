<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandAnalytics2024Bi extends Model
{
    protected $table = 'brand_analytics_2024_bis';

    protected $fillable = [
        'asin',
        'name',
        'search_query',
        'search_query_score',
        'search_query_volume',
        'reporting_date',
        'week',
        'year',
        'impressions_total_count',
        'impressions_asin_count',
        'clicks_total_count',
        'clicks_asin_count',
        'clicks_price_median',
        'clicks_asin_price_median',
        'clicks_shipping_same_day',
        'clicks_shipping_1d',
        'clicks_shipping_2d',
        'cart_adds_total_count',
        'cart_adds_asin_count',
        'purchases_total_count',
        'purchases_asin_count',
    ];

    protected $casts = [
        'reporting_date' => 'date',
        'clicks_price_median' => 'decimal:2',
        'clicks_asin_price_median' => 'decimal:2',
    ];
}
