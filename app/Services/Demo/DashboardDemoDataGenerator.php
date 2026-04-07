<?php

namespace App\Services\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardDemoDataGenerator
{
    private const COUNTRIES = [
        ['code' => 'US', 'marketplace' => 'Amazon.com', 'currency' => 'USD', 'fx' => 1.00],
        ['code' => 'CA', 'marketplace' => 'Amazon.ca', 'currency' => 'CAD', 'fx' => 0.74],
        ['code' => 'MX', 'marketplace' => 'Amazon.com.mx', 'currency' => 'MXN', 'fx' => 0.06],
    ];

    private const CAMPAIGN_NAMES = [
        'Evergreen Essentials Push',
        'Prime Value Sprint',
        'Home Refresh Drive',
        'Kitchen Hero Launch',
        'Weekend Deal Booster',
        'Summer Stock Acceleration',
        'Back To Routine Focus',
        'Premium Bundle Growth',
        'Daily Use Conversion Run',
        'Top Pick Awareness Wave',
        'Smart Choice Momentum',
        'Family Saver Expansion',
        'Brand Lift Velocity',
        'Checkout Magnet Pulse',
        'High Intent Capture',
        'Category Leader Burst',
        'New Arrival Amplifier',
        'Search Rank Stabilizer',
        'AOV Uplift Sequence',
        'Retention Lift Campaign',
        'Gift Ready Spotlight',
        'Always On Performance',
        'Holiday Warmup Series',
        'Cross Sell Ignition',
        'Repeat Buyer Engine',
        'Discovery Funnel Boost',
        'Profit Guard Optimizer',
        'Daily Demand Builder',
        'Scale Up Accelerator',
        'Core SKU Defender',
    ];

    private const KEYWORDS = [
        'home storage organizer',
        'kitchen shelf rack',
        'premium glass bottle',
        'wireless desk lamp',
        'non slip hanger',
        'portable lunch box',
        'stainless steel cup',
        'bathroom holder set',
        'travel makeup bag',
        'drawer divider pack',
        'office cable clips',
        'fitness shaker bottle',
        'air fryer liner',
        'meal prep container',
        'bedside charging stand',
        'car trunk organizer',
        'pantry label stickers',
        'kitchen sink caddy',
        'silicone cooking set',
        'under sink organizer',
        'closet storage bin',
        'compact spice rack',
        'vacuum storage bag',
        'laundry basket foldable',
        'reusable zip bags',
        'fridge organizer tray',
        'shoe storage boxes',
        'waterproof toiletry bag',
        'coffee mug warmer',
        'magnetic spice tins',
    ];

    public function generate(Carbon $startDate, Carbon $endDate, bool $cleanup = false): array
    {
        $productCatalog = [];
        $campaignCatalog = [];

        DB::transaction(function () use ($startDate, $endDate, $cleanup, &$productCatalog, &$campaignCatalog) {
            $this->seedCurrencies();
            $productCatalog = $this->seedProductsAndListings();
            $this->seedWarehouseAndInventoryData($startDate, $endDate, $productCatalog);
            $campaignCatalog = $this->seedCampaignsAndAdsProducts($productCatalog);

            if ($cleanup) {
                $this->cleanupDemoRows($startDate, $endDate, $campaignCatalog);
            }

            $this->seedSalesFacts($startDate, $endDate, $productCatalog);
            $this->seedMonthlyAdsProductPerformance($startDate, $endDate, $productCatalog);
            $this->seedAdsPerformanceFacts($startDate, $endDate, $campaignCatalog, $productCatalog);
            $this->seedCampaignRecommendations($startDate, $endDate, $campaignCatalog);
            $this->seedKeywordRecommendations($startDate, $endDate, $campaignCatalog);
            $this->seedSearchTermSummaryReports($startDate, $endDate, $campaignCatalog);
        });

        return [
            'products' => count($productCatalog),
            'campaigns' => count($campaignCatalog),
            'keywords' => count(self::KEYWORDS),
            'from' => $startDate->toDateString(),
            'to' => $endDate->toDateString(),
        ];
    }

