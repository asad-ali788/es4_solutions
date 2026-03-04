<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderLog extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_order_logs';

    protected $fillable = [
        'purchase_order_id',
        'changed_by',
        'previous_status',
        'new_status',
        'change_reason',
        'changed_at',
    ];

    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
