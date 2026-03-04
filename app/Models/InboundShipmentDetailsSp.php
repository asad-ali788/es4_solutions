<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InboundShipmentDetailsSp extends Model
{
    use SoftDeletes;

    protected $table = 'inbound_shipment_details_sps';

    protected $fillable = [
        'sku',
        'qty_ship',
        'qty_received',
        'ship_id',
        'add_date',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'add_date'];

    public function shipment()
    {
        return $this->belongsTo(InboundShipmentSp::class, 'ship_id', 'shipment_id');
    }
}
