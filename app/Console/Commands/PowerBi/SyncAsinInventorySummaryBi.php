<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncAsinInventorySummaryBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-asin-inventory-summary-bi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate inventory data from 10 PowerBI sources into asin_inventory_summary_bi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ASIN Inventory Summary Sync...');
        $now = now();
        $inventory = [];

        // Helper to aggregate data
        $aggregate = function ($connection, $table, $asinCol, $quantityCol, $targetField) use (&$inventory) {
            $this->info("Fetching data from {$table}...");
            try {
                $rows = DB::connection($connection)
                    ->table($table)
                    ->select($asinCol, DB::raw("SUM(`$quantityCol`) as total"))
                    ->groupBy($asinCol)
                    ->get();

                foreach ($rows as $row) {
                    $asin = trim((string)$row->$asinCol);
                    if (!$asin) continue;

                    if (!isset($inventory[$asin])) {
                        $inventory[$asin] = $this->initAsinData($asin);
                    }
                    $inventory[$asin][$targetField] = (int)$row->total;
                }
            } catch (\Exception $e) {
                $this->error("Error syncing from {$table}: " . $e->getMessage());
            }
        };

        // 1 & 2: FBA
        $this->info("Processing FBA Available and Inbound...");
        try {
            $fbaRows = DB::connection('powerbi')
                ->table('FBA')
                ->select(
                    'asin',
                    DB::raw("SUM(`afn-fulfillable-quantity`) as fba_available"),
                    DB::raw("SUM(`afn-inbound-working-quantity`) as fba_inbound_working"),
                    DB::raw("SUM(`afn-inbound-shipped-quantity`) as fba_inbound_shipped"),
                    DB::raw("SUM(`afn-inbound-receiving-quantity`) as fba_inbound_receiving")
                )
                ->groupBy('asin') // FBA uses lowercase 'asin'
                ->get();

            foreach ($fbaRows as $row) {
                $asin = trim((string)$row->asin);
                if (!$asin) continue;
                if (!isset($inventory[$asin])) $inventory[$asin] = $this->initAsinData($asin);
                $inventory[$asin]['fba_available'] = (int)$row->fba_available;
                $inventory[$asin]['fba_inbound_working'] = (int)$row->fba_inbound_working;
                $inventory[$asin]['fba_inbound_shipped'] = (int)$row->fba_inbound_shipped;
                $inventory[$asin]['fba_inbound_receiving'] = (int)$row->fba_inbound_receiving;
            }
        } catch (\Exception $e) {
            $this->error("Error syncing from FBA: " . $e->getMessage());
        }

        // 3: FC Reserved
        $aggregate('powerbi', 'FC', 'ASIN', 'reserved_fc-transfers', 'fc_reserved');

        // 4 & 5: AWD
        $this->info("Processing AWD Available and Inbound...");
        try {
            $awdRows = DB::connection('powerbi')
                ->table('AWD')
                ->select('ASIN', DB::raw("SUM(`Available in AWD (units)`) as awd_available"), DB::raw("SUM(`Inbound to AWD (units)`) as awd_inbound"))
                ->groupBy('ASIN') // AWD uses uppercase 'ASIN'
                ->get();

            foreach ($awdRows as $row) {
                $asin = trim((string)$row->ASIN);
                if (!$asin) continue;
                if (!isset($inventory[$asin])) $inventory[$asin] = $this->initAsinData($asin);
                $inventory[$asin]['awd_available'] = (int)$row->awd_available;
                $inventory[$asin]['awd_inbound'] = (int)$row->awd_inbound;
            }
        } catch (\Exception $e) {
            $this->error("Error syncing from AWD: " . $e->getMessage());
        }

        // 6: APA Warehouse
        $aggregate('powerbi', 'apa', 'ASIN', 'Available', 'apa_warehouse_available');

        // 9: Flex Warehouse
        $aggregate('powerbi', 'Flex ware house', 'ASIN', 'Available', 'flex_warehouse_available');

        // Local Databases (Shipout & Tactical)
        $getLocalWarehouseInventory = function ($warehouseId, $targetField) use (&$inventory) {
            $this->info("Fetching data for local warehouse {$warehouseId}...");
            try {
                $rows = DB::table('product_wh_inventory as wh')
                    ->join('products as p', 'wh.product_id', '=', 'p.id')
                    ->join('product_asins as pa', 'p.id', '=', 'pa.product_id')
                    ->where('wh.warehouse_id', $warehouseId)
                    ->select('pa.asin1 as asin', DB::raw('SUM(wh.available_quantity) as total'))
                    ->groupBy('pa.asin1')
                    ->get();

                foreach ($rows as $row) {
                    $asin = trim((string)$row->asin);
                    if (!$asin) continue;

                    if (!isset($inventory[$asin])) {
                        $inventory[$asin] = $this->initAsinData($asin);
                    }
                    $inventory[$asin][$targetField] = (int)$row->total;
                }
            } catch (\Exception $e) {
                $this->error("Error syncing from local warehouse {$warehouseId}: " . $e->getMessage());
            }
        };

        // 10: Shipout Warehouse
        $getLocalWarehouseInventory(1, 'shipout_warehouse_inventory');

        // 11: Tactical Warehouse
        $getLocalWarehouseInventory(2, 'tactical_warehouse_inventory');

        $this->info("Total ASINs collected: " . count($inventory));

        if (empty($inventory)) {
            $this->warn("No data collected. Skipping upsert.");
            return 0;
        }

        // Perform UPSERT in batches
        $chunks = array_chunk(array_values($inventory), 500);
        foreach ($chunks as $chunk) {
            foreach ($chunk as &$item) {
                $item['last_synced_at'] = $now;
                $item['updated_at'] = $now;
                $item['created_at'] = $now;
            }
            DB::table('asin_inventory_summary_bi')->upsert(
                $chunk,
                ['asin'],
                [
                    'fba_available',
                    'fba_inbound_working',
                    'fba_inbound_shipped',
                    'fba_inbound_receiving',
                    'fc_reserved',
                    'awd_available',
                    'awd_inbound',
                    'apa_warehouse_available',
                    'flex_warehouse_available',
                    'shipout_warehouse_inventory',
                    'tactical_warehouse_inventory',
                    'last_synced_at',
                    'updated_at',
                ]
            );
        }

        $this->info('Sync complete.');
        return 0;
    }

    private function initAsinData($asin)
    {
        return [
            'asin' => $asin,
            'fba_available' => 0,
            'fba_inbound_working' => 0,
            'fba_inbound_shipped' => 0,
            'fba_inbound_receiving' => 0,
            'fc_reserved' => 0,
            'awd_available' => 0,
            'awd_inbound' => 0,
            'apa_warehouse_available' => 0,
            'flex_warehouse_available' => 0,
            'shipout_warehouse_inventory' => 0,
            'tactical_warehouse_inventory' => 0,
        ];
    }
}
