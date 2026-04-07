<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use App\Ai\Tools\Concerns\LogsToolQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;
use Throwable;

abstract class BaseSqlTool implements Tool
{
    use LogsToolQuery;

    protected const DEFAULT_MAX_ROWS = 250;
    protected const HARD_MAX_ROWS = 10000;

    /**
     * Max rows to fetch for Excel/Download storage (High).
     */
    protected const EXPORT_MAX_ROWS = 5000;

    /**
     * Max rows to actually send to the AI (Higher for richer previews).
     */
    protected const CHAT_MAX_ROWS = 250;

    /**
     * Define the tables allowed for this tool.
     * Can return Model class names or raw table strings.
     *
     * @return array<string>
     */
    abstract protected function allowedTables(): array;

    /**
     * Optional database connection name.
     */
    protected function connectionName(): ?string
    {
        return null; // Override in child classes if needed.
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Stringable|string
    {
        return $this->executeSql($request, $this->allowedTables(), $this->connectionName());
    }

    /**
     * Execute the standard SQL tool flow.
     *
     * @param Request $request The tool request.
     * @param array<string> $allowedTables List of tables (calculated in executeSql).
     * @param string|null $connectionName Optional database connection name.
     * @param array<int|string, mixed> $bindings Optional SQL bindings.
     * @return string JSON response.
     */
    protected function executeSql(Request $request, array $allowedTables, ?string $connectionName = null, array $bindings = []): string
    {
        $traceId = $this->generateTraceId();
        $toolName = static::class;
        $startTime = microtime(true);

        $sql = $request['sql'] ?? '';
        $exportSql = $request['export_sql'] ?? $request['exportSql'] ?? null;
        $expectScalar = (bool) ($request['expect_scalar'] ?? false);
        $maxRows = $this->normalizeMaxRows($request['max_rows'] ?? null);

        if (!is_string($sql)) {
            throw new RuntimeException('SQL input must be a string.');
        }

        // Validate Chat SQL
        $normalizedSql = $this->normalizeSql($sql);
        $tableNames = [];
        foreach ($allowedTables as $table) {
            if (is_string($table) && class_exists($table) && is_subclass_of($table, Model::class)) {
                $tableNames[] = (new $table())->getTable();
            } else {
                $tableNames[] = (string) $table;
            }
        }
        $this->validateReadOnlySql($normalizedSql, $tableNames);

        // Validate Export SQL if provided
        $normalizedExportSql = null;
        if ($exportSql && is_string($exportSql)) {
            $normalizedExportSql = $this->normalizeSql($exportSql);
            $this->validateReadOnlySql($normalizedExportSql, $tableNames);
        }

        // Standardized userId detection
        $userId = $request['user_id'] ?? $request['userId'] ?? null;
        if (!$userId && function_exists('auth') && auth()->check()) {
            $userId = (string) auth()->id();
        }

        $inputParams = [
            'sql' => $sql,
            'export_sql' => $exportSql,
            'expect_scalar' => $expectScalar,
            'max_rows' => $maxRows,
            'bindings' => $bindings,
            'user_id' => $userId,
        ];

        try {
            $connection = $connectionName !== null
                ? DB::connection($connectionName)
                : DB::connection();

            if ($expectScalar) {
                $value = $connection->scalar($normalizedSql, $bindings);

                $meta = [
                    'trace_id' => $traceId,
                    'correlation' => $this->buildCorrelationMeta($traceId, $inputParams),
                    'execution_mode' => 'scalar',
                    'connection' => $connectionName ?? config('database.default'),
                ];

                $executionTime = round((microtime(true) - $startTime), 1);

                $this->logToolQuery(
                    toolName: $toolName,
                    traceId: $traceId,
                    query: $normalizedSql,
                    params: $inputParams,
                    context: ['status' => 'success', 'mode' => 'scalar'],
                    resultCount: $value === null ? 0 : 1,
                    executionTime: $executionTime,
                    aggregates: null,
                    meta: $meta,
                );

                return json_encode([
                    'success' => true,
                    'data' => ['value' => $value],
                    'meta' => $meta,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            $countSql = $this->wrapCountSql($normalizedSql);
            $totalRows = (int) $connection->scalar($countSql, $bindings);

            // Fetch a small set for the AI chat output.
            $aiLimit = min($maxRows, static::CHAT_MAX_ROWS);
            $limitedSql = $this->wrapLimitedSql($normalizedSql, $aiLimit);
            $rows = $connection->select($limitedSql, $bindings);

            $aiItems = array_map(
                static fn(object $row): array => (array) $row,
                $rows
            );

            $meta = [
                'trace_id' => $traceId,
                'correlation' => $this->buildCorrelationMeta($traceId, $inputParams),
                'execution_mode' => 'rows',
                'connection' => $connectionName ?? config('database.default'),
                'returned_rows' => count($aiItems),
                'total_rows' => $totalRows,
                'max_rows' => $maxRows,
                'is_preview' => $totalRows > count($aiItems),
            ];

            if ($totalRows > $aiLimit) {
                $meta['warning'] = "Result set truncated to the chat limit of " . static::CHAT_MAX_ROWS . " rows out of {$totalRows}. The full data is available via the Download button.";
            }

            $executionTime = round((microtime(true) - $startTime), 1);

            $this->logToolQuery(
                toolName: $toolName,
                traceId: $traceId,
                query: $normalizedSql,
                params: $inputParams,
                context: ['status' => 'success', 'mode' => 'rows'],
                resultCount: count($aiItems),
                executionTime: $executionTime,
                aggregates: null,
                meta: $meta,
            );

            // Store validated SQL in session for Excel download (Ultra-lightweight!)
            session([
                'ai_last_query_result' => [
                    'sql' => $normalizedExportSql ?? $normalizedSql,
                    'chat_sql' => $normalizedSql,
                    'connection' => $connectionName ?? config('database.default'),
                    'tool' => $toolName,
                    'bindings' => $bindings,
                    'timestamp' => now()->toDateTimeString(),
                    'trace_id' => $traceId,
                    'source' => $normalizedExportSql ? 'ai_export' : 'ai_chat',
                ]
            ]);

            return json_encode([
                'success' => true,
                'items' => $aiItems,
                'meta' => $meta,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime), 1);

            $this->logToolException($toolName, $traceId, $e, $inputParams, [], $executionTime);

            return json_encode([
                'success' => false,
                'error' => 'Query execution failed: ' . $e->getMessage() . '. You MUST provide a valid MySQL SELECT query.',
                'meta' => ['trace_id' => $traceId]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    protected function normalizeSql(string $sql): string
    {
        $sql = trim($sql);
        if ($sql === '') {
            throw new RuntimeException('SQL is required.');
        }
        $sql = preg_replace('/;+\s*$/', '', $sql) ?? $sql;
        $sql = trim($sql);
        if ($sql === '') {
            throw new RuntimeException('SQL is empty after normalization.');
        }
        return $sql;
    }

    protected function normalizeMaxRows(mixed $maxRows): int
    {
        if ($maxRows === null || $maxRows === '') {
            return static::DEFAULT_MAX_ROWS;
        }
        $value = (int) $maxRows;
        if ($value < 1) {
            return static::DEFAULT_MAX_ROWS;
        }
        return min($value, static::HARD_MAX_ROWS);
    }

    protected function validateReadOnlySql(string $sql, array $allowedTables): void
    {
        if (str_contains($sql, "\0")) {
            throw new RuntimeException('Invalid SQL payload.');
        }

        if (
            preg_match('/--[^\r\n]*$/m', $sql) === 1 ||
            preg_match('/\/\*/', $sql) === 1 ||
            preg_match('/^\s*#/m', $sql) === 1
        ) {
            throw new RuntimeException('SQL comments are not allowed.');
        }

        if (str_contains($sql, ';')) {
            throw new RuntimeException('Multiple SQL statements are not allowed.');
        }

        if (!preg_match('/^\s*(select|with)\b/i', $sql)) {
            throw new RuntimeException('Only SELECT queries are allowed.');
        }

        $blockedPatterns = [
            '/\binsert\b/i',
            '/\bupdate\b/i',
            '/\bdelete\b/i',
            '/\breplace\b/i',
            '/\bdrop\b/i',
            '/\btruncate\b/i',
            '/\balter\b/i',
            '/\bcreate\b/i',
            '/\brename\b/i',
            '/\bgrant\b/i',
            '/\brevoke\b/i',
            '/\bcall\b/i',
            '/\bdo\b/i',
            '/\bhandler\b/i',
            '/\bload_file\s*\(/i',
            '/\binto\s+outfile\b/i',
            '/\binto\s+dumpfile\b/i',
            '/\bsleep\s*\(/i',
            '/\bbenchmark\s*\(/i',
            '/\binformation_schema\b/i',
            '/\bperformance_schema\b/i',
            '/\bmysql\b/i',
            '/\bsys\b/i',
            '/\bshow\b/i',
            '/\bdescribe\b/i',
            '/\bexplain\b/i',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $sql) === 1) {
                throw new RuntimeException('Blocked SQL token detected. Only safe read-only SELECT queries are allowed.');
            }
        }

        $this->extraValidation($sql);

        $cteAliases = $this->extractCteAliases($sql);
        $referencedTables = $this->extractReferencedTables($sql);

        $normalizedAllowedTables = array_map([$this, 'normalizeIdentifier'], $allowedTables);

        foreach ($referencedTables as $table) {
            $normalized = $this->normalizeIdentifier($table);
            if (in_array($normalized, $normalizedAllowedTables, true)) {
                continue;
            }
            if (in_array($normalized, $cteAliases, true)) {
                continue;
            }
            throw new RuntimeException("Query references disallowed table or alias: {$table}");
        }

        if ($referencedTables === []) {
            throw new RuntimeException('Query must reference the allowed tables.');
        }
    }

    /**
     * Hook for additional validation rules.
     */
    protected function extraValidation(string $sql): void
    {
        // Override in child classes if needed.
    }

    protected function extractCteAliases(string $sql): array
    {
        $aliases = [];
        if (!preg_match('/^\s*with\b/i', $sql)) {
            return $aliases;
        }
        preg_match_all('/(?:^|,)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s+as\s*\(/i', $sql, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $alias) {
                $aliases[] = $this->normalizeIdentifier($alias);
            }
        }
        return array_values(array_unique($aliases));
    }

    protected function extractReferencedTables(string $sql): array
    {
        $tables = [];
        preg_match_all('/\b(?:from|join)\s+(`?[a-zA-Z0-9_\.]+`?)/i', $sql, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $table) {
                $clean = trim($table, '`');
                if ($clean !== '') {
                    $tables[] = $clean;
                }
            }
        }
        return array_values(array_unique($tables));
    }

    protected function normalizeIdentifier(string $identifier): string
    {
        return mb_strtolower(trim($identifier, '`'));
    }

    protected function wrapCountSql(string $sql): string
    {
        return "select count(*) as aggregate_count from ({$sql}) as ai_result_count";
    }

    protected function wrapLimitedSql(string $sql, int $maxRows): string
    {
        return "select * from ({$sql}) as ai_result_rows limit {$maxRows}";
    }
}
