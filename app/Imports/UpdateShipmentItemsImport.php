<?php

namespace App\Imports;

use App\Models\InboundShipmentItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UpdateShipmentItemsImport implements ToCollection, WithHeadingRow
{
    protected $shipmentId;
    protected $excludedRows = [];

    public function __construct($shipmentId)
    {
        $this->shipmentId = $shipmentId;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $skus          = $rows->pluck('sku')->filter()->map(fn($sku) => trim($sku))->unique();
            $products      = Product::whereIn('sku', $skus)->pluck('id', 'sku');

            $productIds    = $products->values();
            $existingItems = InboundShipmentItem::where('inbound_shipment_id', $this->shipmentId)
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
                    $shipmentItem = $existingItems[$productId] ?? null;

                    if ($shipmentItem) {
                        $shipmentItem->quantity_received = $qty;
                        $shipmentItem->updated_at        = now();
                        if ($shipmentItem->quantity_ordered == $qty) {
                            $shipmentItem->status = 'received';
                        } else {
                            $shipmentItem->status = 'short';
                        }
                        $shipmentItem->save();
                    } else {
                        $this->excludedRows[] = [
                            'sku' => $sku,
                            'received_qty' => $qty,
                        ];
                    }
                } else {
                    $this->excludedRows[] = [
                        'sku' => $sku,
                        'received_qty' => $qty,
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("UpdateShipmentItemsImport failed: " . $e->getMessage(), [
                'shipment_id' => $this->shipmentId,
                'trace'       => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getExcludedRows(): array
    {
        return $this->excludedRows;
    }
}
