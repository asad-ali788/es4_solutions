<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;
use App\Models\InboundShipmentSp;
use App\Models\InboundShipmentDetailsSp;
use GuzzleHttp\Exception\ClientException;

class GetInboundShipmentsReport extends Command
{
    protected $signature = 'app:get-inbound-shipments-report';
    protected $description = 'Fetch inbound FBA shipments and shipment items';

    protected const BASE_SLEEP = 5;
    protected const MAX_RETRIES = 5;

    protected const DEFAULT_STATUSES = [
        'WORKING',
        'SHIPPED',
        'IN_TRANSIT',
        'DELIVERED',
        'CHECKED_IN',
        'RECEIVING',
        // 'CLOSED',
    ];

    public function handle()
    {
        Log::channel('spApi')->info('✅ GetInboundShipmentsReport Started.');

        $marketplaces = config('marketplaces.marketplace_ids');

        if (empty($marketplaces)) {
            $this->error("⚠️ No marketplaces configured in config/marketplaces.php");
            return;
        }

        $connector = app(SellerConnector::class);

        foreach ($marketplaces as $country => $marketplaceId) {
            $this->info("\n🌍 Processing shipments for {$country} (MarketplaceId: {$marketplaceId})");

            $this->processShipments($connector, $marketplaceId, $country);

            $this->info("⏳ Sleeping " . self::BASE_SLEEP . " seconds before next marketplace...");
            sleep(self::BASE_SLEEP);
        }
        Log::channel('spApi')->info('✅ GetInboundShipmentsReport Completed.');

        $this->info("\n🎉 Inbound shipment fetching completed for all marketplaces.");
    }

    protected function processShipments(SellerConnector $connector, string $marketplaceId, string $country): void
    {
        $api = $connector->fbaInboundV0();

        // Optional: only fetch new shipments since the last run.
        // In production, you’d store the last successful timestamp in DB or cache.
        $lastUpdatedAfter = null;
        // e.g. $lastUpdatedAfter = now()->subDays(30)->toIso8601String();

        $retryCount = 0;
        $sleepTime = self::BASE_SLEEP;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $response = $api->getShipments(
                    'SHIPMENT',
                    $marketplaceId,
                    self::DEFAULT_STATUSES,
                    null,
                    $lastUpdatedAfter
                );

                $shipments = $response->json('payload.ShipmentData') ?? [];

                if (empty($shipments)) {
                    $this->warn("⚠️ No shipments found for marketplace {$marketplaceId}");
                    break;
                }

                $this->info("Found " . count($shipments) . " shipment(s) in {$country}.");

                foreach ($shipments as $shipmentData) {
                    $shipment = InboundShipmentSp::updateOrCreate(
                        ['shipment_id' => $shipmentData['ShipmentId']],
                        [
                            'add_date'          => now(),
                            'ship_status'       => $shipmentData['ShipmentStatus'] ?? null,
                            'ship_arrival_date' => null,
                        ]
                    );

                    $this->info("✅ Saved shipment: {$shipment->shipment_id}");

                    $this->fetchShipmentItems($shipment->shipment_id, $marketplaceId, $connector);
                    usleep(200_000); // short pause between item calls
                }

                break;

            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $this->error("❌ Failed fetching shipments for {$country}: {$errorMessage}");

                if (
                    str_contains($errorMessage, 'Too Many Requests') ||
                    str_contains($errorMessage, 'QuotaExceeded')
                ) {
                    $retryAfter = $sleepTime;

                    if ($e instanceof ClientException) {
                        $response = $e->getResponse();
                        if ($response && $response->getStatusCode() === 429) {
                            $header = $response->getHeaderLine('Retry-After');
                            if (is_numeric($header)) {
                                $retryAfter = (int)$header;
                            }
                        }
                    }

                    $this->warn("⏳ Throttled. Sleeping {$retryAfter} seconds...");
                    sleep($retryAfter);
                    $sleepTime = min($sleepTime * 2, 120);
                    $retryCount++;
                    continue;
                }

                Log::channel('spApi')->error('SP-API getShipments error', [
                    'marketplace' => $country,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                break; // exit for non-quota errors
            }
        }

        if ($retryCount >= self::MAX_RETRIES) {
            $this->error("🚫 Max retries exceeded for {$country}. Skipping...");
        }
    }

    protected function fetchShipmentItems(string $shipmentId, string $marketplaceId, SellerConnector $connector): void
    {
        $api = $connector->fbaInboundV0();

        $retryCount = 0;
        $sleepTime = self::BASE_SLEEP;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $response = $api->getShipmentItemsByShipmentId(
                    $shipmentId,
                    $marketplaceId
                );

                $items = $response->json('payload.ItemData') ?? [];

                if (empty($items)) {
                    $this->warn("⚠️ No items found for shipment {$shipmentId}");
                    return;
                }

                $this->info("🔹 Found " . count($items) . " item(s) for shipment {$shipmentId}");

                $shipment = InboundShipmentSp::updateOrCreate(
                    ['shipment_id' => $shipmentId],
                    ['add_date' => now()]
                );

                foreach ($items as $item) {
                    InboundShipmentDetailsSp::updateOrCreate(
                        [
                            'ship_id' => $shipment->shipment_id,
                            'sku'     => $item['SellerSKU'],
                        ],
                        [
                            'qty_ship'     => $item['QuantityShipped'] ?? 0,
                            'qty_received' => $item['QuantityReceived'] ?? 0,
                            'add_date'     => now(),
                        ]
                    );
                }

                $this->info("✅ Shipment items saved for shipment {$shipmentId}");
                break;

            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $this->error("❌ Failed fetching items for shipment {$shipmentId}: {$errorMessage}");

                if (
                    str_contains($errorMessage, 'Too Many Requests') ||
                    str_contains($errorMessage, 'QuotaExceeded')
                ) {
                    $retryAfter = $sleepTime;

                    if ($e instanceof ClientException) {
                        $response = $e->getResponse();
                        if ($response && $response->getStatusCode() === 429) {
                            $header = $response->getHeaderLine('Retry-After');
                            if (is_numeric($header)) {
                                $retryAfter = (int)$header;
                            }
                        }
                    }

                    $this->warn("⏳ Throttled. Sleeping {$retryAfter} seconds...");
                    sleep($retryAfter);
                    $sleepTime = min($sleepTime * 2, 120);
                    $retryCount++;
                    continue;
                }

                Log::channel('spApi')->error('SP-API getShipmentItemsByShipmentId error', [
                    'shipmentId' => $shipmentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                break;
            }
        }

        if ($retryCount >= self::MAX_RETRIES) {
            $this->error("🚫 Max retries exceeded for shipment {$shipmentId}. Skipping items fetch...");
        }
    }
}
