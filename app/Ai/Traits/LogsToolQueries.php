<?php

namespace App\Ai\Traits;

use App\Models\Ai\AiToolQueryLog;
use Illuminate\Support\Facades\Log;

/**
 * LogsToolQueries - Trait for logging AI tool executions
 * 
 * Usage in your tool:
 * 
 *   use LogsToolQueries;
 *   
 *   public function handle(Request $request)
 *   {
 *       $startTime = microtime(true);
 *       
 *       try {
 *           // your tool logic here
 *           $results = [...];
 *           $resultCount = count($results);
 *           $executionTime = (microtime(true) - $startTime) * 1000;
 *           
 *           $this->logQuery(
 *               toolName: self::class,
 *               parameters: $request->all(),
 *               resultCount: $resultCount,
 *               executionTime: $executionTime,
 *               success: true,
 *               aggregates: $meta['aggregates'] ?? null,
 *               meta: $meta ?? null,
 *           );
 *           
 *           return json_encode($results);
 *       } catch (Throwable $e) {
 *           $executionTime = (microtime(true) - $startTime) * 1000;
 *           
 *           $this->logQuery(
 *               toolName: self::class,
 *               parameters: $request->all(),
 *               resultCount: 0,
 *               executionTime: $executionTime,
 *               success: false,
 *               errorMessage: $e->getMessage(),
 *           );
 *           
 *           throw $e;
 *       }
 *   }
 */
trait LogsToolQueries
{
    /**
     * Log a tool query execution
     */
    protected function logQuery(
        string $toolName,
        array $parameters,
        int $resultCount = 0,
        float $executionTime = 0,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $aggregates = null,
        ?array $meta = null,
    ): void {
        try {
            // Create a deterministic hash of parameters for deduplication
            $queryHash = $this->createParameterHash($parameters);
            $correlation = $this->extractCorrelationContext($parameters);
            $lookup = ['query_hash' => $queryHash, 'tool_name' => class_basename($toolName)];

            if (! empty($correlation['trace_id'])) {
                $lookup = ['trace_id' => $correlation['trace_id']];
            }

            AiToolQueryLog::updateOrCreate($lookup, [
                'tool_name' => class_basename($toolName),
                'trace_id' => $correlation['trace_id'],
                'user_id' => $correlation['user_id'],
                'chat_id' => $correlation['chat_id'],
                'conversation_id' => $correlation['conversation_id'],
                'request_id' => $correlation['request_id'],
                'session_id' => $correlation['session_id'],
                'parameters' => $parameters,
                'result_count' => $resultCount,
                'aggregates' => $aggregates,
                'meta' => $meta,
                'execution_time_ms' => $executionTime,
                'success' => $success,
                'error_message' => $errorMessage,
                'query_hash' => $queryHash,
            ]);
        } catch (\Exception $e) {
            // Silently fail so logging errors don't break the tool
            Log::warning('Failed to log AI tool query: ' . $e->getMessage());
        }
    }

    /**
     * Create a hash of the parameters for deduplication
     */
    private function createParameterHash(array $parameters): string
    {
        // Remove timestamp-based params that always change
        $filterParams = $parameters;
        unset($filterParams['timestamp'], $filterParams['_token']);

        $normalized = $this->normalizeForHash($filterParams);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeForHash(array $params): array
    {
        ksort($params);

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $this->normalizeForHash($value);
            }
        }

        return $params;
    }

    private function extractCorrelationContext(array $params): array
    {
        return [
            'trace_id' => $this->pickFirstNonEmpty($params, ['trace_id', 'traceId']),
            'user_id' => $this->pickFirstNonEmpty($params, ['user_id', 'userId', 'userid', 'user', 'user_ifd', 'customer_id', 'customerId']),
            'chat_id' => $this->pickFirstNonEmpty($params, ['chat_id', 'chatId', 'chat']),
            'conversation_id' => $this->pickFirstNonEmpty($params, ['conversation_id', 'conversationId', 'conversation', 'thread_id', 'threadId']),
            'request_id' => $this->pickFirstNonEmpty($params, ['request_id', 'requestId', 'message_id', 'messageId', 'message']),
            'session_id' => $this->pickFirstNonEmpty($params, ['session_id', 'sessionId', 'session']),
        ];
    }

    private function pickFirstNonEmpty(array $source, array $keys): ?string
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

    /**
     * Get recent queries for this tool
     */
    protected function getRecentQueries(int $hours = 24)
    {
        return AiToolQueryLog::forTool(class_basename($this))
            ->recent($hours)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get duplicate queries (same params, multiple calls)
     */
    protected function getDuplicateQueries(int $minutes = 60)
    {
        return AiToolQueryLog::forTool(class_basename($this))
            ->duplicates($minutes)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get failed queries
     */
    protected function getFailedQueries(int $hours = 24)
    {
        return AiToolQueryLog::forTool(class_basename($this))
            ->recent($hours)
            ->failed()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
