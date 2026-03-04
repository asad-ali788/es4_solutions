<?php

namespace App\Console\Commands\Wh;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Warehouse;
use App\Models\ShipOutWarehouse;
use App\Services\Api\WarehouseService;
use Illuminate\Support\Str;

class ShipOutQueryList extends Command
{
    protected $signature   = 'app:query-list';
    protected $description = 'ShipOut Warehouse - Update new Sku to Product Warehouse';

    public function handle(WarehouseService $api)
    {
        $this->info('🔄 Starting ShipOut product sync…');
        ShipOutWarehouse::where('status', 1)->update(['status' => 0]);

        // api call to service
        $items = $api->queryList();
        $total = count($items);

        if ($total === 0) {
            $this->warn('ShipOut returned zero products — nothing to do.');
            return self::SUCCESS;
        }
        // if the ShipOut Warehout not exist it will create
        $warehouse = Warehouse::firstOrCreate(
            ['warehouse_name' => 'ShipOut'],
            [
                'location' => 'US',
                'uuid'     => Str::uuid(),
            ]
        );

        $existing = ShipOutWarehouse::pluck('warehouse_sku')->flip();

        $rows = [];
        $now  = now();
        // check for new sku 
        foreach ($items as $item) {
            $sku = $item['omsSku'] ?? null;
            if (!$sku || isset($existing[$sku])) {
                continue;
            }

            $rows[] = [
                'warehouse_sku' => $sku,
                'amazon_sku'    => $item['skuNameEN'] ?? null,
                'asin'          => $item['asin'] ?? null,
                'warehouse_id'  => $warehouse->id,
                'status'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        // chunk insert to save time 
        foreach (array_chunk($rows, 500) as $chunk) {
            ShipOutWarehouse::insert($chunk);
        }

        $inserted = count($rows);
        $this->info("✅ ShipOut Synced {$total} products; inserted {$inserted} new SKUs.");
        Log::info("📃 ShipOutQueryList — total: {$total}, new: {$inserted}");

        return self::SUCCESS;
    }
}
