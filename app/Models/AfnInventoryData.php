<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AfnInventoryData extends Model
{
    use SoftDeletes;

    protected $table = 'afn_inventory_data';

    protected $fillable = [
        'seller_sku',
        'fulfillment_channel_sku',
        'asin',
        'condition_type',
        'warehouse_condition_code',
        'quantity_available',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
