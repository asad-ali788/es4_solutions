<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAsins extends Model
{
    use SoftDeletes;

    protected $table = 'product_asins';

    protected $fillable = [
        'product_id',
        'asin1',
        'asin2',
        'asin3',
        'catalog_item_status',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'catalog_item_status' => 'boolean',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_asins', 'asin1', 'product_id', 'asin1', 'id');
    }

    public function categorisation()
    {
        return $this->hasOne(ProductCategorisation::class, 'child_asin', 'asin1')
            ->whereNull('deleted_at'); // SoftDeletes safe
    }
}
