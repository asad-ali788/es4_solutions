<?php

namespace App\Services;

use App\Models\ProductRanking;

class CompetitivePricingService
{
    protected array $marketplaceToCountry = [
        'A2EUQ1WTGCTBG2' => 'CA',
        'ATVPDKIKX0DER'  => 'US',
        'A1AM78C64UM0Y8' => 'MX',
    ];

    public function saveCompetitivePricingRankingData(array $pricingData, array $asinToProductIds): void
    {
        $today = now()->toDateString();
        $batchData = [];

        foreach ($pricingData as $product) {
            $asin = $product['ASIN'] ?? null;

            if (!$asin || !isset($asinToProductIds[$asin])) {
                continue;
            }

            $salesRankings = $product['Product']['SalesRankings'] ?? [];
            $marketplaceId = $product['Product']['Identifiers']['MarketplaceASIN']['MarketplaceId'] ?? '';
            $country = $this->marketplaceToCountry[$marketplaceId] ?? 'Other';

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

            foreach ($asinToProductIds[$asin] as $productId) {
                if ($lowestRank !== null && $lowestRankCategory) {
                    // Check if record already exists for today
                    $existing = ProductRanking::where([
                        ['product_id', '=', $productId],
                        ['date', '=', $today],
                        ['country', '=', $country],
                    ])->first();

                    if ($existing) {
                        if (
                            $existing->rank != $lowestRank ||
                            $existing->current_price != $amount
                        ) {
                            $existing->update([
                                'rank' => $lowestRank,
                                'current_price' => $amount,
                                'updated_at' => now(),
                            ]);
                        }
                    } else {
                        $batchData[] = [
                            'product_id'    => $productId,
                            'date'          => $today,
                            'current_price' => $amount,
                            'country'       => $country,
                            'rank'          => $lowestRank,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                    }
                }
            }
        }

        if (!empty($batchData)) {
            foreach (array_chunk($batchData, 500) as $chunk) {
                ProductRanking::insert($chunk);
            }
        }
    }
}
