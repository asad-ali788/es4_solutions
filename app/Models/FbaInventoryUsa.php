<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbaInventoryUsa extends Model
{
    //
    use SoftDeletes;

    protected $table = 'fba_inventory_usa';

    protected $fillable = [
        'sku',
        'instock',
        'totalstock',
        'reserve_stock',
        'country',

    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'add_date',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function afnInventoryData()
    {
        return $this->hasMany(AfnInventoryData::class, 'seller_sku', 'sku');
    }

    /**
     * Relationship: One FBA SKU can have many Inbound Shipment entries.
     */
    public function inboundShipments()
    {
        return $this->hasMany(InboundShipmentDetailsSp::class, 'sku', 'sku');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'sku', 'sku');
    }


    public function productWhInventories()
    {
        return $this->hasManyThrough(
            ProductWhInventory::class,
            Product::class,
            'sku',               // Foreign key on Product (intermediate) table
            'product_id',        // Foreign key on ProductWhInventory table
            'sku',               // Local key on FbaInventoryUsa
            'id'                 // Local key on Product
        );
    }
}
