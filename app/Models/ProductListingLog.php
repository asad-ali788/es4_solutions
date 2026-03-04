<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductListingLog extends Model
{
    use SoftDeletes;

    protected $table = 'product_listing_logs';

    protected $fillable = [
        'product_id',
        'field_name',
        'old_value',
        'new_value',
        'user_id',
        'country',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