    private function seedCurrencies(): void
    {
        $rows = [
            ['country_code' => 'US', 'currency_code' => 'USD', 'currency_name' => 'US Dollar', 'currency_symbol' => '$', 'conversion_rate_to_usd' => 1.00],
            ['country_code' => 'CA', 'currency_code' => 'CAD', 'currency_name' => 'Canadian Dollar', 'currency_symbol' => 'C$', 'conversion_rate_to_usd' => 0.74],
            ['country_code' => 'MX', 'currency_code' => 'MXN', 'currency_name' => 'Mexican Peso', 'currency_symbol' => '$', 'conversion_rate_to_usd' => 0.06],
        ];

        foreach ($rows as $row) {
            DB::table('currencies')->updateOrInsert(
                ['country_code' => $row['country_code']],
                array_merge($row, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    private function seedProductsAndListings(): array
    {
        $catalog = [];

        for ($i = 1; $i <= 30; $i++) {
            $sku = sprintf('DEMO-SKU-%03d', $i);
            $asin = sprintf('B0DEM%05d', $i);
            $shortName = sprintf('Demo Product %02d', $i);

            $productId = DB::table('products')->where('sku', $sku)->value('id');
            if (!$productId) {
                $productId = DB::table('products')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'sku' => $sku,
                    'short_title' => $shortName,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('product_asins')->updateOrInsert(
                [
                    'product_id' => $productId,
                    'asin1'      => $asin,
                ],
                [
                    'catalog_item_status' => 1,
                    'updated_at'          => now(),
                    'created_at'          => now(),
                ]
            );

            $listingsByCountry = [];

            foreach (self::COUNTRIES as $country) {
                $listing = DB::table('product_listings')
                    ->where('products_id', $productId)
                    ->where('country', $country['code'])
                    ->select('id')
                    ->first();

                if (!$listing) {
                    $listingId = DB::table('product_listings')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'products_id' => $productId,
                        'translator' => 'demo-bot',
                        'title_amazon' => $shortName . ' ' . $country['code'],
                        'description' => 'Demo generated product listing.',
                        'search_terms' => 'demo,seed,data',
                        'advertising_keywords' => 'demo keyword set',
                        'country' => $country['code'],
                        'product_category' => 'Demo',
                        'progress_status' => 1,
                        'disc_status' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $listingId = $listing->id;
                }

                DB::table('product_categorisations')->updateOrInsert(
                    [
                        'child_asin' => $asin,
                        'marketplace' => $country['code'],
                    ],
                    [
                        'parent_short_name' => 'Demo Parent',
                        'child_short_name' => $shortName,
                        'parent_asin' => 'PARENT-' . $asin,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $listingsByCountry[$country['code']] = $listingId;
            }

            $catalog[] = [
                'sku' => $sku,
                'asin' => $asin,
                'short_name' => $shortName,
                'product_id' => $productId,
                'listings' => $listingsByCountry,
            ];
        }

        return $catalog;
    }

    private function seedCampaignsAndAdsProducts(array $productCatalog): array
    {
        $campaigns = [];

        foreach (self::CAMPAIGN_NAMES as $index => $campaignName) {
            $seq = $index + 1;
            $country = self::COUNTRIES[$index % count(self::COUNTRIES)]['code'];
            $type = ['SP', 'SB', 'SD'][$index % 3];
            $campaignId = 770000 + $seq;
            $product = $productCatalog[$index % count($productCatalog)];
            $targetingType = $seq % 2 === 0 ? 'MANUAL' : 'AUTO';

            if ($type === 'SP') {
                DB::table('amz_campaigns')->updateOrInsert(
                    ['campaign_id' => $campaignId],
                    [
                        'country' => $country,
                        'campaign_name' => $campaignName,
                        'campaign_type' => 'sponsoredProducts',
                        'targeting_type' => $targetingType,
                        'daily_budget' => 50 + $seq,
                        'start_date' => now()->subDays(60),
                        'campaign_state' => 'enabled',
                        'added' => now()->subDays(60),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                DB::table('amz_ads_products')->updateOrInsert(
                    ['ad_group_id' => 880000 + $seq],
                    [
                        'campaign_id' => $campaignId,
                        'country' => $country,
                        'ad_id' => 'SP-AD-' . $campaignId,
                        'asin' => $product['asin'],
                        'sku' => $product['sku'],
                        'state' => 'enabled',
                        'added' => now()->subDays(45),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            if ($type === 'SB') {
                DB::table('amz_campaigns_sb')->updateOrInsert(
                    ['campaign_id' => $campaignId],
                    [
                        'country' => $country,
                        'campaign_name' => $campaignName,
                        'campaign_type' => 'sponsoredBrands',
                        'targeting_type' => 'KEYWORD',
                        'daily_budget' => 60 + $seq,
                        'start_date' => now()->subDays(60),
                        'campaign_state' => 'enabled',
                        'added' => now()->subDays(60),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $altProduct = $productCatalog[($index + 1) % count($productCatalog)];

                DB::table('amz_ads_products_sb')->updateOrInsert(
                    ['ad_group_id' => 980000 + $seq],
                    [
                        'campaign_id' => $campaignId,
                        'country' => $country,
                        'ad_id' => 'SB-AD-' . $campaignId,
                        'asin' => $product['asin'],
                        'related_asins' => json_encode([$product['asin'], $altProduct['asin']]),
                        'sku' => $product['sku'],
                        'state' => 'enabled',
                        'added' => now()->subDays(45),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            if ($type === 'SD') {
                DB::table('amz_campaigns_sd')->updateOrInsert(
                    ['campaign_id' => $campaignId],
                    [
                        'country' => $country,
                        'campaign_name' => $campaignName,
                        'campaign_type' => 'sponsoredDisplay',
                        'targeting_type' => 'PRODUCT',
                        'daily_budget' => 40 + $seq,
                        'start_date' => now()->subDays(60)->toDateString(),
                        'campaign_state' => 'enabled',
                        'added' => now()->subDays(60)->toDateString(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                DB::table('amz_ads_products_sd')->updateOrInsert(
                    [
                        'campaign_id' => $campaignId,
                        'ad_group_id' => 1080000 + $seq,
                        'asin' => $product['asin'],
                    ],
                    [
                        'country' => $country,
                        'ad_id' => 1180000 + $seq,
                        'state' => 'enabled',
                        'ad_name' => 'SD Ad ' . $campaignId,
                        'sku' => $product['sku'],
                        'landing_page_url' => 'https://example.com/demo/' . strtolower($product['sku']),
                        'landing_page_type' => 'productDetailPage',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $campaigns[] = [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'country' => $country,
                'type' => $type,
                'targeting_type' => $targetingType,
                'product' => $product,
            ];
        }

        $this->seedKeywordEntities($campaigns);

        return $campaigns;
    }

    private function seedWarehouseAndInventoryData(Carbon $startDate, Carbon $endDate, array $productCatalog): void
    {
        $warehouses = [
            1 => ['name' => 'Ship Out', 'location' => 'US'],
            2 => ['name' => 'Tactical', 'location' => 'US'],
            3 => ['name' => 'AFD', 'location' => 'US'],
        ];

        foreach ($warehouses as $id => $meta) {
            DB::table('warehouses')->updateOrInsert(
                ['id' => $id],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_name' => $meta['name'],
                    'location' => $meta['location'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $shipmentId = 'DEMO-SHIP-' . $cursor->format('Ymd');

            DB::table('inbound_shipment_sps')->updateOrInsert(
                [
                    'shipment_id' => $shipmentId,
                    'add_date' => $cursor->toDateString(),
                ],
                [
                    'ship_status' => 'RECEIVING',
                    'ship_arrival_date' => $cursor->copy()->addDays(4)->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            foreach (array_slice($productCatalog, 0, 20) as $idx => $product) {
                $shipQty = 20 + (($idx + (int) $cursor->format('d')) % 40);
                $receivedQty = max(0, $shipQty - (($idx + (int) $cursor->format('d')) % 7));

                DB::table('inbound_shipment_details_sps')->updateOrInsert(
                    [
                        'sku' => $product['sku'],
                        'ship_id' => $shipmentId,
                        'add_date' => $cursor->toDateString(),
                    ],
                    [
                        'qty_ship' => $shipQty,
                        'qty_received' => $receivedQty,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $cursor->addDay();
        }

        foreach ($productCatalog as $idx => $product) {
            DB::table('fba_inventory_usa')->updateOrInsert(
                [
                    'sku' => $product['sku'],
                    'country' => 'US',
                ],
                [
                    'asin' => $product['asin'],
                    'instock' => 60 + ($idx % 90),
                    'totalstock' => 90 + ($idx % 120),
                    'reserve_stock' => 10 + ($idx % 20),
                    'add_date' => $endDate->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('afn_inventory_data')->updateOrInsert(
                [
                    'seller_sku' => $product['sku'],
                    'asin' => $product['asin'],
                ],
                [
                    'fulfillment_channel_sku' => $product['sku'] . '-FC',
                    'condition_type' => 'NewItem',
                    'warehouse_condition_code' => 'SELLABLE',
                    'quantity_available' => (string) (55 + ($idx % 80)),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            foreach ([1, 2, 3] as $warehouseId) {
                $quantity = 25 + (($idx * 3 + $warehouseId) % 70);
                $reserved = min(8, $quantity);

                DB::table('product_wh_inventory')->updateOrInsert(
                    [
                        'product_id' => $product['product_id'],
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'quantity' => $quantity,
                        'reserved_quantity' => $reserved,
                        'available_quantity' => $quantity - $reserved,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function seedKeywordEntities(array $campaigns): void
    {
        foreach ($campaigns as $campaign) {
            if (!in_array($campaign['type'], ['SP', 'SB'], true)) {
                continue;
            }

            for ($k = 0; $k < 3; $k++) {
                $kwIndex = (($campaign['campaign_id'] + $k) % count(self::KEYWORDS));
                $keywordText = self::KEYWORDS[$kwIndex];
                $keywordId = sprintf('%s-KW-%d-%02d', $campaign['type'], $campaign['campaign_id'], $k + 1);

                if ($campaign['type'] === 'SP') {
                    DB::table('amz_ads_keywords')->updateOrInsert(
                        ['keyword_id' => $keywordId],
                        [
                            'campaign_id' => $campaign['campaign_id'],
                            'country' => $campaign['country'],
                            'ad_group_id' => 'SP-G-' . $campaign['campaign_id'],
                            'keyword_text' => $keywordText,
                            'match_type' => 'BROAD',
                            'state' => 'enabled',
                            'bid' => 0.60 + ($k * 0.1),
                            'added' => now()->subDays(30),
                            'updated' => now(),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                if ($campaign['type'] === 'SB') {
                    DB::table('amz_ads_keyword_sb')->updateOrInsert(
                        ['keyword_id' => $keywordId],
                        [
                            'campaign_id' => $campaign['campaign_id'],
                            'country' => $campaign['country'],
                            'ad_group_id' => 'SB-G-' . $campaign['campaign_id'],
                            'keyword_text' => $keywordText,
                            'match_type' => 'BROAD',
                            'state' => 'enabled',
                            'bid' => 0.70 + ($k * 0.1),
                            'added' => now()->subDays(30),
                            'updated' => now(),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    private function seedSalesFacts(Carbon $startDate, Carbon $endDate, array $productCatalog): void
    {
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            foreach ($productCatalog as $idx => $product) {
                foreach (self::COUNTRIES as $country) {
                    $units = 5 + (($idx + (int) $cursor->format('d')) % 21);
                    $unitPrice = 18 + (($idx % 10) * 3);
                    $revenue = round($units * $unitPrice, 2);
                    $cost = round($revenue * 0.62, 2);
                    $profit = round($revenue - $cost, 2);

                    DB::table('daily_sales')->updateOrInsert(
                        [
                            'sku' => $product['sku'],
                            'asin' => $product['asin'],
                            'marketplace_id' => $country['marketplace'],
                            'sale_date' => $cursor->toDateString(),
                        ],
                        [
                            'product_listings_id' => $product['listings'][$country['code']] ?? null,
                            'sale_datetime' => $cursor->copy()->setHour(12)->setMinute(0)->setSecond(0),
                            'total_units' => $units,
                            'total_revenue' => $revenue,
                            'total_cost' => $cost,
                            'total_profit' => $profit,
                            'currency' => $country['currency'],
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    $weekKey = $cursor->copy()->startOfWeek()->format('o-W');
                    DB::table('weekly_sales')->updateOrInsert(
                        [
                            'sku' => $product['sku'],
                            'asin' => $product['asin'],
                            'marketplace_id' => $country['marketplace'],
                            'week_number' => $weekKey,
                            'sale_date' => $cursor->copy()->startOfWeek()->toDateString(),
                        ],
                        [
                            'product_listings_id' => $product['listings'][$country['code']] ?? null,
                            'total_units' => $units * 7,
                            'total_revenue' => round($revenue * 7, 2),
                            'total_cost' => round($cost * 7, 2),
                            'total_profit' => round($profit * 7, 2),
                            'currency' => $country['currency'],
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    DB::table('monthly_sales')->updateOrInsert(
                        [
                            'sku' => $product['sku'],
                            'asin' => $product['asin'],
                            'marketplace_id' => $country['marketplace'],
                            'sale_date' => $cursor->copy()->startOfMonth()->toDateString(),
                        ],
                        [
                            'product_listings_id' => $product['listings'][$country['code']] ?? null,
                            'total_units' => $units * 30,
                            'total_revenue' => round($revenue * 30, 2),
                            'total_cost' => round($cost * 30, 2),
                            'total_profit' => round($profit * 30, 2),
                            'currency' => $country['currency'],
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            $cursor->addDay();
        }

        $this->seedTodayHourlySales($productCatalog);
    }

    private function seedTodayHourlySales(array $productCatalog): void
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');
        $today = now($marketTz)->startOfDay();

        // Yesterday full-day (0..23) for comparison charts.
        $this->seedHourlySalesForDay($productCatalog, $today->copy()->subDay(), 23);

        // Today half-day (0..11) to simulate in-progress day.
        $this->seedHourlySalesForDay($productCatalog, $today, 11);
    }

    private function seedHourlySalesForDay(array $productCatalog, Carbon $dayStart, int $lastHour): void
    {
        $dateKey = $dayStart->toDateString();

        for ($hour = 0; $hour <= $lastHour; $hour++) {
            $saleHour = $dayStart->copy()->addHours($hour);
            $hourFactor = $this->hourlyDemandFactor($hour);

            foreach (array_slice($productCatalog, 0, 18) as $idx => $product) {
                $seed = crc32($product['sku'] . '|' . $dateKey . '|' . $hour);
                $noise = (($seed % 37) - 18) / 100; // -0.18 to +0.18

                $baseUnits = 2 + ($idx % 6);
                $units = (int) round($baseUnits * $hourFactor * (1 + $noise));
                $units = max(0, $units);

                $basePrice = 19 + ($idx % 7) * 2;
                $priceJitter = 1 + ((($seed % 11) - 5) / 100); // -5% to +5%
                $price = round($basePrice * $priceJitter, 2);

                DB::table('hourly_product_sales')->updateOrInsert(
                    [
                        'sku' => $product['sku'],
                        'sales_channel' => 'Amazon.com',
                        'sale_hour' => $saleHour,
                    ],
                    [
                        'asin' => $product['asin'],
                        'purchase_date' => $saleHour,
                        'total_units' => $units,
                        'item_price' => $price,
                        'currency' => 'USD',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function hourlyDemandFactor(int $hour): float
    {
        return match (true) {
            $hour <= 5 => 0.45,   // Low late-night volume
            $hour <= 9 => 1.05,   // Morning ramp
            $hour <= 13 => 0.85,  // Midday soft dip
            $hour <= 17 => 1.10,  // Afternoon recovery
            $hour <= 21 => 1.35,  // Evening peak
            default => 0.75,      // Wind-down
        };
    }

    private function seedMonthlyAdsProductPerformance(Carbon $startDate, Carbon $endDate, array $productCatalog): void
    {
        $months = [];
        $cursor = $startDate->copy()->startOfMonth();

        while ($cursor->lte($endDate)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        foreach ($months as $month) {
            foreach (array_slice($productCatalog, 0, 20) as $idx => $product) {
                $sold = 160 + ($idx * 4);
                $revenue = $sold * (22 + ($idx % 4));
                $adSpend = round($revenue * 0.18, 2);
                $adSales = round($revenue * 0.65, 2);

                DB::table('monthly_ads_product_performances')->updateOrInsert(
                    [
                        'sku' => $product['sku'],
                        'asin' => $product['asin'],
                        'month' => $month->toDateString(),
                    ],
                    [
                        'sold' => $sold,
                        'revenue' => $revenue,
                        'ad_spend' => $adSpend,
                        'ad_sales' => $adSales,
                        'acos' => $adSales > 0 ? round(($adSpend / $adSales) * 100, 2) : 0,
                        'tacos' => $revenue > 0 ? round(($adSpend / $revenue) * 100, 2) : 0,
                        'ad_units' => (int) round($sold * 0.55),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function seedAdsPerformanceFacts(Carbon $startDate, Carbon $endDate, array $campaignCatalog, array $productCatalog): void
    {
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            foreach ($campaignCatalog as $idx => $campaign) {
                $product = $campaign['product'];
                $baseSpend = 22 + (($idx + (int) $cursor->format('d')) % 40);
                $baseSales = $baseSpend * (2.6 + (($idx % 4) * 0.4));
                $clicks = 40 + (($idx + (int) $cursor->format('d')) % 70);
                $purchases = max(1, (int) round($clicks * 0.12));

                if ($campaign['type'] === 'SP') {
                    DB::table('amz_ads_campaign_performance_report')->insert([
                        'campaign_id' => $campaign['campaign_id'],
                        'ad_group_id' => 880000 + $idx + 1,
                        'cost' => round($baseSpend, 2),
                        'sales1d' => round($baseSales * 0.4, 2),
                        'sales7d' => round($baseSales, 2),
                        'purchases1d' => (int) round($purchases * 0.4),
                        'purchases7d' => $purchases,
                        'clicks' => $clicks,
                        'costPerClick' => round($baseSpend / max(1, $clicks), 2),
                        'c_budget' => 50 + $idx,
                        'c_currency' => 'USD',
                        'c_status' => 'enabled',
                        'c_date' => $cursor->copy()->setHour(11)->setMinute(0)->setSecond(0),
                        'country' => $campaign['country'],
                        'added' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('amz_ads_product_performance_report')->insert([
                        'campaign_id' => $campaign['campaign_id'],
                        'ad_group_id' => 880000 + $idx + 1,
                        'ad_id' => 990000 + $idx + 1,
                        'cost' => round($baseSpend * 0.85, 2),
                        'sales1d' => round($baseSales * 0.35, 2),
                        'sales7d' => round($baseSales * 0.9, 2),
                        'sales30d' => round($baseSales * 1.5, 2),
                        'purchases1d' => (int) round($purchases * 0.3),
                        'purchases7d' => $purchases,
                        'purchases30d' => (int) round($purchases * 1.6),
                        'clicks' => $clicks,
                        'impressions' => $clicks * 20,
                        'sku' => $product['sku'],
                        'asin' => $product['asin'],
                        'c_date' => $cursor->copy()->setHour(11)->setMinute(0)->setSecond(0),
                        'country' => $campaign['country'],
                        'added' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($campaign['type'] === 'SB') {
                    DB::table('amz_ads_campaign_performance_reports_sb')->insert([
                        'campaign_id' => $campaign['campaign_id'],
                        'impressions' => $clicks * 25,
                        'clicks' => $clicks,
                        'unitsSold' => (int) round($purchases * 1.1),
                        'purchases' => $purchases,
                        'cost' => round($baseSpend * 1.05, 2),
                        'c_budget' => 60 + $idx,
                        'c_currency' => 'USD',
                        'c_status' => 'enabled',
                        'sales' => round($baseSales * 1.1, 2),
                        'date' => $cursor->copy()->setHour(10)->setMinute(30)->setSecond(0),
                        'country' => $campaign['country'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($campaign['type'] === 'SD') {
                    DB::table('amz_ads_campaign_performance_report_sd')->insert([
                        'campaign_id' => (string) $campaign['campaign_id'],
                        'campaign_status' => 'enabled',
                        'campaign_budget_amount' => 45 + $idx,
                        'campaign_budget_currency_code' => 'USD',
                        'impressions' => $clicks * 22,
                        'clicks' => $clicks,
                        'cost' => round($baseSpend * 0.95, 2),
                        'sales' => round($baseSales * 0.92, 2),
                        'purchases' => $purchases,
                        'units_sold' => (int) round($purchases * 1.05),
                        'c_date' => $cursor->toDateString(),
                        'country' => $campaign['country'],
                        'added' => $cursor->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $cursor->addDay();
        }
    }

    private function seedCampaignRecommendations(Carbon $startDate, Carbon $endDate, array $campaignCatalog): void
    {
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            foreach ($campaignCatalog as $idx => $campaign) {
                $spend1d = 18 + (($idx + (int) $cursor->format('d')) % 36);
                $sales1d = $spend1d * (2.4 + (($idx % 3) * 0.4));
                $purchases1d = 6 + ($idx % 8);

                $spend7d = $spend1d * 6.7;
                $sales7d = $sales1d * 6.9;
                $purchases7d = (int) round($purchases1d * 7.0);

                $spend14d = $spend7d * 1.8;
                $sales14d = $sales7d * 1.9;
                $purchases14d = (int) round($purchases7d * 1.85);

                $spend30d = $spend7d * 4.1;
                $sales30d = $sales7d * 4.0;
                $purchases30d = (int) round($purchases7d * 4.1);

                $acos1d = $sales1d > 0 ? round(($spend1d / $sales1d) * 100, 2) : 0;
                $acos7d = $sales7d > 0 ? round(($spend7d / $sales7d) * 100, 2) : 0;
                $acos14d = $sales14d > 0 ? round(($spend14d / $sales14d) * 100, 2) : 0;
                $acos30d = $sales30d > 0 ? round(($spend30d / $sales30d) * 100, 2) : 0;

                DB::table('campaign_recommendations')->updateOrInsert(
                    [
                        'campaign_id' => (string) $campaign['campaign_id'],
                        'report_week' => $cursor->toDateString(),
                        'campaign_types' => $campaign['type'],
                    ],
                    [
                        'campaign_name' => $campaign['campaign_name'],
                        'enabled_campaigns_count' => 1,
                        'country' => $campaign['country'],
                        'from_group' => 1 + ($idx % 3),
                        'to_group' => 1 + (($idx + 1) % 3),
                        'total_daily_budget' => 35 + $idx,
                        'total_spend' => round($spend1d, 2),
                        'total_sales' => round($sales1d, 2),
                        'purchases7d' => $purchases1d,
                        'acos' => $acos1d,
                        'total_spend_7d' => round($spend7d, 2),
                        'total_sales_7d' => round($sales7d, 2),
                        'purchases7d_7d' => $purchases7d,
                        'acos_7d' => $acos7d,
                        'total_spend_14d' => round($spend14d, 2),
                        'total_sales_14d' => round($sales14d, 2),
                        'purchases7d_14d' => $purchases14d,
                        'acos_14d' => $acos14d,
                        'total_spend_30d' => round($spend30d, 2),
                        'total_sales_30d' => round($sales30d, 2),
                        'purchases7d_30d' => $purchases30d,
                        'acos_30d' => $acos30d,
                        'suggested_budget' => (string) round($spend1d * 1.25, 2),
                        'manual_budget' => round($spend1d * 1.05, 2),
                        'old_budget' => round($spend1d * 0.95, 2),
                        'run_update' => 0,
                        'run_status' => 'done',
                        'ai_recommendation' => 'Maintain budget and scale top ASIN traffic.',
                        'ai_status' => 'ready',
                        'ai_suggested_budget' => (string) round($spend1d * 1.20, 2),
                        'recommendation' => $acos7d < 30 ? 'Scale budget 10%' : 'Reduce spend 10%',
                        'rule_applied' => $acos7d < 30 ? 'increase_budget' : 'decrease_budget',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $cursor->addDay();
        }
    }

    private function seedKeywordRecommendations(Carbon $startDate, Carbon $endDate, array $campaignCatalog): void
    {
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            foreach ($campaignCatalog as $campaignIdx => $campaign) {
                if (!in_array($campaign['type'], ['SP', 'SB'], true)) {
                    continue;
                }

                for ($k = 0; $k < 3; $k++) {
                    $kwIndex = (($campaignIdx * 3) + $k) % count(self::KEYWORDS);
                    $keyword = self::KEYWORDS[$kwIndex];
                    $keywordId = sprintf('%s-KW-%d-%02d', $campaign['type'], $campaign['campaign_id'], $k + 1);

                    $clicks = 12 + (($campaignIdx + $k + (int) $cursor->format('d')) % 35);
                    $impressions = $clicks * (12 + $k);
                    $bid = round(0.65 + ($k * 0.08), 2);
                    $spend1d = round($clicks * $bid * 0.9, 2);
                    $sales1d = round($spend1d * (2.3 + ($k * 0.35)), 2);
                    $purchases1d = max(1, (int) round($clicks * 0.16));

                    $spend7d = round($spend1d * 6.6, 2);
                    $sales7d = round($sales1d * 6.7, 2);
                    $purchases7d = (int) round($purchases1d * 6.8);

                    $spend14d = round($spend7d * 1.85, 2);
                    $sales14d = round($sales7d * 1.9, 2);
                    $purchases14d = (int) round($purchases7d * 1.85);

                    $spend30d = round($spend7d * 4.0, 2);
                    $sales30d = round($sales7d * 4.1, 2);
                    $purchases30d = (int) round($purchases7d * 4.0);

                    $acos1d = $sales1d > 0 ? round(($spend1d / $sales1d) * 100, 2) : 0;
                    $acos7d = $sales7d > 0 ? round(($spend7d / $sales7d) * 100, 2) : 0;
                    $acos14d = $sales14d > 0 ? round(($spend14d / $sales14d) * 100, 2) : 0;
                    $acos30d = $sales30d > 0 ? round(($spend30d / $sales30d) * 100, 2) : 0;

                    DB::table('amz_keyword_recommendations')->updateOrInsert(
                        [
                            'keyword_id' => $keywordId,
                            'date' => $cursor->toDateString(),
                            'country' => $campaign['country'],
                            'campaign_types' => $campaign['type'],
                            'campaign_id' => (string) $campaign['campaign_id'],
                        ],
                        [
                            'keyword' => $keyword,
                            'clicks' => $clicks,
                            'cpc' => $clicks > 0 ? round($spend1d / $clicks, 2) : 0,
                            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                            'bid' => $bid,
                            'conversion_rate' => $clicks > 0 ? round(($purchases1d / $clicks) * 100, 2) : 0,
                            'clicks_7d' => (int) round($clicks * 6.8),
                            'cpc_7d' => round(($spend7d / max(1, (int) round($clicks * 6.8))), 2),
                            'ctr_7d' => round(($clicks / max(1, $impressions)) * 100, 2),
                            'conversion_rate_7d' => round(($purchases7d / max(1, (int) round($clicks * 6.8))) * 100, 2),
                            'impressions' => (string) $impressions,
                            'impressions_7d' => (string) ($impressions * 7),
                            'total_spend' => $spend1d,
                            'total_sales' => $sales1d,
                            'purchases1d' => $purchases1d,
                            'acos' => $acos1d,
                            'total_spend_7d' => $spend7d,
                            'total_sales_7d' => $sales7d,
                            'purchases1d_7d' => $purchases7d,
                            'acos_7d' => $acos7d,
                            'total_spend_14d' => $spend14d,
                            'total_sales_14d' => $sales14d,
                            'purchases1d_14d' => $purchases14d,
                            'acos_14d' => $acos14d,
                            'total_spend_30d' => $spend30d,
                            'total_sales_30d' => $sales30d,
                            'purchases7d_30d' => $purchases30d,
                            'acos_30d' => $acos30d,
                            'recommendation' => $acos7d < 30 ? 'Increase bid 5%' : 'Decrease bid 8%',
                            'suggested_bid' => (string) round($bid * ($acos7d < 30 ? 1.05 : 0.92), 2),
                            'manual_bid' => round($bid * 1.02, 2),
                            'old_bid' => round($bid * 0.95, 2),
                            'run_update' => 0,
                            'run_status' => 'done',
                            'ai_suggested_bid' => (string) round($bid * 1.04, 2),
                            'ai_recommendation' => 'Keep keyword active and adjust bid based on ACOS trend.',
                            'ai_status' => 'ready',
                            's_bid_min' => round($bid * 0.85, 2),
                            's_bid_range' => round($bid, 2),
                            's_bid_max' => round($bid * 1.20, 2),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            $cursor->addDay();
        }
    }

    private function seedSearchTermSummaryReports(Carbon $startDate, Carbon $endDate, array $campaignCatalog): void
    {
        $spCampaigns = array_values(array_filter($campaignCatalog, fn($c) => ($c['type'] ?? null) === 'SP'));
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            foreach ($spCampaigns as $idx => $campaign) {
                for ($k = 0; $k < 3; $k++) {
                    $keyword = self::KEYWORDS[(($idx * 3) + $k) % count(self::KEYWORDS)];
                    $searchTerm = $k === 0
                        ? $keyword
                        : $keyword . ' ' . ['best', 'new', 'bundle'][$k - 1];

                    $clicks = 10 + (($idx + $k + (int) $cursor->format('d')) % 30);
                    $impressions = $clicks * (11 + $k);
                    $cost = round($clicks * (0.55 + $k * 0.1), 2);
                    $sales1d = round($cost * (2.2 + ($k * 0.4)), 2);

                    DB::table('sp_search_term_summary_reports')->insert([
                        'campaign_id' => $campaign['campaign_id'],
                        'ad_group_id' => 880000 + $idx + 1,
                        'keyword_id' => (int) (($campaign['campaign_id'] * 10) + $k + 1),
                        'country' => $campaign['country'],
                        'date' => $cursor->toDateString(),
                        'keyword' => $keyword,
                        'search_term' => $searchTerm,
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'cost_per_click' => $clicks > 0 ? round($cost / $clicks, 4) : 0,
                        'cost' => $cost,
                        'purchases_1d' => max(1, (int) round($clicks * 0.12)),
                        'purchases_7d' => max(1, (int) round($clicks * 0.9)),
                        'purchases_14d' => max(1, (int) round($clicks * 1.8)),
                        'sales_1d' => $sales1d,
                        'sales_7d' => round($sales1d * 6.5, 2),
                        'sales_14d' => round($sales1d * 12.8, 2),
                        'campaign_budget_amount' => 40 + $idx,
                        'keyword_bid' => round(0.65 + ($k * 0.08), 2),
                        'keyword_type' => 'BROAD',
                        'match_type' => 'BROAD',
                        'targeting' => 'manual',
                        'ad_keyword_status' => 'enabled',
                        'start_date' => $cursor->copy()->subDays(14)->toDateString(),
                        'end_date' => $cursor->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $cursor->addDay();
        }
    }

    private function cleanupDemoRows(Carbon $startDate, Carbon $endDate, array $campaignCatalog): void
    {
        $campaignIds = array_column($campaignCatalog, 'campaign_id');

        DB::table('daily_sales')
            ->where('sku', 'like', 'DEMO-SKU-%')
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('weekly_sales')->where('sku', 'like', 'DEMO-SKU-%')->delete();
        DB::table('monthly_sales')->where('sku', 'like', 'DEMO-SKU-%')->delete();
        DB::table('hourly_product_sales')->where('sku', 'like', 'DEMO-SKU-%')->delete();
        DB::table('monthly_ads_product_performances')->where('sku', 'like', 'DEMO-SKU-%')->delete();
        DB::table('fba_inventory_usa')->where('sku', 'like', 'DEMO-SKU-%')->delete();
        DB::table('afn_inventory_data')->where('seller_sku', 'like', 'DEMO-SKU-%')->delete();
        DB::table('product_wh_inventory')->whereIn('product_id', DB::table('products')->where('sku', 'like', 'DEMO-SKU-%')->pluck('id'))->delete();
        DB::table('inbound_shipment_details_sps')->where('ship_id', 'like', 'DEMO-SHIP-%')->delete();
        DB::table('inbound_shipment_sps')->where('shipment_id', 'like', 'DEMO-SHIP-%')->delete();

        DB::table('campaign_recommendations')
            ->whereIn('campaign_id', array_map('strval', $campaignIds))
            ->whereBetween('report_week', [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('amz_keyword_recommendations')
            ->whereIn('campaign_id', array_map('strval', $campaignIds))
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('amz_ads_campaign_performance_report')
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween(DB::raw('DATE(c_date)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('amz_ads_campaign_performance_reports_sb')
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween(DB::raw('DATE(date)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('amz_ads_campaign_performance_report_sd')
            ->whereIn('campaign_id', array_map('strval', $campaignIds))
            ->whereBetween('c_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('amz_ads_product_performance_report')
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween(DB::raw('DATE(c_date)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();

        DB::table('sp_search_term_summary_reports')
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->delete();
    }
}
