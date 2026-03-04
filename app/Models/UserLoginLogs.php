<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginLogs extends Model
{
    protected $table = 'user_login_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'browser',
        'session_id',
        'platform',
        'logged_in_at',
        'logged_out_at',
        'is_success',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'is_success' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        // Add your date columns here
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
