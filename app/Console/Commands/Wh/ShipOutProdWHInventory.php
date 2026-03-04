<?php

namespace App\Console\Commands\Wh;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Api\WarehouseService;
use App\Models\ProductWhInventory;
use App\Models\ShipOutWarehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShipOutProdWHInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:shiph-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ShipOut Warehouse Sync';

    /**
     * Execute the console command.
     */
    public function handle(WarehouseService $api): int
    {
        $this->info('🔄 Syncing ShipOut available stock…');
        Log::info('🔄 Syncing ShipOut available stock…');

        // 1. Build map of fnsku => product_id
        $productMap = Product::whereNotNull('fnsku')->pluck('id', 'fnsku');

        if ($productMap->isEmpty()) {
            // $this->warn('⚠️ No products found with valid FNSKU.');
            Log::info('⚠️ No products found with valid FNSKU.');
            return self::SUCCESS;
        }

        // 2. Fetch stock from API
        $stocks = $api->stockList();
        if (empty($stocks)) {
            // $this->warn('⚠️ ShipOut returned no stock.');
            Log::info('⚠️ ShipOut returned no stock.');

            return self::SUCCESS;
        }

        // 3. Ensure warehouse exists
        $warehouse = Warehouse::firstOrCreate(
            ['warehouse_name' => 'ShipOut'],
            [
                'location' => 'US',
                'uuid'     => Str::uuid(),
            ]
        );

        // 4. Build inventory rows
        $now        = now();
        $rows       = [];
        $skipped    = [];
        $matched    = 0;

        foreach ($stocks as $row) {
            $omsSku = $row['omsSku'] ?? null;

            if (!$omsSku || !isset($productMap[$omsSku])) {
                $skipped[] = $omsSku;
                continue;
            }

            $productId = $productMap[$omsSku];
            $matched++;

            $rows[] = [
                'product_id'         => $productId,
                'warehouse_id'       => $warehouse->id,
                'available_quantity' => $row['availableQuantity'] ?? 0,
                'updated_at'         => $now,
                'created_at'         => $now,
            ];
        }

        // 5. Insert or update inventory
        if (empty($rows)) {
            // $this->warn('⚠️ No valid inventory rows to sync.');
            Log::info('⚠️ No valid inventory rows to sync.');
            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            ProductWhInventory::updateOrCreate(
                [
                    'product_id'   => $row['product_id'],
                    'warehouse_id' => $row['warehouse_id'],
                ],
                [
                    'available_quantity' => $row['available_quantity'],
                    'updated_at'         => $row['updated_at'],
                ]
            );
        }

        $this->info("🔄 Synced " . count($rows) . " inventory rows.");
        Log::info("🔄 Synced " . count($rows) . " inventory rows.");

        return self::SUCCESS;
    }
}
