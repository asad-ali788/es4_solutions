<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductContainerInfo extends Model
{
    use SoftDeletes;

    protected $table = 'product_container_infos';

    protected $fillable = [
        'commercial_invoice_title',
        'hs_code',
        'hs_code_percentage',
        'item_size_length_cm',
        'item_size_width_cm',
        'item_size_height_cm',
        'ctn_size_length_cm',
        'ctn_size_width_cm',
        'ctn_size_height_cm',
        'item_weight_kg',
        'carton_weight_kg',
        'quantity_per_carton',
        'carton_cbm',
        'moq',
        'product_material',
        'order_lead_time_weeks',
        'product_listings_id',
    ];

    public function productListing()
    {
        return $this->belongsTo(ProductListing::class);
    }
}
