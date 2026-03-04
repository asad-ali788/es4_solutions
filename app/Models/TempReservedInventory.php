<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempReservedInventory extends Model
{
    //
    use SoftDeletes;

    protected $table = 'temp_reserved_inventories';

    protected $fillable = [
        'sku',
        'fnsku',
        'asin',
        'product_name',
        'reserved_qty',
        'reserved_customerorders',
        'reserved_fc_transfers',
        'reserved_fc_processing',
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
