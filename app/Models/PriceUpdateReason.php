<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceUpdateReason extends Model
{
    use SoftDeletes;

    protected $table = 'price_update_reasons';

    protected $fillable = [
        'reason_code',
        'reason_detail',
        'description',
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
