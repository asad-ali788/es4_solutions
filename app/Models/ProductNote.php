<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductNote extends Model
{
    use SoftDeletes;

    protected $table = 'product_notes';

    protected $fillable = [
        'product_id',
        'user_id',
        'note',
        'priority',
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
