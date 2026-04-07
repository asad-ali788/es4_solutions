<?php

namespace App\Ai\Tools\Concerns;

use App\Models\Ai\AiToolQueryLog;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Facades\Log;
use Throwable;

trait LogsToolQuery
{
    protected function generateTraceId(): string
    {
        return (string) str()->uuid();
    }

    protected function logToolQuery(
        string $toolName,
        string $traceId,
        $query,
        array $params,
        array $context = [],
        int $resultCount = 0,
        float $executionTime = 0,
        ?array $aggregates = null,
        ?array $meta = null,
    ): void {

        $correlation = $this->extractCorrelationContext($params, $traceId);
        if (is_string($query)) {
            $sql = $query;
            $bindings = [];
        } else {
            $baseQuery = $query instanceof EloquentBuilder ? $query->toBase() : $query;
            $sql = $baseQuery->toSql();
            $bindings = $baseQuery->getBindings();
        }
        $includeInterpolatedSql = (bool) config('logging.channels.ai_tools.include_interpolated_sql', false);
        $interpolatedSql = $this->interpolateSql($sql, $bindings);

        // Log to Laravel logs (for debugging SQL)
        Log::channel('ai_tools')->info('ai.tool.query', [
            'trace_id' => $traceId,
            'tool' => $toolName,
            'correlation' => $correlation,
            'params' => $params,
            'sql' => $sql,
            'bindings' => $bindings,
            'context' => $context,
            'result_count' => $resultCount,
            'execution_time_ms' => $executionTime,
            ...($includeInterpolatedSql ? ['sql_with_bindings' => $interpolatedSql] : []),
        ]);

        if (! $this->shouldPersistToDatabase($context)) {
            return;
        }

        // Also save to database for persistent tracking and debugging
        try {
            // Store the actual SQL query text (with bindings applied), not a hash.
            $queryHash = $interpolatedSql;
            $metaWithSql = $this->appendSqlToMeta($meta, $interpolatedSql);

            AiToolQueryLog::create([
                'tool_name' => class_basename($toolName),
                'trace_id' => $correlation['trace_id'],
                'user_id' => $correlation['user_id'],
                'parameters' => $params,
                'result_count' => $resultCount,
                'aggregates' => $aggregates,
                'meta' => $metaWithSql,
                'execution_time_ms' => $executionTime,
                'success' => true,
                'error_message' => null,
                'query_hash' => $queryHash,
            ]);
        } catch (Throwable $e) {
            Log::channel('ai_tools')->warning('Failed to save AI tool query log to database: ' . $e->getMessage());
        }
    }

    protected function logToolException(
        string $toolName,
        string $traceId,
        Throwable $exception,
        array $params = [],
        array $context = [],
        float $executionTime = 0,
    ): void {
        $correlation = $this->extractCorrelationContext($params, $traceId);

        // Log to Laravel logs
        Log::channel('ai_tools')->error('ai.tool.error', [
            'trace_id' => $traceId,
            'tool' => $toolName,
            'correlation' => $correlation,
            'params' => $params,
            'context' => $context,
            'execution_time_ms' => $executionTime,
            'error' => [
                'message' => $exception->getMessage(),
                'class' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
        ]);

        // Save to database for persistent tracking
        try {
            $queryHash = 'error: ' . $exception->getMessage();

            AiToolQueryLog::create([
                'tool_name' => class_basename($toolName),
                'trace_id' => $correlation['trace_id'],
                'user_id' => $correlation['user_id'],
                'parameters' => $params,
                'result_count' => 0,
                'execution_time_ms' => $executionTime,
                'success' => false,
                'error_message' => $exception->getMessage(),
                'query_hash' => $queryHash,
            ]);
        } catch (Throwable $e) {
            Log::channel('ai_tools')->warning('Failed to save AI tool error log to database: ' . $e->getMessage());
        }
    }

    protected function buildCorrelationMeta(string $traceId, array $params): array
    {
        return $this->extractCorrelationContext($params, $traceId);
    }

    private function extractCorrelationContext(array $params, string $traceId): array
    {
        return [
            'trace_id' => $traceId,
            'user_id' => $this->pickFirstNonEmpty($params, ['user_id', 'userId', 'userid', 'user', 'user_ifd', 'customer_id', 'customerId']),
        ];
    }

    private function shouldPersistToDatabase(array $context): bool
    {
        $status = strtolower((string) ($context['status'] ?? ''));

        return in_array($status, ['success', 'validation_failed'], true);
    }

    private function pickFirstNonEmpty(array $source, array $keys): string|null
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }

            $value = $source[$key];
            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }

                return $value;
            }

            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function interpolateSql(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            $replacement = match (true) {
                $binding === null => 'null',
                is_bool($binding) => $binding ? '1' : '0',
                is_int($binding), is_float($binding) => (string) $binding,
                $binding instanceof DateTimeInterface => "'{$binding->format('Y-m-d H:i:s')}'",
                default => "'" . str_replace("'", "''", (string) $binding) . "'",
            };

            $sql = preg_replace('/\?/', $replacement, $sql, 1) ?? $sql;
        }

        return $sql;
    }

    private function appendSqlToMeta(?array $meta, string $sql): array
    {
        $meta = $meta ?? [];
        $meta['query_sql_full'] = $sql;

        return $meta;
    }
}
