<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategorisation extends Model
{
    use SoftDeletes;

    protected $table = 'product_categorisations';

    protected $fillable = [
        'parent_short_name',
        'child_short_name',
        'parent_asin',
        'child_asin',
        'marketplace',
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
