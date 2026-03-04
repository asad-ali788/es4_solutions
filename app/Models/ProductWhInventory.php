<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductWhInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_wh_inventory';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'available_quantity',
        'reserved_quantity',
        'updated_at',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
