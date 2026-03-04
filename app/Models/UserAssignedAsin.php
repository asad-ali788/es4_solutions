<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAssignedAsin extends Model
{
    use SoftDeletes;

    protected $table = 'user_assigned_asins';

    protected $fillable = [
        'user_id',
        'asin',
        'sku',
        'assigned_by_id',
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
