<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\TempProducts;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductAsins;
use App\Services\AmazonReportParser as ServicesAmazonReportParser;

class ReportService
{
    public function downloadAndProcessReport($connector, string $reportId, string $country): array
    {
        $api = $connector->reportsV20210630();

        // Fetch report status first
        $report = $api->getReport($reportId)->json();

        if (($report['processingStatus'] ?? '') !== 'DONE') {
            return [
                'error' => true,
                'message' => 'Report is not ready yet. Status: ' . ($report['processingStatus'] ?? 'unknown'),
                'status' => 202
            ];
        }

        $documentId = $report['reportDocumentId'] ?? null;

        if (!$documentId) {
            return [
                'error' => true,
                'message' => 'Report document ID not found.',
                'status' => 404
            ];
        }

        $reportType = $report['reportType'] ?? 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';

        // Get signed URL for document
        $document = $api->getReportDocument($documentId, $reportType)->json();

        $url = $document['url'] ?? null;
        $compression = $document['compressionAlgorithm'] ?? null;

        if (!$url) {
            return [
                'error' => true,
                'message' => 'Signed URL not found in report document.',
                'status' => 500
            ];
        }

        // Download via HTTP client (safer than file_get_contents)
        $response = Http::timeout(300)->get($url);
        if (!$response->ok()) {
            return [
                'error' => true,
                'message' => 'Failed to download report file from signed URL.',
                'status' => 500
            ];
        }

        $reportContent = $response->body();

        if ($compression === 'GZIP') {
            $reportContent = gzdecode($reportContent);
        }

        if ($reportContent === false) {
            return [
                'error' => true,
                'message' => 'Failed to decode GZIP report content.',
                'status' => 500
            ];
        }

        $today = now('UTC')->toDateString();
        $filename = "{$today}_report_{$reportId}.txt";
        $storagePath = "api/reports/{$filename}";

        Storage::disk('public')->put($storagePath, $reportContent);

        // Dispatch logic based on report type
        if ($reportType === 'GET_MERCHANT_LISTINGS_ALL_DATA') {
            $this->processMerchantListings($reportId, $country);
        } elseif ($reportType === 'GET_RESERVED_INVENTORY_DATA') {
            $this->processReservedInventory($reportId);
        } elseif ($reportType === 'GET_AFN_INVENTORY_DATA') {
            $this->processAfnInventory($reportId);
        } elseif ($reportType === 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL') {
            $this->processSalesOrder($reportId);
        } elseif ($reportType === 'GET_FBA_MYI_ALL_INVENTORY_DATA') {
            $this->processFbaInventoryUsa($reportId);
        }

        return [
            'success' => true,
            'reportId' => $reportId,
            'download_url' => asset("storage/{$storagePath}")
        ];
    }


    /**
     * Formerly tempProducts()
     */
    public function processMerchantListings(string $reportId, string $country): void
    {
        $parser = new ServicesAmazonReportParser();
        $now = now();

        // Parse the report into rows
        $reportRows = iterator_to_array($parser->parse($reportId));

        if (empty($reportRows)) {
            Log::warning("No data found in merchant listings report: {$reportId}");
            return;
        }

        // Map SKUs to short titles
        $skusMap = collect($reportRows)
            ->mapWithKeys(fn($data) => [
                $data['seller-sku'] ?? '' => Str::limit($data['item-description'] ?? '', 255),
            ])
            ->filter(fn($title, $sku) => !empty($sku))
            ->toArray();

        if (empty($skusMap)) {
            Log::warning("No SKUs found in merchant listings report: {$reportId}");
            return;
        }

        // Fetch existing products
        $existingProducts = Product::whereIn('sku', array_keys($skusMap))
            ->pluck('id', 'sku')
            ->toArray();

        // Insert new products if needed
        $newSkus = array_diff(array_keys($skusMap), array_keys($existingProducts));
        
        if (!empty($newSkus)) {
            $newProducts = collect($newSkus)->map(fn($sku) => [
                'sku'         => $sku,
                'short_title' => $skusMap[$sku],
                'uuid'        => (string) Str::uuid(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ])->values()->toArray();
            Product::insert($newProducts);

            $newlyInserted = Product::whereIn('sku', $newSkus)
                ->pluck('id', 'sku')
                ->toArray();

            $existingProducts += $newlyInserted;

            Log::info("Inserted new products from merchant listings report: " . implode(", ", $newSkus));
        }

        /**
         * Track inserted listings and unique ASINs per product.
         */
        $productListings = [];
        $asinRecordsPerProduct = [];

        foreach ($reportRows as $row) {
            $sku = $row['seller-sku'] ?? null;
            if (empty($sku)) {
                continue;
            }

            $productId = $existingProducts[$sku] ?? null;
            if (!$productId) {
                continue;
            }

            // Add product listing row
            $productListings[] = [
                'products_id'     => $productId,
                'uuid'            => (string) Str::uuid(),
                'country'         => $country,
                'progress_status' => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            // Prepare unique ASINs per product
            $asinFields = ['asin1', 'asin2', 'asin3'];

            foreach ($asinFields as $asinField) {
                $asinValue = $row[$asinField] ?? null;

                if (!empty($asinValue)) {
                    // Avoid duplicate rows for same product + asin
                    $asinKey = "{$productId}|{$asinField}|{$asinValue}";

                    if (!isset($asinRecordsPerProduct[$asinKey])) {
                        $asinRecordsPerProduct[$asinKey] = [
                            'product_id' => $productId,
                            'asin1'      => null,
                            'asin2'      => null,
                            'asin3'      => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $asinRecordsPerProduct[$asinKey][$asinField] = $asinValue;
                }
            }
        }

        // Aggregate unique ASIN rows
        $asinCombos = array_values($asinRecordsPerProduct);

        // Delete previous product_asins for these products
        $productIds = array_unique(array_column($asinCombos, 'product_id'));

        if (!empty($productIds)) {
            ProductAsins::whereIn('product_id', $productIds)->forceDelete();
            Log::info("Deleted old ProductAsins for products: " . implode(', ', $productIds));
        }

        if (!empty($asinCombos)) {
            foreach (array_chunk($asinCombos, 500) as $chunk) {
                ProductAsins::insert($chunk);
            }
            Log::info("Inserted new ProductAsins rows: " . count($asinCombos));
        }

        if (!empty($productListings)) {
            foreach (array_chunk($productListings, 500) as $chunk) {
                ProductListing::insert($chunk);
            }
            Log::info("Inserted ProductListings rows: " . count($productListings));
        }
    }

}
