<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsinInventorySummaryBi extends Model
{
    protected $table = 'asin_inventory_summary_bi';

    protected $fillable = [
        'asin',
        'fba_available',
        'fba_inbound',
        'fc_reserved',
        'awd_available',
        'awd_inbound',
        'apa_warehouse_available',
        'shipment_quantity',
        'awd_to_fba_quantity',
        'flex_warehouse_available',
        'tactical_warehouse_inventory',
        'last_synced_at',
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
