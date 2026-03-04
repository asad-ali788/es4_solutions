<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductAsins;
use Illuminate\Support\Facades\DB;
use App\Services\AmazonReportParser as ServicesAmazonReportParser;

class NewProductsService
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

        // Get signed URL for document
        $document = $api->getReportDocument($documentId, $report['reportType'])->json();

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

        $today       = now('UTC')->toDateString();
        $filename    = "{$today}_report_{$reportId}.txt";
        $storagePath = "api/reports/{$filename}";

        Storage::disk('public')->put($storagePath, $reportContent);

        // Dispatch logic based on report type
        if ($report['reportType'] === 'GET_MERCHANT_LISTINGS_ALL_DATA') {
            $this->processMerchantListings($reportId, $country);
        } else {
            Log::info("Inserted new products + listings + ASINs in Something wrong check the report type");
        }

        return [
            'success' => true,
            'reportId' => $reportId,
            'download_url' => asset("storage/{$storagePath}")
        ];
    }

    public function processMerchantListings(string $reportId, string $country): void
    {
        $parser = new ServicesAmazonReportParser();
        $now    = now();

        // 1) First pass (no DB writes) – safe outside transaction
        $skusMap = [];

        foreach ($parser->parse($reportId) as $row) {
            $rawSku = $row['seller-sku'] ?? null;
            if ($rawSku === null) {
                continue;
            }

            $sku = strtoupper(trim($rawSku));
            if ($sku === '') {
                continue;
            }

            if (!isset($skusMap[$sku])) {
                $skusMap[$sku] = Str::limit($row['item-description'] ?? '', 255);
            }
        }

        if (empty($skusMap)) {
            Log::warning("No SKUs found in merchant listings report: {$reportId}");
            return;
        }

        DB::transaction(function () use ($reportId, $country, $parser, $skusMap, $now) {
            $allSkus = array_keys($skusMap);

            // 2) Find existing products
            $existingProducts = Product::query()
                ->selectRaw('id, UPPER(TRIM(sku)) as sku_key')
                ->whereIn(DB::raw('UPPER(TRIM(sku))'), $allSkus)
                ->pluck('id', 'sku_key')
                ->toArray();

            $newSkus = array_diff($allSkus, array_keys($existingProducts));
            if (empty($newSkus)) {
                Log::info("No new products found in merchant listings report: {$reportId}");
                return;
            }

            // 3) Insert new products
            $newProducts = collect($newSkus)->map(function (string $sku) use ($skusMap, $now) {
                return [
                    'sku'         => $sku,
                    'short_title' => $skusMap[$sku] ?? '',
                    'uuid'        => (string) Str::uuid(),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            })->values()->toArray();

            Product::insert($newProducts);

            // reload IDs (now including freshly inserted)
            $existingProducts = Product::query()
                ->selectRaw('id, UPPER(TRIM(sku)) as sku_key')
                ->whereIn(DB::raw('UPPER(TRIM(sku))'), $allSkus)
                ->pluck('id', 'sku_key')
                ->toArray();

            $newProductIdsBySku = [];
            foreach ($newSkus as $sku) {
                if (isset($existingProducts[$sku])) {
                    $newProductIdsBySku[$sku] = $existingProducts[$sku];
                }
            }

            if (empty($newProductIdsBySku)) {
                Log::warning("No product IDs found for new SKUs in report (inside transaction): {$reportId}");
                return;
            }

            // 4) Second pass in the SAME transaction:
            $productListings       = [];
            $asinRecordsPerProduct = [];

            foreach ($parser->parse($reportId) as $row) {
                $rawSku = $row['seller-sku'] ?? null;
                if ($rawSku === null) {
                    continue;
                }

                $sku = strtoupper(trim($rawSku));
                if (!isset($newProductIdsBySku[$sku])) {
                    continue;
                }

                $productId = $newProductIdsBySku[$sku];

                $productListings[] = [
                    'products_id'     => $productId,
                    'uuid'            => (string) Str::uuid(),
                    'country'         => $country,
                    'progress_status' => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];

                $asinFields = ['asin1', 'asin2', 'asin3'];

                foreach ($asinFields as $asinField) {
                    $asinValue = $row[$asinField] ?? null;

                    if (!empty($asinValue)) {
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

            $asinCombos = array_values($asinRecordsPerProduct);

            if (!empty($asinCombos)) {
                foreach (array_chunk($asinCombos, 500) as $chunk) {
                    ProductAsins::insert($chunk);
                }
            }

            if (!empty($productListings)) {
                foreach (array_chunk($productListings, 500) as $chunk) {
                    ProductListing::insert($chunk);
                }
            }

            Log::info("Inserted new products + listings + ASINs in single transaction for report {$reportId}");
        });
    }
}
