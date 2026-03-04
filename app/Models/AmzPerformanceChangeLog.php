<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzPerformanceChangeLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'amz_performance_change_logs';

    protected $fillable = [
        'change_type',
        'campaign_id',
        'keyword_id',
        'target_id',
        'country',
        'old_value',
        'new_value',
        'type',
        'user_id',
        'executed_at',
        'date',
        'run_update',
        'run_status',
        'reverted_by',
        'revert_executed_at',
    ];
    protected $casts = [
        'executed_at' => 'datetime',
        'date' => 'date',
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'run_update' => 'boolean',
        'revert_executed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public static function logChange(array $data): self
    {
        return self::create([
            'change_type' => $data['change_type'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'keyword_id' => $data['keyword_id'] ?? null,
            'target_id' => $data['target_id'] ?? null,
            'country' => $data['country'] ?? null,
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'type' => $data['type'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'executed_at' => $data['executed_at'] ?? now(),
            'date' => $data['date'] ?? now(),
        ]);
    }
}
