<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisedProductBi extends Model
{
    protected $table = 'advertised_product_bi';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'report_date',
        'campaign_name',
        'currency',
        'asin',
        'units',
        'sales',
    ];

    /**
     * Type casting
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',

            'units' => 'integer',
            'sales' => 'decimal:2',

            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
