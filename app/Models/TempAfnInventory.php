<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempAfnInventory extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_afn_inventories';

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
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
