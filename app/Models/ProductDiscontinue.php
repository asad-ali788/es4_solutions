<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDiscontinue extends Model
{
    use SoftDeletes;

    protected $table = 'product_discontinue';

    protected $fillable = [
        'products_id',
        'country',
        'reason_of_dis',
        'discontinued_at',
    ];

    public function product()
    {
        return $this->belongsTo(ProductListing::class, 'products_id', 'products_id');
    }
}
