<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InboundShipmentSp extends Model
{
    use SoftDeletes;

    protected $table = 'inbound_shipment_sps';

    protected $fillable = [
        'shipment_id',
        'ship_status',
        'add_date',
        'ship_arrival_date',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'add_date', 'ship_arrival_date'];

    public function details()
    {
        return $this->hasMany(InboundShipmentDetailsSp::class, 'ship_id', 'shipment_id');
    }
}
