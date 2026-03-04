<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'warehouse_name',
        'location',
    ];

    public function inventories()
    {
        return $this->hasMany(ProductWhInventory::class);
    }

    public function inboundShipments()
    {
        return $this->hasMany(InboundShipment::class);
    }
}
