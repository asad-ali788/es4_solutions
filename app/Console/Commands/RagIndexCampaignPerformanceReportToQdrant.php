<?php

namespace App\Console\Commands;

use App\Models\RagDocument;
use App\Models\RagIndexState;
use App\Services\Rag\QdrantClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class RagIndexCampaignPerformanceReportToQdrant extends Command
{
    protected $signature = 'rag:index:campaign-performance
        {--country= : Filter by country column (e.g. US, IN). Empty = all}
        {--from= : c_date from (YYYY-MM-DD)}
        {--to= : c_date to (YYYY-MM-DD)}
        {--min_cost=10 : Minimum cost filter}
        {--mode=id : id|updated}
        {--chunk=500 : DB chunk size}
        {--batch=64 : Qdrant upsert batch size}
        {--max_rows=0 : Safety cap per run (0 = unlimited)}';

    protected $description = 'Incrementally index amz_ads_campaign_performance_report into Qdrant using Laravel AI SDK embeddings (safe commit order).';

    public function handle(QdrantClient $qdrant): int
    {
        $source = 'amz_ads_campaign_performance_report';

        $country = trim((string) $this->option('country'));
        $from = trim((string) $this->option('from'));
        $to = trim((string) $this->option('to'));
        $minCost = (float) $this->option('min_cost');
        $mode = (string) $this->option('mode');

        $chunkSize = max(50, (int) $this->option('chunk'));
        $batchSize = max(1, (int) $this->option('batch'));
        $maxRows = (int) $this->option('max_rows');

        $qdrant->ensureCollection();

        /** @var RagIndexState $state */
        $state = RagIndexState::query()->firstOrCreate(
            ['source' => $source],
            ['last_id' => 0, 'last_updated_at' => null]
        );

        $query = DB::table($source)
            ->where('cost', '>=', $minCost)
            ->when($country !== '', fn($q) => $q->where('country', $country))
            ->when($from !== '', fn($q) => $q->whereDate('c_date', '>=', $from))
            ->when($to !== '', fn($q) => $q->whereDate('c_date', '<=', $to))
            ->orderBy('id');

        if ($mode === 'updated') {
            if ($state->last_updated_at) {
                $query->where('updated_at', '>', $state->last_updated_at);
            }
        } else {
            $query->where('id', '>', (int) $state->last_id);
        }

        $processed = 0;
        $newLastId = (int) $state->last_id;
        $newLastUpdatedAt = $state->last_updated_at;

        $query->chunkById($chunkSize, function ($rows) use (
            $source,
            $batchSize,
            $maxRows,
            $qdrant,
            &$processed,
            &$newLastId,
            &$newLastUpdatedAt
        ) {
            $points = [];
            $pendingDocRows = []; // write to DB only after Qdrant upsert success
            $pendingMaxId = $newLastId;

            foreach ($rows as $r) {
                if ($maxRows > 0 && $processed >= $maxRows) {
                    break;
                }

                $docKey = "{$source}:{$r->id}";
                $pointId = $this->uuidFromKey($docKey);

                $content = $this->rowToDocText($r);
     
                $hash = hash('sha256', $content);

                $existing = RagDocument::query()
                    ->where('doc_key', $docKey)
                    ->first(['content_hash', 'embedded_at']);

                // ✅ Skip only if it was actually embedded+committed previously
                if ($existing && $existing->embedded_at !== null && $existing->content_hash === $hash) {
                    $processed++;
                    $pendingMaxId = max($pendingMaxId, (int) $r->id);
                    $newLastUpdatedAt = $this->maxDt($newLastUpdatedAt, $r->updated_at ?? null);
                    continue;
                }

                // ✅ Embedding via Laravel AI SDK - Ollama nomic-embed-text
                $vector = Str::of($content)->toEmbeddings();

                $spend = isset($r->cost) ? (float) $r->cost : 0.0;
                $sales1d = isset($r->sales1d) ? (float) $r->sales1d : 0.0;
                $acos1d = ($sales1d > 0) ? round(($spend / $sales1d) * 100, 2) : 0.0;

                $points[] = [
                    'id' => $pointId, // Qdrant-valid UUID 
                    'vector' => $vector,
                    'payload' => [
                        'doc_key' => $docKey,
                        'source' => $source,
                        'row_id' => (int) $r->id,

                        'marketplace' => (string) ($r->country ?? ''),
                        'campaign_id' => (string) ($r->campaign_id ?? ''),
                        'ad_group_id' => (string) ($r->ad_group_id ?? ''),
                        'date' => $r->c_date ? \Illuminate\Support\Carbon::parse($r->c_date)->format('Y-m-d') : '',
                        'status' => (string) ($r->c_status ?? ''),

                        'spend' => $spend,
                        'clicks' => isset($r->clicks) ? (int) $r->clicks : 0,
                        'orders1d' => isset($r->purchases7d) ? (int) $r->purchases7d : 0,
                        'sales1d' => $sales1d,
                        'cpc' => isset($r->costPerClick) ? (float) $r->costPerClick : 0.0,
                        'budget' => isset($r->c_budget) ? (float) $r->c_budget : 0.0,
                        'acos1d' => $acos1d,

                        'content' => $content,
                    ],
                ];

                $pendingDocRows[] = [
                    'doc_key' => $docKey,
                    'source' => $source,
                    'source_row_id' => (int) $r->id,
                    'content_hash' => $hash,
                ];

                $processed++;
                $pendingMaxId = max($pendingMaxId, (int) $r->id);
                $newLastUpdatedAt = $this->maxDt($newLastUpdatedAt, $r->updated_at ?? null);

                // Flush when reaching batch size
                if (count($points) >= $batchSize) {
                    $this->flushBatch($qdrant, $points, $pendingDocRows);
                    $points = [];
                    $pendingDocRows = [];
                }
            }

            // Flush remaining
            if (!empty($points)) {
                $this->flushBatch($qdrant, $points, $pendingDocRows);
            }

            // Only advance cursor after successful flush of this chunk
            $newLastId = max($newLastId, (int) $pendingMaxId);
        });

        // Save cursor after all chunks done
        $state->forceFill([
            'last_id' => $newLastId,
            'last_updated_at' => $newLastUpdatedAt,
        ])->save();

        $this->info("Indexed/checked this run: {$processed}");
        $this->info("Cursor saved: last_id={$newLastId}, last_updated_at=" . ($state->last_updated_at?->toDateTimeString() ?? 'null'));

        return self::SUCCESS;
    }

    /**
     * Upsert to Qdrant first; only on success write rag_documents.
     *
     * @param array<int, array{id:string, vector:array<int,float>, payload:array<string,mixed>}> $points
     * @param array<int, array{doc_key:string, source:string, source_row_id:int, content_hash:string}> $pendingDocRows
     */
    private function flushBatch(QdrantClient $qdrant, array $points, array $pendingDocRows): void
    {
        // ✅ This will throw if Qdrant rejects anything (so we won't mark as embedded)
        $qdrant->upsertBatch($points);

        $now = now();

        foreach ($pendingDocRows as $row) {
            RagDocument::query()->updateOrCreate(
                ['doc_key' => $row['doc_key']],
                [
                    'source' => $row['source'],
                    'source_row_id' => $row['source_row_id'],
                    'content_hash' => $row['content_hash'],
                    'embedded_at' => $now,
                ]
            );
        }
    }

    private function rowToDocText(object $r): string
    {
        $date = (string) ($r->c_date ?? '');
        $country = (string) ($r->country ?? '');
        $campaignId = (string) ($r->campaign_id ?? '');
        $adGroupId = (string) ($r->ad_group_id ?? '');
        $status = (string) ($r->c_status ?? '');

        $spend = isset($r->cost) ? (float) $r->cost : 0.0;
        $sales1d = isset($r->sales1d) ? (float) $r->sales1d : 0.0;
        $orders1d = isset($r->purchases7d) ? (int) $r->purchases7d : 0;
        $clicks = isset($r->clicks) ? (int) $r->clicks : 0;
        $cpc = isset($r->costPerClick) ? (float) $r->costPerClick : 0.0;
        $budget = isset($r->c_budget) ? (float) $r->c_budget : 0.0;
        $acos1d = ($sales1d > 0) ? round(($spend / $sales1d) * 100, 2) : 0.0;

        $formattedDate = $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : $date;

        $lines = [];

        // Header
        $lines[] = "On {$formattedDate}, Campaign {$campaignId} in {$country} marketplace was {$status}.";
        $lines[] = '';

        // Campaign details
        $lines[] = "Campaign {$campaignId}";
        $lines[] = "AdGroup {$adGroupId}";
        $lines[] = "Date " . ($date ? \Illuminate\Support\Carbon::parse($date)->format('Y-m-d') : '');
        $lines[] = "Marketplace {$country}";
        $lines[] = "Status {$status}";
        $lines[] = '';

        // Metrics section
        $lines[] = 'Metrics:';
        $lines[] = 'Spend $' . number_format($spend, 2, '.', '');
        $lines[] = 'Clicks ' . $clicks;
        $lines[] = 'Orders1d ' . $orders1d;
        $lines[] = 'Sales1d $' . number_format($sales1d, 2, '.', '');
        $lines[] = 'CPC $' . number_format($cpc, 2, '.', '');
        $lines[] = 'Budget $' . number_format($budget, 2, '.', '');
        $lines[] = 'ACOS1d ' . number_format($acos1d, 2, '.', '') . '%';

        $text = implode("\n", $lines);

        return mb_substr($text, 0, 1200);
    }

    private function uuidFromKey(string $docKey): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, $docKey)->toString();
    }

    private function maxDt($a, $b)
    {
        if (!$b) return $a;
        $cb = \Illuminate\Support\Carbon::parse($b);
        if (!$a) return $cb;
        return $cb->greaterThan($a) ? $cb : $a;
    }
}
