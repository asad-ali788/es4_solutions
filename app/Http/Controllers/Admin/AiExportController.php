<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\AiQueryResultExport;
use Illuminate\Http\Request;
use App\Models\Ai\AiToolQueryLog;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class AiExportController extends Controller
{
    /**
     * Download the AI query result (Excel) via trace_id or session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Request $request)
    {
        $traceId = $request->query('trace_id');
        $sql = null;
        $connection = config('database.default');
        $bindings = [];
        $toolName = 'AI_Query';

        if ($traceId) {
            $log = AiToolQueryLog::where('trace_id', $traceId)->first();
            if ($log) {
                $params = $log->parameters ?? [];
                // Prefer export_sql if the AI generated it, otherwise fallback to the chat sql.
                $sql = $params['export_sql'] ?? $params['sql'] ?? null;
                $bindings = $params['bindings'] ?? [];
                $toolName = $log->tool_name;
            }
        }

        // Fallback to session if no trace_id or if log was not found
        if (!$sql) {
            $sessionData = session('ai_last_query_result');
            if ($sessionData && !empty($sessionData['sql'])) {
                $sql = $sessionData['sql'];
                $connection = $sessionData['connection'] ?? $connection;
                $bindings = $sessionData['bindings'] ?? $bindings;
                $toolName = class_basename($sessionData['tool'] ?? $toolName);
            }
        }

        if (!$sql) {
            return back()->with('error', 'No recent AI query found to export.');
        }

        // Clean up the SQL (remove trailing semicolons which break subquery wrapping)
        $sql = rtrim(trim($sql), ';');

        $timestamp = now()->format('Y-m-d_His');

        try {
            // High limit for exports
            $exportLimit = 10000;
            $limitedSql = "select * from ({$sql}) as ai_export_rows limit {$exportLimit}";

            $rows = \Illuminate\Support\Facades\DB::connection($connection)->select($limitedSql, $bindings);

            if (empty($rows)) {
                return back()->with('error', 'The query returned no data for export.');
            }

            $items = array_map(static fn($row) => (array) $row, $rows);
            $filename = Str::slug($toolName) . "_export_{$timestamp}.xlsx";

            return Excel::download(new AiQueryResultExport($items), $filename);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("AI Export Failed: " . $e->getMessage());
            return back()->with('error', 'Something went wrong! This response cannot be exported.');
        }
    }
}
