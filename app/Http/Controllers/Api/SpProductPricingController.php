<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AmazonSoldPrice;
use App\Models\ProductAsins;
use App\Models\ProductRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;
use App\Models\TempProductPricing;
use Illuminate\Support\Carbon;

use Exception;

class SpProductPricingController extends Controller
{
    public function getPricing(Request $request, SellerConnector $connector)
    {
        try {
            $asins = $request->input('asins', ['B0CZRX51QM']);
            $marketplaceId = 'ATVPDKIKX0DER'; // US marketplace
            $itemType = 'Asin';               // or 'Sku' if you're passing SKUs

            $api = $connector->productPricingV0();

            $response = $api->getPricing(
                $marketplaceId,
                $itemType,
                $asins,
                null,       // SKU list (null if using ASIN)
                'New',      // Optional item condition
                'B2C'       // Optional offer type (default is B2C)
            );
            $payload = $response->json('payload');

            // Store pricing data
            $this->savePricingData($payload);

            return response()->json($response->json());
        } catch (Exception $e) {
            Log::error('SP-API getPricing error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching pricing: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCompetitivePricing(Request $request, SellerConnector $connector)
    {
        try {
            // Get the first ProductAsins record
            // $firstProduct = ProductAsins::query()
            //     ->whereNotNull('asin1')
            //     ->first();

            // if (!$firstProduct) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'No ASIN found in the ProductAsins table.'
            //     ], 404);
            // }

            // $asins = array_filter([
            //     $firstProduct->asin1,
            //     $firstProduct->asin2,
            //     $firstProduct->asin3,
            // ]);

            $asins = ['B01KIFISX2'];

            $marketplaceId = 'ATVPDKIKX0DER';
            $itemType = 'Asin';

            $api = $connector->productPricingV0();
            $response = $api->getCompetitivePricing($marketplaceId, $itemType, $asins);
            $payload = $response->json('payload');

            // $asinToProductId = [];
            // foreach ($asins as $asin) {
            //     $asinToProductId[$asin] = $firstProduct->product_id;
            // }

            // $this->saveCompetitivePricingRankingData($payload, $asinToProductId);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('SP-API getCompetitivePricing error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching competitive pricing: ' . $e->getMessage()
            ], 500);
        }
    }


    public function saveCompetitivePricingRankingData(array $pricingData, array $asinToProductId): void
    {
        $batchData = [];

        $marketplaceToCountry = [
            'A2EUQ1WTGCTBG2' => 'CA',
            'ATVPDKIKX0DER' => 'US',
            'A1AM78C64UM0Y8' => 'MX',
        ];

        foreach ($pricingData as $product) {
            $asin = $product['ASIN'] ?? null;

            if (!$asin || !isset($asinToProductId[$asin])) {
                continue;
            }

            $productId = $asinToProductId[$asin];
            $salesRankings = $product['Product']['SalesRankings'] ?? [];
            $marketplaceId = $product['Product']['Identifiers']['MarketplaceASIN']['MarketplaceId'] ?? '';
            $country = $marketplaceToCountry[$marketplaceId] ?? 'Other';

            $lowestRank = null;
            $lowestRankCategory = null;

            foreach ($salesRankings as $ranking) {
                $rank = $ranking['Rank'] ?? null;

                if ($rank !== null && ($lowestRank === null || $rank < $lowestRank)) {
                    $lowestRank = $rank;
                    $lowestRankCategory = $ranking['ProductCategoryId'] ?? null;
                }
            }

            $amount = $product['Product']['CompetitivePricing']['CompetitivePrices'][0]['Price']['LandedPrice']['Amount'] ?? null;

            if ($lowestRank !== null && $lowestRankCategory) {
                $batchData[] = [
                    'product_id'    => $productId,
                    'date'          => now(),
                    'current_price' => $amount,
                    'country'       => $country,
                    'rank'          => $lowestRank,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
        }

        if (!empty($batchData)) {
            foreach (array_chunk($batchData, 500) as $chunk) {
                ProductRanking::insert($chunk);
            }
        }
    }



    public function savePricingData(array $pricingData)
    {
        $batchData = [];

        foreach ($pricingData as $product) {
            $asin = $product['ASIN'] ?? null;
            $marketplaceId = $product['Product']['Identifiers']['MarketplaceASIN']['MarketplaceId'] ?? null;

            foreach ($product['Product']['Offers'] ?? [] as $offer) {
                $buyingPrice = $offer['BuyingPrice'] ?? [];
                $points = $buyingPrice['Points'] ?? null;

                $batchData[] = [
                    'asin' => $asin,
                    'marketplace_id' => $marketplaceId,
                    'seller_sku' => $offer['SellerSKU'] ?? null,
                    'offer_type' => $offer['offerType'] ?? null,

                    // Prices
                    'listing_price' => $buyingPrice['ListingPrice']['Amount'] ?? null,
                    'landed_price' => $buyingPrice['LandedPrice']['Amount'] ?? null,
                    'shipping_price' => $buyingPrice['Shipping']['Amount'] ?? null,
                    'regular_price' => $offer['RegularPrice']['Amount'] ?? null,
                    'business_price' => $offer['businessPrice']['Amount'] ?? null,

                    // Points
                    'points_number' => $points['PointsNumber'] ?? null,
                    'points_value' => $points['PointsMonetaryValue']['Amount'] ?? null,

                    // Condition & Channel
                    'item_condition' => $offer['ItemCondition'] ?? null,
                    'item_sub_condition' => $offer['ItemSubCondition'] ?? null,
                    'fulfillment_channel' => $offer['FulfillmentChannel'] ?? null,

                    // Optional JSON fields
                    'sales_rankings' => json_encode($product['SalesRankings'] ?? []),
                    'quantity_discount_prices' => json_encode($offer['quantityDiscountPrices'] ?? []),

                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($batchData, 500) as $chunk) {
            AmazonSoldPrice::insert($chunk);
        }
    }
}
