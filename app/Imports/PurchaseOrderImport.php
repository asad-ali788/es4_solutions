<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PurchaseOrderImport implements ToCollection, WithHeadingRow
{
    protected $purchaseOrderId;
    protected $excludedRows = [];

    public function __construct($purchaseOrderId)
    {
        $this->purchaseOrderId = $purchaseOrderId;
    }
    public function collection(Collection $rows)
    {
        $insertData = [];

        DB::beginTransaction();
        try {
            $skus = $rows->pluck('sku')->filter()->map(fn($sku) => trim($sku))->unique();

            $products = Product::with(['listings.pricing'])
                ->whereIn('sku', $skus)
                ->get()
                ->keyBy('sku');

            foreach ($rows as $index => $row) {
                $sku = trim($row['sku'] ?? '');
                $qty = (int) ($row['qty'] ?? 0);

                if (empty($sku) || $qty <= 0) {
                    $this->excludedRows[] = [
                        'sku'        => $sku,
                        'qty'        => $qty,
                        'unit_price' => $row['unit_price'] ?? '',
                        'remarks'    => $row['remarks'] ?? '',
                        'reason'     => 'Missing or invalid SKU/quantity',
                    ];
                    continue;
                }

                $product = $products[$sku] ?? null;

                if ($product) {
                    $unit_cost = null;

                    foreach ($product->listings as $listing) {
                        if ($listing->pricing && $listing->pricing->item_price !== null) {
                            $unit_cost = $listing->pricing->item_price;
                            break;
                        }
                    }

                    if ($unit_cost === null) {
                        $this->excludedRows[] = [
                            'sku'        => $sku,
                            'qty'        => $qty,
                            'unit_price' => 'N/A',
                            'remarks'    => $row['remarks'] ?? '',
                            'reason'     => 'No item_price found in pricing',
                        ];
                        continue;
                    }

                    $insertData[] = [
                        'purchase_order_id'  => $this->purchaseOrderId,
                        'product_id'         => $product->id,
                        'quantity_ordered'   => $qty,
                        'quantity_received'  => 0,
                        'unit_price'         => $unit_cost,
                        'status'             => 'pending',
                        'remarks'            => $row['remarks'] ?? null,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                } else {
                    $this->excludedRows[] = [
                        'sku'        => $sku,
                        'qty'        => $qty,
                        'unit_price' => $row['unit_price'] ?? '',
                        'remarks'    => $row['remarks'] ?? '',
                        'reason'     => 'SKU not found in DB',
                    ];
                }
            }

            if (!empty($insertData)) {
                PurchaseOrderItem::insert($insertData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("PurchaseOrderImport failed: " . $e->getMessage(), [
                'purchase_order_id' => $this->purchaseOrderId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }


    public function getExcludedRows(): array
    {
        return $this->excludedRows;
    }
}
