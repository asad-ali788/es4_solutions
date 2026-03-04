<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    //
    use SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'notification_id',
        'assigned_user_id',
        'title',
        'details',
        'level',
        'read_status',
        'created_date',
        'read_date',
        'handler',
    ];

    protected $dates = [
        'created_date',
        'read_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
    public function handlerUser()
    {
        return $this->belongsTo(User::class, 'handler');
    }

    public function details()
    {
        return $this->hasMany(NotificationDetails::class);
    }
}
