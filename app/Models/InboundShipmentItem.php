<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InboundShipmentItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inbound_shipment_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'status',
    ];

    public function shipment()
    {
        return $this->belongsTo(InboundShipment::class, 'inbound_shipment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedUnitCostAttribute()
    {
        return number_format($this->unit_cost, 2);
    }

    public function getFormattedTotalCostAttribute()
    {
        return number_format($this->total_cost, 2);
    }
}
