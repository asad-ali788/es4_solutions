<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InboundShipmentDetailsSp;
use App\Models\InboundShipmentSp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Saloon\Exceptions\Request\RequestException;
use SellingPartnerApi\Seller\SellerConnector;

class SpShipmentController extends Controller
{
    public function getShipments(Request $request, SellerConnector $connector)
    {
        try {
            $api = $connector->fbaInboundV0();

            $statusList = $request->input('status_list');
            $shipmentIdList = $request->input('shipment_id_list');

            if (is_string($statusList)) {
                $statusList = [$statusList];
            }
            if (is_string($shipmentIdList)) {
                $shipmentIdList = [$shipmentIdList];
            }

            $marketplaceId = $request->input('marketplace_id', 'ATVPDKIKX0DER');
            $lastUpdatedAfterInput = $request->input('last_updated_after');
            $lastUpdatedAfter = $lastUpdatedAfterInput ? new \DateTime($lastUpdatedAfterInput) : null;

            $response = $api->getShipments(
                'SHIPMENT',
                $marketplaceId,
                $statusList,
                $shipmentIdList,
                $lastUpdatedAfter
            );
            $shipments = $response->json()['payload']['ShipmentData'] ?? [];


            foreach ($shipments as $shipmentData) {
                InboundShipmentSp::updateOrCreate(
                    ['shipment_id' => $shipmentData['ShipmentId']],
                    [
                        'add_date'           => now(),
                        'ship_status'        => $shipmentData['ShipmentStatus'] ?? null,
                        'ship_arrival_date'  => null,
                    ]
                );
            }

            return response()->json($response->json());
        } catch (\Throwable $e) {
            Log::error('SP-API getShipments error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shipments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function getShipmentItemsById(string $shipmentId, SellerConnector $connector)
    {
        try {
            $api      = $connector->fbaInboundV0();
            $response = $api->getShipmentItemsByShipmentId($shipmentId); // optionally pass marketplace id
            $data     = $response->json();
            $items    = $data['payload']['ItemData'] ?? [];

            if (empty($items)) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'message' => 'No items found for this shipment',
                ]);
            } else {
                $shipment = InboundShipmentSp::updateOrCreate(
                    ['shipment_id' => $shipmentId],
                    ['add_date' => now()]
                );

                foreach ($items as $item) {
                    InboundShipmentDetailsSp::updateOrCreate(
                        [
                            'ship_id' => $shipment->shipment_id,
                            'sku'     => $item['SellerSKU'],
                        ],
                        [
                            'qty_ship'     => $item['QuantityShipped'],
                            'qty_received' => $item['QuantityReceived'],
                            'add_date'     => now(),
                        ]
                    );
                }

                return response()->json($items);
            }
        } catch (\Throwable $e) {
            Log::error('SP-API getShipmentItemsById error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shipment items',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
