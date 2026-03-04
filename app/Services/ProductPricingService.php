<?php

namespace App\Services;

use App\Models\AmazonSoldPrice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProductPricingService
{
    public function savePricingData(array $pricingData): void
    {
        foreach ($pricingData as $product) {
            $asin = $product['ASIN'] ?? null;
            $marketplaceId = $product['Product']['Identifiers']['MarketplaceASIN']['MarketplaceId'] ?? null;

            if (!$asin || !$marketplaceId) {
                continue;
            }

            foreach ($product['Product']['Offers'] ?? [] as $offer) {
                $buyingPrice = $offer['BuyingPrice'] ?? [];
                $points = $buyingPrice['Points'] ?? null;
                $sellerSku = $offer['SellerSKU'] ?? null;

                if (empty($sellerSku)) {
                    continue;
                }

                // Check if SKU maps to multiple products
                $productIds = Product::where('sku', $sellerSku)->pluck('id');

                if ($productIds->count() > 1) {
                    Log::warning("❗ SKU [{$sellerSku}] belongs to multiple products. Skipping save for ASIN [{$asin}].");
                    continue;
                }

                $productId = $productIds->first();

                if (!$productId) {
                    // Log::warning("❗ No product found for seller_sku [{$sellerSku}]");
                    continue;
                }

                $data = [
                    'asin'                   => $asin,
                    'marketplace_id'         => $marketplaceId,
                    'seller_sku'             => $sellerSku,
                    'offer_type'             => $offer['offerType'] ?? null,
                    'listing_price'          => $buyingPrice['ListingPrice']['Amount'] ?? null,
                    'landed_price'           => $buyingPrice['LandedPrice']['Amount'] ?? null,
                    'shipping_price'         => $buyingPrice['Shipping']['Amount'] ?? null,
                    'regular_price'          => $offer['RegularPrice']['Amount'] ?? null,
                    'business_price'         => $offer['businessPrice']['Amount'] ?? null,
                    'points_number'          => $points['PointsNumber'] ?? null,
                    'points_value'           => $points['PointsMonetaryValue']['Amount'] ?? null,
                    'item_condition'         => $offer['ItemCondition'] ?? null,
                    'item_sub_condition'     => $offer['ItemSubCondition'] ?? null,
                    'fulfillment_channel'    => $offer['FulfillmentChannel'] ?? null,
                    'sales_rankings'         => json_encode($product['SalesRankings'] ?? []),
                    'quantity_discount_prices' => json_encode($offer['quantityDiscountPrices'] ?? []),
                    'updated_at'             => Carbon::now(),
                ];

                // ✅ Check if a price row already exists for this SKU + ASIN
                $existing = AmazonSoldPrice::where('asin', $asin)
                    ->where('marketplace_id', $marketplaceId)
                    ->where('seller_sku', $sellerSku)
                    ->first();

                if ($existing) {
                    $fieldsToCompare = [
                        'listing_price',
                        'landed_price',
                        'shipping_price',
                        'regular_price',
                        'business_price',
                        'points_number',
                        'points_value',
                    ];

                    $hasChanged = false;
                    foreach ($fieldsToCompare as $field) {
                        if ($existing->$field != $data[$field]) {
                            $hasChanged = true;
                            break;
                        }
                    }

                    if ($hasChanged) {
                        $existing->update($data);
                        // Log::info("🔁 Updated pricing for SKU {$sellerSku}, ASIN {$asin}, marketplace {$marketplaceId}.");
                    }
                } else {
                    $data['created_at'] = Carbon::now();
                    AmazonSoldPrice::create($data);
                    // Log::info("🆕 Inserted pricing for SKU {$sellerSku}, ASIN {$asin}, marketplace {$marketplaceId}.");
                }
            }
        }
    }
}
