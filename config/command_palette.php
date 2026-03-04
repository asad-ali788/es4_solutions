<?php

return [

    'items' => [

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'dashboard.view',
            'module' => 'Dashboard',
            'label' => 'Dashboard',
            'keywords' => ['dashboard', 'home', 'charts', 'hourly', 'monthly', 'weekly', 'yesterday', 'today', 'top'],
            'url' => '/admin/dashboard',
            'permission' => 'dashboard.view',
            'order' => 10,
        ],

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'profile.view',
            'module' => 'Profile',
            'label' => 'My Profile',
            'keywords' => ['profile', 'account', 'my profile', 'settings', 'password', 'change password'],
            'url' => '/admin/profile',
            'permission' => 'profile.view',
            'order' => 20,
        ],

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'products.list',
            'module' => 'Products',
            'label' => 'Products',
            'keywords' => ['products', 'product list', 'inventory'],
            'url' => '/admin/products',
            'permission' => 'products.view',
            'order' => 30,
        ],

        /*
        |--------------------------------------------------------------------------
        | Selling (SKU based)
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'selling.sku',
            'module' => 'Selling',
            'label' => 'Selling (SKU)',
            'keywords' => [
                'selling',
                'sku selling',
                'selling sku',
                'sales by sku',
                'sku performance'
            ],
            'url' => '/admin/selling',
            'permission' => 'selling.view',
            'order' => 40,
        ],

        /*
        |--------------------------------------------------------------------------
        | Selling (ASIN based)
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'selling.asin',
            'module' => 'Selling',
            'label' => 'Selling (ASIN)',
            'keywords' => [
                'asin selling',
                'selling asin',
                'asin performance',
                'sales by asin'
            ],
            'url' => '/admin/asin-selling',
            'permission' => 'selling.view',
            'order' => 50,
        ], /*
        |--------------------------------------------------------------------------
        | Selling Ads Item (ASIN level)
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'selling_ads_item.overview',
            'module' => 'Selling',
            'label' => 'Ads Item (ASIN)',
            'keywords' => ['ads item', 'asin ads', 'selling ads asin', 'ads overview asin'],
            'url' => '/admin/sellingAdsItem',
            'permission' => 'selling.ads.view',
            'order' => 100,
        ],

        /*
        |--------------------------------------------------------------------------
        | Sourcing
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'sourcing.view',
            'module' => 'Sourcing',
            'label' => 'Sourcing',
            'keywords' => ['sourcing', 'product sourcing', 'vendors'],
            'url' => '/admin/sourcing',
            'permission' => 'sourcing.view',
            'order' => 130,
        ],

        /*
        |--------------------------------------------------------------------------
        | Order Forecast
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'order_forecast.view',
            'module' => 'Forecast',
            'label' => 'Order Forecast',
            'keywords' => ['order forecast', 'forecast', 'sales forecast', 'ai forecast'],
            'url' => '/admin/orderforecast',
            'permission' => 'forecast.view',
            'order' => 140,
        ],

        /*
        |--------------------------------------------------------------------------
        | Stocks (SKU)
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'stocks.sku',
            'module' => 'Stocks',
            'label' => 'Stocks (SKU)',
            'keywords' => ['stock sku', 'sku stock', 'inventory sku', 'fba', 'afn', 'shipout', "tactical"],
            'url' => '/admin/stocks/sku',
            'permission' => 'stocks.view',
            'order' => 150,
        ],

        /*
        |--------------------------------------------------------------------------
        | Stocks (ASIN)
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'stocks.asin',
            'module' => 'Stocks',
            'label' => 'Stocks (ASIN)',
            'keywords' => ['stock asin', 'asin stock', 'inventory asin', 'fba', 'afn', 'shipout', "tactical"],
            'url' => '/admin/stocks/asin',
            'permission' => 'stocks.view',
            'order' => 160,
        ],

        /*
        |--------------------------------------------------------------------------
        | Warehouse
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'warehouse.view',
            'module' => 'Warehouse',
            'label' => 'Warehouse',
            'keywords' => ['warehouse', 'inventory warehouse', 'tactical', 'shipout', 'aws', 'stocks'],
            'url' => '/admin/warehouse',
            'permission' => 'warehouse.view',
            'order' => 170,
        ],
        [
            'id' => 'warehouse.all_inventory',
            'module' => 'Warehouse',
            'label' => 'All Warehouse Stocks',
            'keywords' => ['all warehouse stocks', 'detailed stock', 'warehouse detailed', 'tactical', 'shipout', 'aws'],
            'url' => '/admin/warehouse/allWarehouseInventory',
            'permission' => 'warehouse.view',
            'order' => 180,
        ],

        /*
        |--------------------------------------------------------------------------
        | Shipments
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'shipments.view',
            'module' => 'Shipments',
            'label' => 'Shipments',
            'keywords' => ['shipments', 'inbound shipments', 'outbound shipments'],
            'url' => '/admin/shipments',
            'permission' => 'shipments.view',
            'order' => 290,
        ],

        /*
        |--------------------------------------------------------------------------
        | Ads Overview (ASIN level)
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'ads.overview',
            'module' => 'Amazon Ads',
            'label' => 'Ads Overview Campaign',
            'keywords' => ['ads overview', 'asin ads', 'ads performance asin'],
            'url' => '/admin/ads/overview',
            'permission' => 'ads.view',
            'order' => 50,
        ],
        [
            'id' => 'ads.overview.keywords',
            'module' => 'Amazon Ads',
            'label' => 'Ads Overview Keywords',
            'keywords' => ['ads overview', 'asin ads', 'ads performance asin','keyword','keyword performance'],
            'url' => '/admin/ads/overview/keywordDashboard',
            'permission' => 'ads.view',
            'order' => 55,
        ],

        /*
        |--------------------------------------------------------------------------
        | Ads Campaigns
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'ads.campaigns.sp',
            'module' => 'Amazon Ads',
            'label' => 'Campaigns (SP)',
            'keywords' => ['sp campaigns', 'sponsored products', 'ads campaigns sp'],
            'url' => '/admin/ads/campaigns',
            'permission' => 'ads.view',
            'order' => 210,
        ],
        [
            'id' => 'ads.campaigns.sb',
            'module' => 'Amazon Ads',
            'label' => 'Campaigns (SB)',
            'keywords' => ['sb campaigns', 'sponsored brands'],
            'url' => '/admin/ads/campaignsSb',
            'permission' => 'ads.view',
            'order' => 220,
        ],
        [
            'id' => 'ads.campaigns.sd',
            'module' => 'Amazon Ads',
            'label' => 'Campaigns (SD)',
            'keywords' => ['sd campaigns', 'sponsored display'],
            'url' => '/admin/ads/campaignsSd',
            'permission' => 'ads.view',
            'order' => 230,
        ],
        [
            'id' => 'ads.campaigns.create.sp',
            'module' => 'Amazon Ads',
            'label' => 'Create Campaigns (SP)',
            'keywords' => ['campaign create', 'create','manual','auto'],
            'url' => '/admin/ads/campaigns/create?asin=',
            'permission' => 'ads.view',
            'order' => 230,
        ],
        /*
        |--------------------------------------------------------------------------
        | Ads Keywords & Targets
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'ads.keywords.sp',
            'module' => 'Amazon Ads',
            'label' => 'Keywords (SP)',
            'keywords' => ['keywords sp', 'sp keywords'],
            'url' => '/admin/ads/keywords',
            'permission' => 'ads.view',
            'order' => 240,
        ],
        [
            'id' => 'ads.keywords.sb',
            'module' => 'Amazon Ads',
            'label' => 'Keywords (SB)',
            'keywords' => ['keywords sb', 'sb keywords'],
            'url' => '/admin/ads/keywordsSb',
            'permission' => 'ads.view',
            'order' => 250,
        ],
        [
            'id' => 'ads.keyword.campaign_asins_sp',
            'module' => 'Amazon Ads',
            'label' => 'Campaign ASIN Keywords (SP)',
            'keywords' => ['campaign asin sp', 'asin keyword sp'],
            'url' => '/admin/ads/keyword/campaignAsinsSp',
            'permission' => 'ads.view',
            'order' => 260,
        ],
        [
            'id' => 'ads.keyword.campaign_asins_sb',
            'module' => 'Amazon Ads',
            'label' => 'Campaign ASIN Keywords (SB)',
            'keywords' => ['campaign asin sb', 'asin keyword sb'],
            'url' => '/admin/ads/keyword/campaignAsinsSb',
            'permission' => 'ads.view',
            'order' => 270,
        ],
        [
            'id' => 'ads.targets.sd',
            'module' => 'Amazon Ads',
            'label' => 'Targets (SD)',
            'keywords' => ['targets sd', 'sponsored display targets'],
            'url' => '/admin/ads/targetsSd',
            'permission' => 'ads.view',
            'order' => 280,
        ],
        /*
        |--------------------------------------------------------------------------
        | Ads Performance
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'ads.performance.asins',
            'module' => 'Amazon Ads',
            'label' => 'ASIN Performance',
            'keywords' => [
                'asin performance',
                'ads asin performance',
                'asin ads report'
            ],
            'url' => '/admin/ads/performance/asins',
            'permission' => 'ads.view',
            'order' => 290,
        ],
        [
            'id' => 'ads.performance.campaigns',
            'module' => 'Amazon Ads',
            'label' => 'Campaign Performance',
            'keywords' => [
                'campaign performance',
                'ads campaign performance',
                'campaign ads report'
            ],
            'url' => '/admin/ads/performance/campaigns',
            'permission' => 'ads.view',
            'order' => 200,
        ],
        [
            'id' => 'ads.performance.keywords',
            'module' => 'Amazon Ads',
            'label' => 'Keyword Performance',
            'keywords' => [
                'keyword performance',
                'ads keyword performance',
                'search term performance'
            ],
            'url' => '/admin/ads/performance/keywords',
            'permission' => 'ads.view',
            'order' => 210,
        ],

        /*
        |--------------------------------------------------------------------------
        | Ads Schedule
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'ads.schedule.active_campaigns',
            'module' => 'Amazon Ads',
            'label' => 'Active Campaign Schedules',
            'keywords' => [
                'active campaign schedule',
                'ads schedule active',
                'scheduled campaigns'
            ],
            'url' => '/admin/ads/schedule/activeCampaigns',
            'permission' => 'ads.schedule.view',
            'order' => 320,
        ],
        [
            'id' => 'ads.schedule.all',
            'module' => 'Amazon Ads',
            'label' => 'Campaign Schedules',
            'keywords' => [
                'campaign schedule',
                'ads schedule',
                'campaign timing'
            ],
            'url' => '/admin/ads/schedule',
            'permission' => 'ads.schedule.view',
            'order' => 330,
        ],

        /*
        |--------------------------------------------------------------------------
        | Search Terms
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'ads.search_terms',
            'module' => 'Amazon Ads',
            'label' => 'Search Terms',
            'keywords' => [
                'search terms',
                'ads search terms',
                'customer search terms'
            ],
            'url' => '/admin/searchterms',
            'permission' => 'ads.view',
            'order' => 340,
        ],

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'notifications.view',
            'module' => 'System',
            'label' => 'Notifications',
            'keywords' => [
                'notifications',
                'system notifications',
                'stock',
                'stock notifications',
            ],
            'url' => '/admin/notification',
            'permission' => 'notifications.view',
            'order' => 350,
        ],

        /*
        |--------------------------------------------------------------------------
        | Data / System Tools
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'data.tools',
            'module' => 'Data Reports / Exchange',
            'label' => 'Data',
            'keywords' => [
                'data',
                'system data',
                'data tools',
                'export',
                'exchange',
                'currency',
            ],
            'url' => '/admin/data',
            'permission' => 'system.view',
            'order' => 260,
        ],
        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        [
            'id' => 'users.list',
            'module' => 'Users',
            'label' => 'Users',
            'keywords' => ['users', 'user list', 'manage users', 'assign', 'permissions', 'role', 'block', 'admin'],
            'url' => '/admin/users',
            'permission' => 'users.view',
            'order' => 10,
        ],
    ],

];
