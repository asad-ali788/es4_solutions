<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SellingPartnerApi\Seller\SellerConnector;
use App\Models\TempInventorySummaries;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    public function getInventorySummaries(SellerConnector $connector)
    {
        try {
            $granularityType = 'Marketplace';
            $granularityId   = 'ATVPDKIKX0DER';   // US marketplace
            $marketplaceIds   = ['ATVPDKIKX0DER'];   // US marketplace
            $sellerSku       = null;
            $details         = true;
            $startDateTime   = null;
            $sellerSkus      = [];//'AutoCSS-Large-Vcut', 'EcoSteeringDesk', 'ECOSUNSHADE190T'
            $itemApi         = $connector->fbaInventoryV1();
            $response        = $itemApi->getInventorySummaries(
                $granularityType,
                $granularityId,
                $marketplaceIds,
                $details,
                $startDateTime,
                $sellerSkus,
                $sellerSku
            );
            $data            = $response->json();
            $summaries       = $data['payload']['inventorySummaries'] ?? [];
            // return response()->json($data);

            if (empty($summaries)) {
                return;
            }
            $insertData = [];

            foreach ($summaries as $item) {
                $insertData[] = [
                    'asin'                     => $item['asin'],
                    'fn_sku'                   => $item['fnSku'],
                    'seller_sku'               => $item['sellerSku'],
                    'condition'                => $item['condition'],
                    'last_updated_time'        => date('Y-m-d H:i:s', strtotime($item['lastUpdatedTime'])),
                    'product_name'             => $item['productName'],
                    'total_quantity'           => $item['totalQuantity'] ?? 0,
                    'fulfillableQuantity'      => $item['inventoryDetails']['fulfillableQuantity'] ?? 0,
                    'inboundWorkingQuantity'   => $item['inventoryDetails']['inboundWorkingQuantity'] ?? 0,
                    'inboundShippedQuantity'   => $item['inventoryDetails']['inboundShippedQuantity'] ?? 0,
                    'inboundReceivingQuantity' => $item['inventoryDetails']['inboundReceivingQuantity'] ?? 0,
                    'totalReservedQuantity'    => $item['inventoryDetails']['reservedQuantity']['totalReservedQuantity'] ?? 0,
                    'inventoryDetails'         => json_encode($item['inventoryDetails'] ?? []),
                    'stores'                   => json_encode($item['stores'] ?? []),
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ];
            }

            TempInventorySummaries::insert($insertData);
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('SP-API Catalog Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch catalog item: ' . $e->getMessage()
            ], 500);
        }
    }
}
