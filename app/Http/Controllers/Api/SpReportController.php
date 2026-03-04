<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AmzReportsLog;
use App\Models\FbaInventoryUsa;
use App\Models\Product;
use App\Models\ProductAsins;
use App\Models\ProductListing;
use App\Models\TempAfnInventory;
use App\Models\TempProducts;
use App\Models\TempReservedInventory;
use App\Models\TempSalesOrder;
use App\Services\AmazonReportParser;
use App\Services\DailyReportCreateService;
use Illuminate\Http\JsonResponse;
use SellingPartnerApi\Seller\SellerConnector;
use Illuminate\Support\Facades\Storage;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\DailySales;

class SpReportController extends Controller
{
    // Returns report details for the reports that match the filters that you specify.

    public function getReports(SellerConnector $connector)
    {
        try {
            $api = $connector->reportsV20210630();
            $marketplaceId = config('marketplaces.marketplace_ids');
            $response = $api->getReports(
                reportTypes: ['GET_MERCHANT_LISTINGS_ALL_DATA'],
                marketplaceIds: $marketplaceId,
                pageSize: 10
            );

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Returns report details (including the reportDocumentId, if available) for the report that you specify.

    public function getReport(SellerConnector $connector, $reportId)
    {
        try {
            $api = $connector->reportsV20210630();
            $response = $api->getReport($reportId)->json();
            return response()->json($response);
        } catch (\Throwable $e) {
            return response()->json(['error'   => true, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Creates a report by specifying the report type, marketplaces, 
     * and any optional parameters to include. 
     * If the request is successful, the response will include a reportId value.
     */

    public function createReport(Request $request, SellerConnector $connector): JsonResponse
    {
        try {
            $api            = $connector->reportsV20210630();
            $reportType     = $request->input('reportType');
            $marketplaceIds = ['ATVPDKIKX0DER'];

            if (!$reportType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report Type is not given',
                ]);
            }
            // List of report types that do NOT require a date range
            $reportTypesWithoutDate = [
                'GET_AFN_INVENTORY_DATA',
                'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA',
            ];

            $startDate  = null;
            $endDate    = null;

            // Add reportOptions if needed
            if (!in_array($reportType, $reportTypesWithoutDate)) {
                $startDate = now()->subDays(7)->utc();
                $endDate   = now()->utc();

                $reportSpec = new CreateReportSpecification(
                    reportType: $reportType,
                    marketplaceIds: $marketplaceIds,
                    dataStartTime: $startDate,
                    dataEndTime: $endDate
                );
            } else {
                $reportSpec = new CreateReportSpecification($reportType, $marketplaceIds);
            }

            $response = $api->createReport($reportSpec)->json();

            // Log to DB
            AmzReportsLog::create([
                'report_type'     => $reportType,
                'report_id'       => $response['reportId'],
                'report_status'   => $response['processingStatus'] ?? 'IN_PROGRESS',
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'marketplace_ids' => $marketplaceIds,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Report request submitted successfully.',
                'report_id'  => $response['reportId'] ?? null,
                'status'     => $response['processingStatus'] ?? 'IN_PROGRESS',
            ]);
        } catch (\Exception $e) {
            Log::error('SP-API Report Creation Failed: ' . $e->getMessage());

            return response()->json([
                'error'   => true,
                'message' => 'Failed to create report.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrieves a report document from Amazon SP-API using the provided report ID 
     * and saves the parsed records into the database.
     */

    public function getReportDocument(SellerConnector $connector, string $reportId, string $country): JsonResponse
    {
        try {
            $api    = $connector->reportsV20210630();
            $report = $api->getReport($reportId)->json();

            if (($report['processingStatus'] ?? '') !== 'DONE') {
                return response()->json([
                    'error' => true,
                    'message' => 'Report is not ready yet. Status: ' . ($report['processingStatus'] ?? 'unknown'),
                ], 202);
            }

            $documentId = $report['reportDocumentId'] ?? null;

            if (!$documentId) {
                return response()->json([
                    'error' => true,
                    'message' => 'Report document ID not found.',
                ], 404);
            }

            $reportType = $report['reportType'] ?? 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
            // echo $reportType; die;
            $document = $api->getReportDocument($documentId, $reportType)->json();

            $url = $document['url'] ?? null;
            $compression = $document['compressionAlgorithm'] ?? null;

            if (!$url) {
                return response()->json([
                    'error' => true,
                    'message' => 'Signed URL not found in report document.',
                ], 500);
            }

            $reportContent = file_get_contents($url);
            if ($compression === 'GZIP') {
                $reportContent = gzdecode($reportContent);
            }

            if ($reportContent === false) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to download or decode the report content.',
                ], 500);
            }
            $today       = now('UTC')->toDateString();         // e.g., '2025-06-22'
            $filename    = "{$today}_report_{$reportId}.txt";
            $storagePath = "api/reports/{$filename}";

            Storage::disk('public')->put($storagePath, $reportContent);
            if ($report['reportType'] == 'GET_MERCHANT_LISTINGS_ALL_DATA') {
                $this->tempProducts($reportId, $country);
            } elseif ($report['reportType'] == 'GET_RESERVED_INVENTORY_DATA') {
                $this->reservedInventory($reportId);
            } elseif ($report['reportType'] == 'GET_AFN_INVENTORY_DATA') {
                $this->afnInventory($reportId);
            } elseif ($report['reportType'] == 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL') {
                $this->salesOrder($reportId);
            } elseif ($report['reportType'] == 'GET_FBA_MYI_ALL_INVENTORY_DATA') {
                $this->fbaInventoryUsa($reportId);
            }

            return response()->json([
                'success'      => true,
                'reportId'     => $reportId,
                'download_url' => asset("storage/{$storagePath}"),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // insert to temp_products    GET_MERCHANT_LISTINGS_ALL_DATA
    public function tempProducts($reportId, $country)
    {
        $parser = new AmazonReportParser();

        $batchData            = [];
        $asinComboCandidates  = [];
        $newProductsData      = [];
        $productListingData   = [];
        $skus                 = [];

        $now = now();

        // Parse report ONCE
        $reportRows = iterator_to_array($parser->parse($reportId));

        // Prepare temp_products rows and collect SKUs
        foreach ($reportRows as $data) {
            $batchData[] = [
                'item_name'                  => $data['item-name'] ?? null,
                'item_description'           => $data['item-description'] ?? null,
                'listing_id'                 => $data['listing-id'],
                'seller_sku'                 => $data['seller-sku'] ?? null,
                'price'                      => $data['price'] ?: null,
                'quantity'                   => $data['quantity'] ?: null,
                'open_date'                  => !empty($data['open-date']) ? date('Y-m-d H:i:s', strtotime($data['open-date'])) : null,
                'image_url'                  => $data['image-url'] ?? null,
                'item_is_marketplace'        => ($data['item-is-marketplace'] ?? '') === 'y',
                'product_id_type'            => $data['product-id-type'] ?? null,
                'zshop_shipping_fee'         => $data['zshop-shipping-fee'] ?? null,
                'item_note'                  => $data['item-note'] ?? null,
                'item_condition'             => $data['item-condition'] ?? null,
                'zshop_category1'            => $data['zshop-category1'] ?? null,
                'zshop_browse_path'          => $data['zshop-browse-path'] ?? null,
                'zshop_storefront_feature'   => $data['zshop-storefront-feature'] ?? null,
                'asin1'                      => $data['asin1'] ?? null,
                'asin2'                      => $data['asin2'] ?? null,
                'asin3'                      => $data['asin3'] ?? null,
                'will_ship_internationally'  => ($data['will-ship-internationally'] ?? '') === 'y',
                'expedited_shipping'         => ($data['expedited-shipping'] ?? '') === 'y',
                'zshop_boldface'             => ($data['zshop-boldface'] ?? '') === 'y',
                'product_id'                 => $data['product-id'] ?? null,
                'bid_for_featured_placement' => ($data['bid-for-featured-placement'] ?? '') === 'y',
                'add_delete'                 => $data['add-delete'] ?? null,
                'pending_quantity'           => $data['pending-quantity'] ?: null,
                'fulfillment_channel'        => $data['fulfillment-channel'] ?? null,
                'merchant_shipping_group'    => $data['merchant-shipping-group'] ?? null,
                'status'                     => $data['status'] ?? null,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ];

            if (!empty($data['seller-sku'])) {
                $skus[$data['seller-sku']] = $data['item-description'] ?? '';
            }
        }

        // Fetch existing products by SKU
        $existingProducts = Product::whereIn('sku', array_keys($skus))
            ->pluck('id', 'sku')
            ->toArray();

        $newSkus = array_diff(array_keys($skus), array_keys($existingProducts));

        foreach ($newSkus as $sku) {
            $newProductsData[] = [
                'sku'         => $sku,
                'short_title' => $skus[$sku],
                'uuid'        => (string) Str::uuid(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if (!empty($newProductsData)) {
            Product::insert($newProductsData);

            $newlyInserted = Product::whereIn('sku', $newSkus)
                ->pluck('id', 'sku')
                ->toArray();

            $existingProducts = $existingProducts + $newlyInserted;
        }

        // Prepare product listings and asin candidates
        foreach ($reportRows as $data) {
            if (!empty($data['seller-sku'])) {
                $productId = $existingProducts[$data['seller-sku']] ?? null;

                if ($productId) {
                    $productListingData[] = [
                        'products_id'     => $productId,
                        'uuid'            => (string) Str::uuid(),
                        'country'         => $country,
                        'progress_status' => 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];

                    $asin1 = $data['asin1'] ?? null;
                    $asin2 = $data['asin2'] ?? null;
                    $asin3 = $data['asin3'] ?? null;

                    if ($asin1 || $asin2 || $asin3) {
                        $key = $productId . '|' . $asin1 . '|' . $asin2 . '|' . $asin3;
                        $asinComboCandidates[$key] = [
                            'product_id' => $productId,
                            'asin1'      => $asin1,
                            'asin2'      => $asin2,
                            'asin3'      => $asin3,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        // Fetch existing product_asins to skip duplicates
        $existingAsinKeys = ProductAsins::whereIn(
            'product_id',
            array_column($asinComboCandidates, 'product_id')
        )
            ->get(['product_id', 'asin1', 'asin2', 'asin3'])
            ->map(function ($row) {
                return $row->product_id . '|' . $row->asin1 . '|' . $row->asin2 . '|' . $row->asin3;
            })->toArray();

        $newAsinBatchData = [];

        foreach ($asinComboCandidates as $key => $row) {
            if (!in_array($key, $existingAsinKeys)) {
                $newAsinBatchData[] = $row;
            }
        }

        // Insert temp_products in chunks
        foreach (array_chunk($batchData, 500) as $chunk) {
            TempProducts::insert($chunk);
        }

        // Insert product listings in chunks
        if (!empty($productListingData)) {
            foreach (array_chunk($productListingData, 500) as $chunk) {
                ProductListing::insert($chunk);
            }
        }

        // Insert product_asins in chunks
        if (!empty($newAsinBatchData)) {
            foreach (array_chunk($newAsinBatchData, 500) as $chunk) {
                ProductAsins::insert($chunk);
            }
        }
    }




    // insert to temp_reserved_inventories    GET_RESERVED_INVENTORY_DATA
    public function reservedInventory($reportId)
    {
        $parser    = new AmazonReportParser();
        $batchData = [];

        foreach ($parser->parse($reportId) as $data) {
            $batchData[] = [
                'sku'                     => $data['sku'] ?? null,
                'fnsku'                   => $data['fnsku'] ?? null,
                'asin'                    => $data['asin'] ?? null,
                'product_name'            => $data['product-name'] ?? null,
                'reserved_qty'            => (int) ($data['reserved_qty'] ?? 0),
                'reserved_customerorders' => (int) ($data['reserved_customerorders'] ?? 0),
                'reserved_fc_transfers'   => (int) ($data['reserved_fc-transfers'] ?? 0),
                'reserved_fc_processing'  => (int) ($data['reserved_fc-processing'] ?? 0),
                'created_at'              => now(),
                'updated_at'              => now(),
            ];
        }

        foreach (array_chunk($batchData, 500) as $chunk) {
            TempReservedInventory::insert($chunk);
        }
    }

    // insert to temp_afn_inventories    GET_AFN_INVENTORY_DATA
    public function afnInventory($reportId)
    {
        $parser    = new AmazonReportParser();
        $batchData = [];

        foreach ($parser->parse($reportId) as $data) {
            $batchData[] = [
                'seller_sku'               => $data['seller-sku'] ?? null,
                'fulfillment_channel_sku'  => $data['fulfillment-channel-sku'] ?? null,
                'asin'                     => $data['asin'] ?? null,
                'condition_type'           => $data['condition-type'] ?? null,
                'warehouse_condition_code' => $data['Warehouse-Condition-code'] ?? null,
                'quantity_available'       => $data['Quantity Available'] ?? null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }

        foreach (array_chunk($batchData, 500) as $chunk) {
            TempAfnInventory::insert($chunk);
        }
    }

    // insert to temp_sales_orders    GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL
    public function salesOrder($reportId)
    {
        $parser    = new AmazonReportParser();
        $batchData = [];

        foreach ($parser->parse($reportId) as $data) {
            $batchData[] = [
                'amazon_order_id'                    => $data['amazon-order-id'] ?? null,
                'merchant_order_id'                  => $data['merchant-order-id'] ?? null,
                'purchase_date'                      => isset($data['purchase-date']) ? date('Y-m-d H:i:s', strtotime($data['purchase-date'])) : null,
                'last_updated_date'                  => isset($data['last-updated-date']) ? date('Y-m-d H:i:s', strtotime($data['last-updated-date'])) : null,
                'order_status'                       => $data['order-status'] ?? null,
                'fulfillment_channel'                => $data['fulfillment-channel'] ?? null,
                'sales_channel'                      => $data['sales-channel'] ?? null,
                'order_channel'                      => $data['order-channel'] ?? null,
                'ship_service_level'                 => $data['ship-service-level'] ?? null,
                'product_name'                       => $data['product-name'] ?? null,
                'sku'                                => $data['sku'] ?? null,
                'asin'                               => $data['asin'] ?? null,
                'item_status'                        => $data['item-status'] ?? null,
                'quantity'                           => isset($data['quantity']) ? (int) $data['quantity'] : null,
                'currency'                           => $data['currency'] ?? null,
                'item_price'                         => isset($data['item-price']) ? (float) $data['item-price'] : null,
                'item_tax'                           => isset($data['item-tax']) ? (float) $data['item-tax'] : null,
                'shipping_price'                     => isset($data['shipping-price']) ? (float) $data['shipping-price'] : null,
                'shipping_tax'                       => isset($data['shipping-tax']) ? (float) $data['shipping-tax'] : null,
                'gift_wrap_price'                    => isset($data['gift-wrap-price']) ? (float) $data['gift-wrap-price'] : null,
                'gift_wrap_tax'                      => isset($data['gift-wrap-tax']) ? (float) $data['gift-wrap-tax'] : null,
                'item_promotion_discount'            => isset($data['item-promotion-discount']) ? (float) $data['item-promotion-discount'] : null,
                'ship_promotion_discount'            => isset($data['ship-promotion-discount']) ? (float) $data['ship-promotion-discount'] : null,
                'ship_city'                          => $data['ship-city'] ?? null,
                'ship_state'                         => $data['ship-state'] ?? null,
                'ship_postal_code'                   => $data['ship-postal-code'] ?? null,
                'ship_country'                       => $data['ship-country'] ?? null,
                'promotion_ids'                      => $data['promotion-ids'] ?? null,
                'cpf'                                => $data['cpf'] ?? null,
                'is_business_order'                  => isset($data['is-business-order']) && $data['is-business-order'] === 'true',
                'purchase_order_number'              => $data['purchase-order-number'] ?? null,
                'price_designation'                  => $data['price-designation'] ?? null,
                'signature_confirmation_recommended' => isset($data['signature-confirmation-recommended']) && $data['signature-confirmation-recommended'] === 'true',
                'created_at'                         => now(),
                'updated_at'                         => now(),
            ];
        }

        foreach (array_chunk($batchData, 500) as $chunk) {
            TempSalesOrder::insert($chunk);
        }
    }
    public function fbaInventoryUsa($reportId)
    {
        $parser    = new AmazonReportParser();
        $batchData = [];

        foreach ($parser->parse($reportId) as $data) {
            if (empty($data['sku'])) {
                continue;
            }
            $batchData[] = [
                'sku'           => $data['sku'],
                'instock'       => (int)($data['afn-fulfillable-quantity'] ?? 0),
                'totalstock'    => (int)($data['afn-total-quantity'] ?? 0),
                'reserve_stock' => (int)($data['afn-reserved-quantity'] ?? 0),
                'country'       =>  'USA',
                'add_date'      => now()->toDateString(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if (!empty($batchData)) {
            foreach (array_chunk($batchData, 500) as $chunk) {
                FbaInventoryUsa::insert($chunk);
            }
        }
    }

}
