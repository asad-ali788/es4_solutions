<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class QdrantClient
{
    private function client()
    {
        return Http::withHeaders([
            'api-key' => (string) config('rag.qdrant.api_key'),
            'Content-Type' => 'application/json',
        ])->timeout(120);
    }

    public function ensureCollection(): void
    {
        $url = rtrim((string) config('rag.qdrant.url'), '/');
        $collection = (string) config('rag.qdrant.collection');
        $dim = (int) config('rag.vector_dim');

        $exists = $this->client()->get("{$url}/collections/{$collection}");
        if ($exists->successful()) {
            return;
        }

        $resp = $this->client()->put("{$url}/collections/{$collection}", [
            'vectors' => [
                'size' => $dim,
                'distance' => 'Cosine',
            ],
        ]);

        if (!$resp->successful()) {
            throw new RuntimeException(
                'Create Qdrant collection failed: ' .
                    $resp->status() . ' ' . $resp->body()
            );
        }
    }

    /**
     * @param array<int, array{id:string|int, vector:array<int,float>, payload:array}>
     */
    public function upsertBatch(array $points): void
    {
        $url = rtrim((string) config('rag.qdrant.url'), '/');
        $collection = (string) config('rag.qdrant.collection');

        $resp = $this->client()->put("{$url}/collections/{$collection}/points", [
            'points' => $points,
        ]);

        if (!$resp->successful()) {
            throw new RuntimeException(
                'Qdrant upsert failed: ' .
                    $resp->status() . ' ' . $resp->body()
            );
        }
    }

    /**
     * @return array<int, array{score:float, payload:array}>
     */
    public function search(array $vector, int $limit = 8): array
    {
        $url = rtrim((string) config('rag.qdrant.url'), '/');
        $collection = (string) config('rag.qdrant.collection');

        $resp = $this->client()->post("{$url}/collections/{$collection}/points/search", [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
        ]);

        if (!$resp->successful()) {
            throw new RuntimeException(
                'Qdrant search failed: ' .
                    $resp->status() . ' ' . $resp->body()
            );
        }

        return $resp->json('result') ?? [];
    }
}
