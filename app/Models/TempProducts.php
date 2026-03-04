<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempProducts extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_products';

    protected $fillable = [
        'item_name',
        'item_description',
        'listing_id',
        'seller_sku',
        'price',
        'quantity',
        'open_date',
        'image_url',
        'item_is_marketplace',
        'product_id_type',
        'zshop_shipping_fee',
        'item_note',
        'item_condition',
        'zshop_category1',
        'zshop_browse_path',
        'zshop_storefront_feature',
        'asin1',
        'asin2',
        'asin3',
        'will_ship_internationally',
        'expedited_shipping',
        'zshop_boldface',
        'product_id',
        'bid_for_featured_placement',
        'add_delete',
        'pending_quantity',
        'fulfillment_channel',
        'merchant_shipping_group',
        'status',
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
