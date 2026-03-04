<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPricing extends Model
{
    use SoftDeletes;

    protected $table = 'product_pricings';

    protected $fillable = [
        'item_price',
        'postage',
        'base_price',
        'fba_fee',
        'duty',
        'air_ship',
        'product_listings_id',
    ];

    public function productListing()
    {
        return $this->belongsTo(ProductListing::class);
    }
}
