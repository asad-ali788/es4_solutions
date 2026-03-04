<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductAsin;
use App\Models\ProductAsins;
use App\Models\ProductWhInventory;
use App\Models\Warehouse;
use App\Services\Api\TacticalWarehouseService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SyncTacticalWarehouseInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TacticalWarehouseService $service): void
    {
        $inventoryItems = $service->getInventory();

        if (empty($inventoryItems)) {
            Log::warning('🚫 No inventory returned from Tactical Warehouse API');
            return;
        }

        $warehouse = Warehouse::firstOrCreate(
            ['warehouse_name' => 'Tactical'],
            ['location' => 'US', 'uuid' => Str::uuid()]
        );

        // Build fnsku => product_id map (lowercased)
        $fnskus = [];
        Product::whereNotNull('fnsku')
            ->select('id', 'fnsku')
            ->chunk(500, function ($products) use (&$fnskus) {
                foreach ($products as $p) {
                    $k = strtolower(trim($p->fnsku));
                    if ($k === '') continue;
                    if (isset($fnskus[$k]) && $fnskus[$k] !== $p->id) {
                        Log::warning("Duplicate FNSKU for {$p->fnsku} -> product_ids {$fnskus[$k]} and {$p->id}");
                    }
                    $fnskus[$k] = $p->id;
                }
            });

        // Build asin => product_id map ONLY from product_asins
        $asinMap = [];
        ProductAsins::whereNotNull('asin1')
            ->select('product_id', 'asin1')
            ->chunk(1000, function ($rows) use (&$asinMap) {
                foreach ($rows as $pa) {
                    $k = strtolower(trim($pa->asin1));
                    if ($k === '') continue;
                    if (!isset($asinMap[$k])) {
                        $asinMap[$k] = $pa->product_id;
                    } elseif ($asinMap[$k] !== $pa->product_id) {
                        // Log::warning("Duplicate ASIN across product_asins for asin {$pa->asin1} -> product_ids {$asinMap[$k]} and {$pa->product_id}");
                    }
                }
            });

        foreach ($inventoryItems as $item) {
            $sku = $item['SKU'] ?? null;
            if (!$sku) continue;

            // Normalize and split SKU (split on -, whitespace; lowercase)
            $rawParts = preg_split('/[-\s]+/', $sku);
            $parts = array_filter(array_map(fn($s) => strtolower(trim((string) $s)), $rawParts), fn($s) => $s !== '');

            $productId = null;

            // 1) Try FNSKU match
            foreach ($parts as $part) {
                if (str_starts_with($part, 'x00') && isset($fnskus[$part])) {
                    $productId = $fnskus[$part];
                    break;
                }
            }

            // 2) Try ASIN match (conservative 10-char alnum)
            if (!$productId) {
                foreach ($parts as $part) {
                    if (preg_match('/^[a-z0-9]{10}$/', $part)) {
                        if (isset($asinMap[$part])) {
                            $productId = $asinMap[$part];
                            break;
                        }
                    }
                }
            }

            if (!$productId) {
                // silently skip when no match (no per-item logs as requested)
                continue;
            }

            // perform the inventory update/create (no logging or counters)
            ProductWhInventory::updateOrCreate(
                [
                    'product_id'   => $productId,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'available_quantity' => (int) ($item['UnitCount'] ?? 0),
                ]
            );
        }

        // Minimal final log to mark completion (optional; remove if you want zero logs entirely)
        Log::info('🔄 Tactical warehouse inventory sync complete.');
    }
}
