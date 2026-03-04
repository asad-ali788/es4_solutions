<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagIndexState extends Model
{
    protected $fillable = ['source', 'last_id', 'last_updated_at'];

    protected $casts = [
        'last_updated_at' => 'datetime',
    ];
}
