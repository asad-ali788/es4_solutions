<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\ProductPricingService;
use SellingPartnerApi\Seller\SellerConnector;
use Throwable;

class ProcessProductPricing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $marketplaceId;
    protected string $country;
    protected array $asins;

    // SP-API ProductPricing allows up to 20 ASINs per request
    private const API_BATCH_SIZE = 20;

    // 0.5 RPS → 1 request/2s. Add safety margin: ~2.3s per call.
    private const REQUEST_INTERVAL_MICROS = 2_300_000;

    // Number of times this job may retry
    public $tries = 6;

    public function backoff(): array
    {
        return [10, 30, 60, 120, 300, 600];
    }

    public function __construct(string $marketplaceId, string $country, array $asins)
    {
        $this->marketplaceId = $marketplaceId;
        $this->country = $country;
        $this->asins = $asins;
    }

    public function handle(ProductPricingService $pricingService): void
    {
        $itemType      = 'Asin';
        $itemCondition = 'New';
        $offerType     = 'B2C';

        $api = app(SellerConnector::class)->productPricingV0();
        $totalAsins = count($this->asins);

        $this->cli("🚀 Starting pricing job for {$this->country}: {$totalAsins} ASINs");

        foreach (array_chunk($this->asins, self::API_BATCH_SIZE) as $batchNumber => $asinsBatch) {
            $batchLabel = $batchNumber + 1;
            $this->cli("➡️  [{$this->country}] Batch #{$batchLabel} with " . count($asinsBatch) . " ASINs");

            try {
                $response = $api->getPricing(
                    $this->marketplaceId,
                    $itemType,
                    $asinsBatch,
                    null,
                    $itemCondition,
                    $offerType
                );

                // Adjust based on actual SellerConnector response type
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
                    $this->cli("⚠️  [{$this->country}] No pricing data for batch #{$batchLabel}");
                } else {
                    $pricingService->savePricingData($payload);
                    // $this->cli("📦 Payload: " . json_encode($payload));
                    $this->cli("✅ [{$this->country}] Saved pricing for batch #{$batchLabel}");
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
                    throw $e; // triggers retry with backoff()
                }

                throw $e;
            }
        }

        $this->cli("🏁 Completed pricing fetch for {$this->country} chunk (" . count($this->asins) . " ASINs)");
    }

    /**
     * Simple helper to print to CLI when running in console
     * (and also log to default logger).
     */
    protected function cli(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
        // logger()->info($message);
    }
}
