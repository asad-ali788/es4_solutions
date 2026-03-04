<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductRanking extends Model
{
    use SoftDeletes;

    protected $table = 'product_rankings';

    protected $fillable = [
        'product_id',
        'date',
        'current_price',
        'rank',
        'country',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Optional: If you have a Product model
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
