<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceUpdateQueue extends Model
{
    use SoftDeletes;

    protected $table = 'price_update_queues';

    protected $fillable = [
        'sku',
        'product_id',
        'country',
        'currency',
        'feed_id',
        'new_price',
        'old_price',
        'base_price',
        'status',
        'pi_user_id',
        'reference',
        'price_update_reason_id',
        'added_date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // public function priceFeedLogs()
    // {
    //     return $this->hasMany(PriceFeedLog::class, 'price_update_queue_id');
    // }
}
