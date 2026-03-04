<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempInventorySummaries extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_inventory_summaries';

    protected $fillable = [
        'asin',
        'fn_sku',
        'seller_sku',
        'condition',
        'last_updated_time',
        'product_name',
        'total_quantity',
        'fulfillableQuantity',
        'inboundWorkingQuantity',
        'inboundShippedQuantity',
        'inboundReceivingQuantity',
        'inventoryDetails',
        'totalReservedQuantity',
        'stores',
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
