<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempSalesOrder extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_sales_orders';

    protected $fillable = [
        'amazon_order_id',
        'merchant_order_id',
        'purchase_date',
        'last_updated_date',
        'order_status',
        'fulfillment_channel',
        'sales_channel',
        'order_channel',
        'ship_service_level',
        'product_name',
        'sku',
        'asin',
        'item_status',
        'quantity',
        'currency',
        'item_price',
        'item_tax',
        'shipping_price',
        'shipping_tax',
        'gift_wrap_price',
        'gift_wrap_tax',
        'item_promotion_discount',
        'ship_promotion_discount',
        'ship_city',
        'ship_state',
        'ship_postal_code',
        'ship_country',
        'promotion_ids',
        'cpf',
        'is_business_order',
        'purchase_order_number',
        'price_designation',
        'signature_confirmation_recommended',
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
