<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipOutWarehouse extends Model
{
    use SoftDeletes;

    protected $table = 'ship_out_warehouses';

    protected $fillable = [
        'warehouse_sku',
        'amazon_sku',
        'asin',
        'warehouse_id',
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
