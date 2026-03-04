<?php

namespace App\Console\Commands;

use App\Models\RagDocument;
use App\Models\RagIndexState;
use App\Services\Rag\QdrantClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RagIndexKeywordReportToQdrant extends Command
{
    protected $signature = 'rag:index:keyword-report
        {--marketplace=US}
        {--min_cost=10}
        {--chunk=500}
        {--batch=64}
        {--max_rows=0}';

    protected $description = 'Incrementally index amz_ads_keyword_performance_report into Qdrant using Laravel AI SDK embeddings.';

    public function handle(QdrantClient $qdrant): int
    {
        $source = 'amz_ads_keyword_performance_report';

        $marketplace = (string) $this->option('marketplace');
        $minCost = (float) $this->option('min_cost');

        $chunkSize = max(50, (int) $this->option('chunk'));
        $batchSize = max(1, (int) $this->option('batch'));
        $maxRows = (int) $this->option('max_rows');

        $qdrant->ensureCollection();

        /** @var RagIndexState $state */
        $state = RagIndexState::query()->firstOrCreate(
            ['source' => $source],
            ['last_id' => 0, 'last_updated_at' => null]
        );

        $processed = 0;
        $newLastId = (int) $state->last_id;

        $query = DB::table($source)
            ->where('marketplace', $marketplace)
            ->where('cost', '>=', $minCost)
            ->where('id', '>', $state->last_id)
            ->orderBy('id');

        $query->chunkById($chunkSize, function ($rows) use (
            $source,
            $marketplace,
            $batchSize,
            $maxRows,
            $qdrant,
            &$processed,
            &$newLastId
        ) {
            $points = [];

            foreach ($rows as $r) {
                if ($maxRows > 0 && $processed >= $maxRows) {
                    break;
                }

                $docKey = "{$source}:{$r->id}";
                $content = $this->rowToDocText($r);
                $hash = hash('sha256', $content);

                $existingHash = RagDocument::query()
                    ->where('doc_key', $docKey)
                    ->value('content_hash');

                if ($existingHash === $hash) {
                    $processed++;
                    $newLastId = max($newLastId, (int) $r->id);
                    continue;
                }

                // ✅ Embedding using Laravel AI SDK (same as your test)
                $vector = Str::of($content)->toEmbeddings();

                RagDocument::query()->updateOrCreate(
                    ['doc_key' => $docKey],
                    [
                        'source' => $source,
                        'source_row_id' => (int) $r->id,
                        'content_hash' => $hash,
                        'embedded_at' => now(),
                    ]
                );

                $points[] = [
                    'id' => $docKey,     // stable id -> allows updates
                    'vector' => $vector, // 768 floats
                    'payload' => [
                        'doc_key' => $docKey,
                        'source' => $source,
                        'row_id' => (int) $r->id,
                        'marketplace' => (string) ($r->marketplace ?? ''),
                        'report_date' => (string) ($r->report_date ?? ''),
                        'campaign_id' => (string) ($r->campaign_id ?? ''),
                        'ad_group_id' => (string) ($r->ad_group_id ?? ''),
                        'keyword_id' => (string) ($r->keyword_id ?? ''),
                        'match_type' => (string) ($r->match_type ?? ''),
                        // Keep payload small in free tier; store content if you want quick display:
                        'content' => $content,
                    ],
                ];

                if (count($points) >= $batchSize) {
                    $qdrant->upsertBatch($points);
                    $points = [];
                }

                $processed++;
                $newLastId = max($newLastId, (int) $r->id);
            }

            if (!empty($points)) {
                $qdrant->upsertBatch($points);
            }
        });

        $state->forceFill([
            'last_id' => $newLastId,
        ])->save();

        $this->info("Indexed/checked: {$processed} rows. Cursor last_id={$newLastId}");

        return self::SUCCESS;
    }

    private function rowToDocText(object $r): string
    {
        // Keep consistent formatting and reasonable length
        $parts = array_filter([
            (string) ($r->report_date ?? ''),
            'Marketplace ' . (string) ($r->marketplace ?? ''),
            'Campaign ' . (string) ($r->campaign_id ?? ''),
            'AdGroup ' . (string) ($r->ad_group_id ?? ''),
            'Keyword ' . (string) ($r->keyword_text ?? $r->keyword ?? ''),
            'Match ' . (string) ($r->match_type ?? ''),
            isset($r->impressions) ? 'Impr ' . (int) $r->impressions : null,
            isset($r->clicks) ? 'Clicks ' . (int) $r->clicks : null,
            isset($r->cost) ? 'Cost ' . number_format((float) $r->cost, 2, '.', '') : null,
            isset($r->sales) ? 'Sales ' . number_format((float) $r->sales, 2, '.', '') : null,
            isset($r->orders) ? 'Orders ' . (int) $r->orders : null,
            isset($r->acos) ? 'ACOS ' . number_format((float) $r->acos, 2, '.', '') . '%' : null,
        ]);

        return mb_substr(implode(' | ', $parts), 0, 1200);
    }
}
