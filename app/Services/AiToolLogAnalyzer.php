<?php

namespace App\Services;

use App\Models\Ai\AiToolQueryLog;
use Illuminate\Support\Collection;

/**
 * AiToolLogAnalyzer - Utility for analyzing AI tool query logs
 * 
 * Usage:
 *   $analyzer = new AiToolLogAnalyzer();
 *   
 *   // Find inconsistent results
 *   $inconsistencies = $analyzer->findInconsistencies('UnifiedPerformanceQuery', 24);
 *   
 *   // Compare two queries
 *   $comparison = $analyzer->compareQueries($query1Id, $query2Id);
 *   
 *   // Find slow queries
 *   $slowQueries = $analyzer->findSlowQueries('UnifiedPerformanceQuery', minTime: 5000);
 */
class AiToolLogAnalyzer
{
    /**
     * Find queries with same parameters that returned different result counts
     * This helps detect data consistency issues
     */
    public function findInconsistencies(string $toolName, int $hours = 24): Collection
    {
        $logs = AiToolQueryLog::forTool($toolName)
            ->recent($hours)
            ->orderBy('query_hash')
            ->orderBy('created_at', 'desc')
            ->get();

        $inconsistencies = collect();

        // Group by query hash
        $logs->groupBy('query_hash')->each(function ($group) use (&$inconsistencies) {
            if ($group->count() < 2) {
                return; // Skip single executions
            }

            $resultCounts = $group->pluck('result_count')->unique();

            // If same parameters returned different result counts, flag it
            if ($resultCounts->count() > 1) {
                $inconsistencies->push([
                    'query_hash' => $group->first()->query_hash,
                    'parameters' => $group->first()->parameters,
                    'execution_count' => $group->count(),
                    'result_count_range' => [
                        'min' => $resultCounts->min(),
                        'max' => $resultCounts->max(),
                        'unique_counts' => $resultCounts->values()->toArray(),
                    ],
                    'executions' => $group->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'result_count' => $log->result_count,
                            'execution_time_ms' => round($log->execution_time_ms, 2),
                            'created_at' => $log->created_at,
                            'aggregates' => $log->aggregates,
                        ];
                    })->toArray(),
                ]);
            }
        });

        return $inconsistencies;
    }

    /**
     * Find queries that took significantly longer than average
     */
    public function findSlowQueries(
        string $toolName,
        int $hours = 24,
        ?float $minTime = null,
        ?float $percentileThreshold = 90
    ): Collection {
        $logs = AiToolQueryLog::forTool($toolName)
            ->recent($hours)
            ->get();

        if ($logs->isEmpty()) {
            return collect();
        }

        $times = $logs->pluck('execution_time_ms')->values();
        $threshold = $minTime ?? $this->calculatePercentile($times, $percentileThreshold ?? 90);

        return $logs->filter(fn($log) => $log->execution_time_ms >= $threshold)
            ->sortByDesc('execution_time_ms')
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'tool' => $log->tool_name,
                    'execution_time_ms' => round($log->execution_time_ms, 2),
                    'result_count' => $log->result_count,
                    'parameters' => $log->parameters,
                    'created_at' => $log->created_at,
                ];
            });
    }

    /**
     * Compare execution results between two queries
     */
    public function compareQueries(int $id1, int $id2): array
    {
        $log1 = AiToolQueryLog::find($id1);
        $log2 = AiToolQueryLog::find($id2);

        if (!$log1 || !$log2) {
            return ['error' => 'One or both log entries not found'];
        }

        $paramsDiff = $this->diffArrays($log1->parameters, $log2->parameters);
        $aggregatesDiff = $this->diffArrays($log1->aggregates ?? [], $log2->aggregates ?? []);

        return [
            'log1' => [
                'id' => $log1->id,
                'tool' => $log1->tool_name,
                'result_count' => $log1->result_count,
                'execution_time_ms' => round($log1->execution_time_ms, 2),
                'created_at' => $log1->created_at,
                'success' => $log1->success,
            ],
            'log2' => [
                'id' => $log2->id,
                'tool' => $log2->tool_name,
                'result_count' => $log2->result_count,
                'execution_time_ms' => round($log2->execution_time_ms, 2),
                'created_at' => $log2->created_at,
                'success' => $log2->success,
            ],
            'parameter_differences' => $paramsDiff,
            'aggregate_differences' => $aggregatesDiff,
            'result_count_difference' => $log2->result_count - $log1->result_count,
            'time_difference_ms' => round($log2->execution_time_ms - $log1->execution_time_ms, 2),
        ];
    }

    /**
     * Get statistics for a tool's execution
     */
    public function getToolStatistics(string $toolName, int $hours = 24): array
    {
        $logs = AiToolQueryLog::forTool($toolName)
            ->recent($hours)
            ->get();

        if ($logs->isEmpty()) {
            return ['count' => 0, 'message' => 'No logs found'];
        }

        $successCount = $logs->where('success', true)->count();
        $failureCount = $logs->where('success', false)->count();
        $times = $logs->pluck('execution_time_ms')->values();
        $resultCounts = $logs->pluck('result_count')->values();

        return [
            'total_executions' => $logs->count(),
            'successful' => $successCount,
            'failed' => $failureCount,
            'success_rate' => round(($successCount / $logs->count()) * 100, 2) . '%',
            'execution_time' => [
                'min_ms' => round($times->min(), 2),
                'max_ms' => round($times->max(), 2),
                'avg_ms' => round($times->avg(), 2),
                'p50_ms' => round($this->calculatePercentile($times, 50), 2),
                'p90_ms' => round($this->calculatePercentile($times, 90), 2),
                'p99_ms' => round($this->calculatePercentile($times, 99), 2),
            ],
            'result_count' => [
                'min' => $resultCounts->min(),
                'max' => $resultCounts->max(),
                'avg' => round($resultCounts->avg(), 0),
            ],
            'last_error' => $logs->where('success', false)->first()?->error_message,
            'last_execution' => $logs->first()?->created_at,
        ];
    }

    /**
     * Find duplicate executions (same parameters, different times)
     */
    public function findDuplicates(string $toolName, int $minutes = 60): Collection
    {
        return AiToolQueryLog::forTool($toolName)
            ->recent($minutes)
            ->duplicates($minutes)
            ->orderBy('query_hash')
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('query_hash')
            ->map(function ($group) {
                return [
                    'query_hash' => $group->first()->query_hash,
                    'parameters' => $group->first()->parameters,
                    'execution_count' => $group->count(),
                    'time_span_minutes' => $group->first()->created_at->diffInMinutes($group->last()->created_at),
                    'executions' => $group->map(fn($log) => [
                        'id' => $log->id,
                        'result_count' => $log->result_count,
                        'created_at' => $log->created_at,
                    ])->toArray(),
                ];
            });
    }

    /**
     * Calculate percentile from a collection of values
     */
    private function calculatePercentile(Collection $values, float $percentile): float
    {
        if ($values->isEmpty()) {
            return 0;
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $index = (($percentile / 100) * $count) - 1;

        if ($index < 0) {
            return $sorted->first();
        }

        if ($index >= $count - 1) {
            return $sorted->last();
        }

        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        $weight = $index - $lower;

        return $sorted[$lower] * (1 - $weight) + $sorted[$upper] * $weight;
    }

    /**
     * Find differences between two arrays
     */
    private function diffArrays(?array $array1, ?array $array2): array
    {
        $array1 = $array1 ?? [];
        $array2 = $array2 ?? [];

        $differences = [];

        // Find changed and removed keys
        foreach ($array1 as $key => $value) {
            if (!isset($array2[$key])) {
                $differences[$key] = ['removed', $value, null];
            } elseif ($array1[$key] != $array2[$key]) {
                $differences[$key] = ['changed', $value, $array2[$key]];
            }
        }

        // Find added keys
        foreach ($array2 as $key => $value) {
            if (!isset($array1[$key])) {
                $differences[$key] = ['added', null, $value];
            }
        }

        return empty($differences) ? ['message' => 'No differences'] : $differences;
    }
}
