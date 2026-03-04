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

class SpApiController extends Controller
{
    /**
     * Fetch Marketplace Participations
     */
    public function index(SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->sellersV1();
            $result = $api->getMarketplaceParticipations();

            return response()->json($result->json());
        } catch (RequestException $e) {
            $response = $e->getResponse();
            return response()->json($response?->json(), $e->getStatus());
        }
    }

    public function getListingItem(Request $request, SellerConnector $connector)
    {
        try {
            // Get SKU and Marketplace ID from request
            $sku           = $request->input('sku');
            $marketplaceId = $request->input('marketplace_id', 'ATVPDKIKX0DER');
            $sellerId      = "A2K3H4G5EXAMPLE"; // OR use a configured sellerId, e.g., from .env
            // $sellerId      = $connector->getSellerId(); // OR use a configured sellerId, e.g., from .env

            if (!$sku) {
                return response()->json([
                    'success' => false,
                    'message' => 'SKU is required',
                ], 400);
            }

            // Call SP-API getListingsItem
            $api      = $connector->listingsItemsV20210801();
            $response = $api->getListingsItem($sellerId, $sku, [$marketplaceId]);

            return response()->json([
                'success' => true,
                'data'    => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SP-API getListingsItem error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listing item',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function searchListingsItems(Request $request, SellerConnector $connector)
    {
        try {
            // Inputs
            $marketplaceId = $request->input('marketplace_id', 'ATVPDKIKX0DER');
            $sku           = $request->input('sku');

            // Must be set in .env
            $sellerId = "A2K3H4G5EXAMPLE";
            // if (!$sellerId) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Missing SP_API_SELLER_ID in .env',
            //     ], 400);
            // }

            $api = $connector->listingsItemsV20210801();

            // If SKU is passed, set identifiers
            $identifiers     = $sku ? [$sku] : null;
            $identifiersType = $sku ? 'SKU' : null;

            $response = $api->searchListingsItems(
                $sellerId,
                [$marketplaceId],
                null,              // issueLocale
                null,              // includedData
                $identifiers,
                $identifiersType,
                null,              // variationParentSku
                null,              // packageHierarchySku
                null,              // createdAfter
                null,              // createdBefore
                null,              // lastUpdatedAfter
                null,              // lastUpdatedBefore
                null,              // withIssueSeverity
                null,              // withStatus
                null,              // withoutStatus
                null,              // sortBy
                null,              // sortOrder
                null,              // pageSize
                null               // pageToken
            );

            return response()->json([
                'success' => true,
                'data'    => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SP-API searchListingsItems error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error searching listings',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getMarketplaceParticipations(SellerConnector $connector)
    {
        try {
            $api      = $connector->sellersV1();
            $response = $api->getMarketplaceParticipations();

            return response()->json([
                'success' => true,
                'data'    => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SP-API getMarketplaceParticipations error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch marketplace participations',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
