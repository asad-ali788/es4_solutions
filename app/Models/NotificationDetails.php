<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationDetails extends Model
{
    //
    use SoftDeletes;

    protected $table = 'notification_details';

    protected $fillable = [
        'sku',
        'quantity_available',
        'stock_status',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
