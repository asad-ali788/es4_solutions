<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HourlyProductSales extends Model
{
    use SoftDeletes;

    protected $table = 'hourly_product_sales';

    protected $fillable = [
        'sku',
        'asin',
        'sales_channel',
        'purchase_date',
        'sale_hour',
        'total_units',
        'item_price',
        'currency',
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

    // In HourlyProductSales model
    public function productCategorisation()
    {
        return $this->hasOne(ProductCategorisation::class, 'child_asin', 'asin');
    }
}
