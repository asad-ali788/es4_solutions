<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompMappingBi extends Model
{
    protected $table = 'comp_mapping';

    protected $fillable = [
        'asin',
        'comp_asin',
        'brand',
    ];
}
