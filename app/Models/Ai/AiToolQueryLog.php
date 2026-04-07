<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

class AiToolQueryLog extends Model
{
    protected $table = 'ai_tool_query_logs';

    protected $fillable = [
        'tool_name',
        'trace_id',
        'user_id',
        'parameters',
        'result_count',
        'aggregates',
        'meta',
        'execution_time_ms',
        'success',
        'error_message',
        'query_hash',
    ];

    protected $casts = [
        'parameters' => 'array',
        'aggregates' => 'array',
        'meta' => 'array',
        'success' => 'boolean',
        'execution_time_ms' => 'float',
        'result_count' => 'integer',
    ];

    /**
     * Scope to find queries by tool name
     */
    public function scopeForTool($query, string $toolName)
    {
        return $query->where('tool_name', $toolName);
    }

    /**
     * Scope to find recent queries
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to find failed queries
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Find duplicate queries (same hash within time period)
     */
    public function scopeDuplicates($query, int $minutes = 60)
    {
        $subQuery = self::selectRaw('query_hash')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->groupBy('query_hash')
            ->havingRaw('COUNT(*) > 1');

        return $query->whereIn('query_hash', $subQuery);
    }

    /**
     * Scope by user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by trace ID
     */
    public function scopeForTrace($query, string $traceId)
    {
        return $query->where('trace_id', $traceId);
    }

    /**
     * Get similar queries (same parameters, different timing)
     */
    public static function findSimilar(string $queryHash, int $limit = 10)
    {
        return self::where('query_hash', $queryHash)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Find all queries in a trace
     */
    public static function findByTrace(string $traceId)
    {
        return self::where('trace_id', $traceId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
