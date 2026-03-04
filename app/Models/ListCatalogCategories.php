<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListCatalogCategories extends Model
{
    //
    use SoftDeletes;

    protected $table = 'list_catalog_categories';

    protected $fillable = [
        'asin',
        'marketplace_id',
        'seller_sku',
        'catalog_categories',
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
