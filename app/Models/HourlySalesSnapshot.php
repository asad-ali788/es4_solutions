<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HourlySalesSnapshot extends Model
{
    use SoftDeletes;

    protected $table = 'hourly_sales_snapshots';

    protected $fillable = [
        'snapshot_time',
        'total_units',
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
