<?php

namespace App\Imports;

use App\Models\InboundShipmentItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ShipmentItemsImport implements ToCollection, WithHeadingRow
{
    protected $shipmentId;
    protected $excludedRows = [];

    public function __construct($shipmentId)
    {
        $this->shipmentId = $shipmentId;
    }

    public function collection(Collection $rows)
    {
        $insertData = [];
        DB::beginTransaction();

        try {
            // 1. Collect unique SKUs from sheet
            $skus = $rows->pluck('sku')->filter()->map(fn($sku) => trim($sku))->unique();

            // 2. Preload all products with listings and pricing
            $products = Product::with(['listings.pricing'])
                ->whereIn('sku', $skus)
                ->get()
                ->keyBy('sku'); // Use sku as key for quick lookup

            foreach ($rows as $index => $row) {
                $sku = trim($row['sku'] ?? '');
                $qty = (int) ($row['qty'] ?? 0);

                if (empty($sku) || $qty <= 0) {
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

                    $insertData[] = [
                        'inbound_shipment_id' => $this->shipmentId,
                        'product_id'          => $product->id,
                        'quantity_ordered'    => $qty,
                        'quantity_received'   => 0,
                        'unit_cost'           => $unit_cost,
                        'total_cost'          => $unit_cost !== null ? $unit_cost * $qty : null,
                        'status'              => 'pending',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ];
                } else {
                    $this->excludedRows[] = [
                        'sku' => $sku,
                        'qty' => $qty,
                    ];
                }
            }

            if (!empty($insertData)) {
                InboundShipmentItem::insert($insertData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("ShipmentItemsImport failed: " . $e->getMessage(), [
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
