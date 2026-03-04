<?php

namespace App\Jobs;

use App\Models\ProductAsins;
use App\Services\CompetitivePricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;
use Throwable;

class ProcessCompetitivePricing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $marketplaceId;
    protected string $country;
    protected array $asins;

    // SP-API ProductPricing allows up to 20 ASINs per request
    private const API_BATCH_SIZE = 20;

    // 0.5 RPS -> 1 request / 2s; safety margin -> 2.3s
    private const REQUEST_INTERVAL_MICROS = 2_300_000;

    public $tries = 6;

    public function backoff(): array
    {
        return [10, 30, 60, 120, 300, 600];
    }

    public function __construct(string $marketplaceId, string $country, array $asins)
    {
        $this->marketplaceId = $marketplaceId;
        $this->country       = $country;
        $this->asins         = $asins;
    }

    public function handle(CompetitivePricingService $pricingService): void
    {
        $itemType  = 'Asin';
        $connector = app(SellerConnector::class);
        $api       = $connector->productPricingV0();

        $totalAsins = count($this->asins);
        $this->cli("🚀 [{$this->country}] ProcessCompetitivePricing started with {$totalAsins} ASINs");

        // Build ASIN -> product_ids map ONCE per job for its chunk
        $asinToProductIds = $this->buildAsinToProductMap($this->asins);

        foreach (array_chunk($this->asins, self::API_BATCH_SIZE) as $batchNumber => $asinsBatch) {
            $batchLabel = $batchNumber + 1;
            $this->cli("➡️  [{$this->country}] Batch #{$batchLabel} with " . count($asinsBatch) . " ASINs");

            try {
                $response = $api->getCompetitivePricing(
                    $this->marketplaceId,
                    $itemType,
                    $asinsBatch
                );

                // Adjust for actual response type
                if (is_object($response) && method_exists($response, 'json')) {
                    $payload = $response->json('payload');
                } elseif (is_array($response)) {
                    $payload = $response['payload'] ?? [];
                } elseif (is_object($response)) {
                    $payload = $response->payload
                        ?? (method_exists($response, 'getPayload') ? $response->getPayload() : []);
                } else {
                    $payload = [];
                }

                if (empty($payload)) {
                    $this->cli("⚠️  [{$this->country}] No competitive pricing data for batch #{$batchLabel}");
                } else {
                    // Slice mapping for just this batch
                    $batchMap = $this->filterAsinMapForBatch($asinToProductIds, $asinsBatch);

                    $pricingService->saveCompetitivePricingRankingData($payload, $batchMap);

                    $this->cli("✅ [{$this->country}] Saved competitive pricing for batch #{$batchLabel}");
                }

                // Respect 0.5 RPS
                usleep(self::REQUEST_INTERVAL_MICROS);
            } catch (Throwable $e) {
                $message = $e->getMessage();

                $this->cli("❌ [{$this->country}] Error on batch #{$batchLabel}: {$message}");

                if (
                    str_contains($message, 'Too Many Requests') ||
                    str_contains($message, 'QuotaExceeded') ||
                    str_contains(strtolower($message), 'throttl')
                ) {
                    // Let Laravel retry with backoff()
                    throw $e;
                }

                throw $e;
            }
        }

        $this->cli("🏁 [{$this->country}] ProcessCompetitivePricing completed for chunk (" . count($this->asins) . " ASINs)");
    }

    protected function buildAsinToProductMap(array $asins): array
    {
        $map = [];

        ProductAsins::where(function ($query) use ($asins) {
            $query->whereIn('asin1', $asins)
                ->orWhereIn('asin2', $asins)
                ->orWhereIn('asin3', $asins);
        })
            ->get()
            ->each(function ($product) use (&$map, $asins) {
                foreach (['asin1', 'asin2', 'asin3'] as $field) {
                    $asin = $product->$field;
                    if (!empty($asin) && in_array($asin, $asins, true)) {
                        $map[$asin][] = $product->product_id;
                    }
                }
            });

        return $map;
    }

    protected function filterAsinMapForBatch(array $map, array $batchAsins): array
    {
        $batchMap = [];
        foreach ($batchAsins as $asin) {
            if (isset($map[$asin])) {
                $batchMap[$asin] = $map[$asin];
            }
        }
        return $batchMap;
    }

    /**
     * Print to CLI when running in console and also log.
     */
    protected function cli(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
        // Log::channel('spApi')->info($message);
    }
}
