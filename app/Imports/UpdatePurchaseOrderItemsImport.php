<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UpdatePurchaseOrderItemsImport implements ToCollection, WithHeadingRow
{
    protected $purchaseOrderId;
    protected $excludedRows = [];

    public function __construct($purchaseOrderId)
    {
        $this->purchaseOrderId = $purchaseOrderId;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $skus     = $rows->pluck('sku')->filter()->map(fn($sku) => trim($sku))->unique();
            $products = Product::whereIn('sku', $skus)->pluck('id', 'sku');

            $productIds = $products->values();
            $existingItems = PurchaseOrderItem::where('purchase_order_id', $this->purchaseOrderId)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            foreach ($rows as $index => $row) {
                $sku = trim($row['sku'] ?? '');
                $qty = (int) ($row['received_qty'] ?? 0);

                if (empty($sku) || $qty <= 0) {
                    continue;
                }

                $productId = $products[$sku] ?? null;

                if ($productId) {
                    $poItem = $existingItems[$productId] ?? null;

                    if ($poItem) {
                        $poItem->quantity_received = $qty;
                        $poItem->updated_at        = now();
                        $poItem->status            = $poItem->quantity_ordered == $qty ? 'received' : 'short';
                        $poItem->save();
                    } else {
                        $this->excludedRows[] = [
                            'sku'          => $sku,
                            'received_qty' => $qty,
                            'reason'       => 'Item not found in purchase order',
                        ];
                    }
                } else {
                    $this->excludedRows[] = [
                        'sku'          => $sku,
                        'received_qty' => $qty,
                        'reason'       => 'SKU not found in product table',
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("UpdatePurchaseOrderItemsImport failed: " . $e->getMessage(), [
                'purchase_order_id' => $this->purchaseOrderId,
                'trace'             => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getExcludedRows(): array
    {
        return $this->excludedRows;
    }
}
