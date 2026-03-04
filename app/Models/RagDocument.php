<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagDocument extends Model
{
    protected $fillable = [
        'doc_key',
        'source',
        'source_row_id',
        'content_hash',
        'embedded_at',
    ];

    protected $casts = [
        'embedded_at' => 'datetime',
    ];
}
