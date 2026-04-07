<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductWhInventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehouseInventoryImport implements ToCollection, WithHeadingRow
{
    protected $warehouseId;
    protected $excludedRows = [];

    public function __construct($warehouseId)
    {
        $this->warehouseId = $warehouseId;
    }

    public function collection(Collection $rows)
    {
        $insertData = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $sku = trim($row['sku'] ?? '');
                $qty = (int) ($row['qty'] ?? 0);

                if (empty($sku) || $qty <= 0) {
                    continue;
                }

                $product = Product::with('listings.pricing')
                    ->where('sku', $sku)
                    ->select('id')
                    ->first();

                if ($product) {
                    $insertData[] = [
                        'product_id'        => $product->id,
                        'warehouse_id'      => $this->warehouseId,
                        'quantity'          => $qty,
                        'reserved_quantity' => 0,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                } else {
                    $this->excludedRows[] = [
                        'sku' => $sku,
                        'qty' => $qty,
                        'row' => $index + 2
                    ];
                }
            }

            if (!empty($insertData)) {
                ProductWhInventory::insert($insertData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('WarehouseInventoryImport Failed: ' . $e->getMessage(), [
                'warehouse_id' => $this->warehouseId,
                'trace'        => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getExcludedRows(): array
    {
        return $this->excludedRows;
    }
}
