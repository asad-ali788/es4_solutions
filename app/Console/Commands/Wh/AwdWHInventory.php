<?php

namespace App\Console\Commands\Wh;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductWhInventory;
use Exception;

class AwdWHInventory extends Command
{
    protected $signature = 'app:awd-wh-inventory';
    protected $description = 'AWD Warehouse Sync';

    public function handle(SellerConnector $connector)
    {
        try {
            Log::info('🔄 Syncing AWD available stock…');

            $granularityType = 'Marketplace';
            $granularityId   = 'ATVPDKIKX0DER';
            $marketplaceIds = ['ATVPDKIKX0DER'];

            $warehouse = Warehouse::firstOrCreate(
                ['warehouse_name' => 'AWD'],
                [
                    'location' => 'US',
                    'uuid'     => Str::uuid(),
                ]
            );

            $itemApi   = $connector->fbaInventoryV1();
            $nextToken = null;
            $total     = 0;

            do {
                /** -------------------------------
                 *  Retry wrapper for SP-API call
                 *  ------------------------------- */
                $attempt = 0;
                $maxRetries = 5;
                $delay = 2;

                while (true) {
                    try {
                        $response = $itemApi->getInventorySummaries(
                            $granularityType,
                            $granularityId,
                            $marketplaceIds,
                            true,
                            null,
                            null,
                            null,
                            $nextToken
                        );

                        // success → break retry loop
                        break;
                    } catch (Exception $e) {

                        // Check for 429 specifically
                        if (str_contains($e->getMessage(), '429') && $attempt < $maxRetries) {
                            $attempt++;

                            Log::warning("⚠️ AWD rate limit hit (429). Retry {$attempt}/{$maxRetries} after {$delay}s");

                            sleep($delay);
                            $delay *= 2; // exponential backoff

                            continue;
                        }

                        // Any other error OR retries exhausted
                        throw $e;
                    }
                }

                /** -------------------------------
                 *  Process response
                 *  ------------------------------- */
                $data      = $response->json();
                $summaries = $data['payload']['inventorySummaries'] ?? [];

                foreach ($summaries as $summary) {
                    $sellerSku = $summary['sellerSku'] ?? null;
                    if (!$sellerSku) continue;

                    $product = Product::where('sku', $sellerSku)->first();
                    if (!$product) continue;

                    $inventoryDetails = $summary['inventoryDetails'] ?? [];

                    $fulfillable   = $inventoryDetails['fulfillableQuantity'] ?? 0;
                    $reserved      = $inventoryDetails['reservedQuantity']['totalReservedQuantity'] ?? 0;
                    $unfulfillable = $inventoryDetails['futureSupplyQuantity']['futureSupplyBuyableQuantity'] ?? 0;

                    if ($fulfillable == 0 && $reserved == 0 && $unfulfillable == 0) {
                        continue;
                    }

                    ProductWhInventory::updateOrCreate(
                        [
                            'product_id'   => $product->id,
                            'warehouse_id' => $warehouse->id,
                        ],
                        [
                            'quantity'           => $fulfillable,
                            'reserved_quantity'  => $reserved,
                            'available_quantity' => $unfulfillable,
                            'updated_at'         => now(),
                        ]
                    );

                    $total++;
                }

                $nextToken = $data['pagination']['nextToken'] ?? null;
            } while ($nextToken);

            Log::info("✅ Synced {$total} inventory records for AWD.");
            $this->info("✅ Synced {$total} inventory records for AWD.");
        } catch (Exception $e) {
            Log::error('❌ AWD inventory sync failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
