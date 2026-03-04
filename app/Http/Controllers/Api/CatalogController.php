<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TempListCatalogCategories;
use SellingPartnerApi\Seller\SellerConnector;
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{
    public function getCatalogItem(SellerConnector $connector)
    {
        try {
            $itemApi  = $connector->catalogItemsV20220401();
            $response = $itemApi->getCatalogItem(
                'B0CSSW3YHX',                          // ASIN
                ['ATVPDKIKX0DER'],                     // Marketplace ID(s)
                ['summaries', 'attributes', 'images', 'productTypes'],        // Optional: includedData
            );

            $data         = $response->json();
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('SP-API Catalog Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch catalog item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function listCatalogCategories(SellerConnector $connector)
    {
        try {
            $itemApi       = $connector->catalogItemsV0();
            $marketplaceId = 'ATVPDKIKX0DER';
            $asin          = 'B0CSYX3S9C';
            $sellerSku     = null;
            $response      = $itemApi->listCatalogCategories($marketplaceId, $asin, $sellerSku);
            $data          = $response->json();

            if (!empty($data['payload'][0])) {
                $categoryTree = $data['payload'][0];

                $catalogCategories = $this->getCatalogCategories($categoryTree);
                TempListCatalogCategories::updateOrCreate(
                    [
                        'marketplace_id' => $marketplaceId,
                        'asin'           => $asin,
                    ],
                    [
                        'seller_sku'         => $sellerSku,
                        'catalog_categories' => $catalogCategories,
                    ]
                );
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('SP-API Catalog Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch catalog item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCatalogCategories(array $category): string
    {
        $names = [];

        while (isset($category['ProductCategoryName']) && count($names) < 3) {
            $names[] = $category['ProductCategoryName'];
            $category = $category['parent'] ?? [];
        }

        return implode(' -> ', $names);
    }
}
