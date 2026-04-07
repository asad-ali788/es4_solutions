<?php

namespace App\Console\Commands;

use App\Models\Ai\AiToolQueryLog;
use Illuminate\Console\Command;

class ShowAiToolLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:logs {tool?} {--failed} {--hours=24} {--limit=10} {--hash=}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'View AI tool query logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tool = $this->argument('tool');
        $failed = $this->option('failed');
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');
        $hash = $this->option('hash');

        $query = AiToolQueryLog::recent($hours);

        if ($tool) {
            $query->forTool($tool);
        }

        if ($failed) {
            $query->failed();
        }

        if ($hash) {
            $query->where('query_hash', $hash);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No logs found.');
            return;
        }

        $headers = ['ID', 'Tool', 'Status', 'Results', 'Time (ms)', 'Created'];

        $rows = $logs->map(function ($log) {
            return [
                $log->id,
                $log->tool_name,
                $log->success ? '✓' : '✗',
                $log->result_count,
                round($log->execution_time_ms, 2),
                $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $this->table($headers, $rows);

        // Show detailed info for first log if requested
        if ($this->confirm('Show details for first log?', false)) {
            $log = $logs->first();
            $this->showLogDetails($log);
        }
    }

    /**
     * Show detailed information about a log entry
     */
    private function showLogDetails(AiToolQueryLog $log)
    {
        $this->newLine();
        $this->info("=== Log ID: {$log->id} ===");
        
        $this->line("Tool: <fg=cyan>{$log->tool_name}</>");
        $this->line("Status: " . ($log->success ? '<fg=green>Success</>' : '<fg=red>Failed</>'));
        $this->line("Results: {$log->result_count}");
        $this->line("Execution Time: {$log->execution_time_ms}ms");
        $this->line("Created: {$log->created_at->format('Y-m-d H:i:s')}");
        $this->line("Query Hash: {$log->query_hash}");

        if ($log->error_message) {
            $this->error("Error: {$log->error_message}");
        }

        $this->newLine();
        $this->line('<fg=yellow>Parameters:</> (copy to test reproduction)');
        $this->line(json_encode($log->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($log->aggregates) {
            $this->newLine();
            $this->line('<fg=yellow>Aggregates:</>');
            $this->line(json_encode($log->aggregates, JSON_PRETTY_PRINT));
        }

        if ($log->meta) {
            $this->newLine();
            $this->line('<fg=yellow>Metadata:</>');
            $this->line(json_encode($log->meta, JSON_PRETTY_PRINT));
        }

        // Show other queries with same hash
        $similar = AiToolQueryLog::findSimilar($log->query_hash, 5);
        if ($similar->count() > 1) {
            $this->newLine();
            $this->info('Similar queries (same parameters):');
            foreach ($similar as $s) {
                $status = $s->success ? '✓' : '✗';
                $this->line("  $status ID:{$s->id} Results:{$s->result_count} Time:{$s->execution_time_ms}ms {$s->created_at->format('H:i:s')}");
            }
        }
    }
}
