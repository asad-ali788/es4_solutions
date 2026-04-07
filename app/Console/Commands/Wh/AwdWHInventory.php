<?php

namespace App\Console\Commands\Wh;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\ProductAsins;
use App\Models\ProductWhInventory;
use Exception;

class AwdWHInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:awd-wh-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AWD Warehouse Sync from PowerBI';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::info('🔄 Syncing AWD inventory from PowerBI…');
            $this->info('🔄 Syncing AWD inventory from PowerBI…');

            $warehouse = Warehouse::firstOrCreate(
                ['warehouse_name' => 'AWD'],
                [
                    'location' => 'US',
                    'uuid' => (string) Str::uuid(),
                ]
            );

            $rows = DB::connection('powerbi')->table('AWD')->get();
            $total = 0;

            foreach ($rows as $row) {
                $sku = trim((string) ($row->SKU ?? ''));
                if (!$sku) {
                    // Fallback to ASIN if SKU is missing
                    $asin = trim((string) ($row->ASIN ?? ''));
                    if (!$asin) continue;
                    $pa = ProductAsins::where('asin1', $asin)->first();
                    $product = $pa ? $pa->product : null;
                } else {
                    $product = Product::where(DB::raw('BINARY `sku`'), '=', $sku)->first();
                }

                if (!$product)
                    continue;

                $available = (int) ($row->{'Available in AWD (units)'} ?? 0);
                $inbound = (int) ($row->{'Inbound to AWD (units)'} ?? 0);

                if ($available == 0 && $inbound == 0) {
                    continue;
                }

                ProductWhInventory::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'available_quantity' => $available,
                        'quantity' => $inbound, // Inbound units saved as quantity for tracking
                        'updated_at' => now(),
                    ]
                );

                $total++;
            }

            Log::info("✅ Synced {$total} inventory records for AWD from PowerBI.");
            $this->info("✅ Synced {$total} inventory records for AWD from PowerBI.");

        } catch (Exception $e) {
            Log::error('❌ AWD inventory sync failed', [
                'error' => $e->getMessage()
            ]);
            $this->error('❌ AWD inventory sync failed: ' . $e->getMessage());
        }
    }
}
