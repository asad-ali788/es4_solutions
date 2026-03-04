<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'sku',
        'fnsku',
        'short_title',
        'status',
    ];

    public function listings()
    {
        return $this->hasMany(ProductListing::class, 'products_id');
    }

    public function inboundShipmentItems()
    {
        return $this->hasMany(InboundShipmentItem::class);
    }

    public function purchaseOrder()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function orderForecastSnapshots()
    {
        return $this->hasMany(OrderForecastSnapshot::class);
    }

    public function productForecasts()
    {
        return $this->hasMany(ProductForecast::class, 'product_id');
    }

    public function asins()
    {
        return $this->hasOne(ProductAsins::class);
    }
    
    public function afnInventory()
    {
        return $this->hasMany(AfnInventoryData::class, 'seller_sku', 'sku');
    }

    public function fbaInventory()
    {
        return $this->hasMany(FbaInventoryUsa::class, 'sku', 'sku');
    }

    public function inboundShipments()
    {
        return $this->hasMany(InboundShipmentDetailsSp::class, 'sku', 'sku');
    }

    public function whInventories()
    {
        return $this->hasMany(ProductWhInventory::class, 'product_id', 'id');
    }
}
