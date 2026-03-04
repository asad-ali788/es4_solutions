<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InboundShipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shipment_name',
        'supplier_id',
        'warehouse_id',
        'status',
        'tracking_number',
        'carrier_name',
        'dispatch_date',
        'expected_arrival',
        'actual_arrival',
        'shipping_notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(InboundShipmentItem::class);
    }
}
