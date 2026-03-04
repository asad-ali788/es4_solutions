<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempProductPricing extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_product_pricings';

    protected $fillable = [
        'asin',
        'marketplace_id',
        'seller_sku',
        'offer_type',
        'listing_price',
        'landed_price',
        'shipping_price',
        'regular_price',
        'business_price',
        'points_number',
        'points_value',
        'item_condition',
        'item_sub_condition',
        'fulfillment_channel',
        'sales_rankings',
        'quantity_discount_prices'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
