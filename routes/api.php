<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\InventoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SpReportController;
use App\Http\Controllers\Api\SpApiController;
use App\Http\Controllers\Api\SpShipmentController;
use App\Http\Controllers\Api\SpProductPricingController;
use App\Http\Controllers\Api\SpFeedsController;
use App\Http\Controllers\Api\SpReportCronApi;
use App\Services\Api\AmazonAdsService;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto\ListingsItemPatchRequest;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto\PatchOperation;

// Route::prefix('spapi')->as('spapi.')->group(function () {

/**
 * 📦 SP Report Routes
 * - Handles report fetching, creation, and documents
 */
Route::controller(SpReportController::class)->group(function () {
    Route::get('/getReports', 'getReports')->name('getReports');
    Route::post('/createReport', 'createReport')->name('createReport');
    Route::get('/getReport/{reportId}', 'getReport')->name('getReport');
    Route::get('/getReportDocument/{reportId}/{country}', 'getReportDocument')->name('getReportDocument');
});

/**
 * 📊 Inventory Summary
 */
    // Route::get('/getInventorySummaries', [InventoryController::class, 'getInventorySummaries'])->name('getInventorySummaries');

/**
 * 📚 Catalog Item & Category Routes
 */
    // Route::controller(CatalogController::class)->group(function () {
    //     Route::get('/getCatalogItem', 'getCatalogItem')->name('getCatalogItem');
    //     Route::get('/listCatalogCategories', 'listCatalogCategories')->name('listCatalogCategories');
    // });

/**
 * 🌐 SP API General Routes
 */
    // Route::controller(SpApiController::class)->group(function () {
    //     Route::get('/marketplaces', 'index')->name('marketplaces');
    //     Route::get('/getListingItem', 'getListingItem')->name('getListingItem');
    //     Route::get('/searchListingsItems', 'searchListingsItems')->name('searchListingsItems');
    //     Route::get('/getMarketplaceParticipations', 'getMarketplaceParticipations')->name('getMarketplaceParticipations');
    // });

/**
 * 💰 Product Pricing Routes
 */
    // Route::controller(SpProductPricingController::class)->group(function () {
    //     Route::get('/getPricing', 'getPricing')->name('getPricing');
    //     Route::get('/getCompetitivePricing', 'getCompetitivePricing')->name('getCompetitivePricing');
    // });

/**
 * 🚚 Shipment Routes
 */
    // Route::controller(SpShipmentController::class)->group(function () {
    //     Route::get('/getShipments', 'getShipments')->name('getShipments');
    //     Route::get('/getShipmentItemsById/{shipmentId}', 'getShipmentItemsById')->name('getShipmentItemsById');
    // });

/**
 * 📝 Feeds Routes
 */
    // Route::controller(SpFeedsController::class)->group(function () {
    //     Route::get('/getFeed/{feedId}', 'getFeed')->name('getFeed');
    //     Route::post('/createFeed', 'createFeed')->name('createFeed');
    //     Route::post('/createFeedDocument', 'createFeedDocument')->name('createFeedDocument');
    //     Route::post('/uploadFeedDocument', 'uploadFeedDocuments')->name('uploadFeedDocument');
    //     Route::post('/checkFeedProcessingReport', 'checkFeedProcessingReport')->name('checkFeedProcessingReport');
    //     Route::get('/getFeedProcessingReport/{documentId}', 'getFeedProcessingReport')->name('getFeedProcessingReport');
    // });

/**
 * 📝 Manual Cron Functionality Run for reports
 */
// Route::controller(SpReportCronApi::class)->group(function () {
//     // Daily Sales
//     Route::post('create/dailySales', 'dailySalesApi');
//     Route::post('create/dailySalesSave', 'dailySalesApiSave');
//     // Weekly Sales
//     Route::post('create/weeklySales', 'weeklySalesApi');
//     Route::post('create/weeklySalesSave', 'weeklySalesApiSave');
//     // Monthly Sales
//     Route::post('create/monthlySales', 'monthlySalesApi');
//     Route::post('create/monthlySalesSave', 'monthlySalesApiSave');

//     // Insert FNSKU
//     Route::get('FbaManagerReport', 'FbaManagerReport');
//     Route::get('FbaManagerReportSave/{reportId}', 'FbaManagerReportSave');
// });

// /api/spapi/getListingsItem
// Route::get('/getListingsItem', function (\SellingPartnerApi\Seller\SellerConnector $connector) {
//     $listingsApi = $connector->listingsItemsV20210801();
//     $sellerId       = 'A3TC0WDGAJEARB';
//     $sku            = 'ECOTC2RED1P';
//     $marketplaceIds = ['ATVPDKIKX0DER'];

//     $includedData = ['summaries', 'offers', 'fulfillmentAvailability'];

//     $response = $listingsApi->getListingsItem(
//         $sellerId,
//         $sku,
//         $marketplaceIds,
//         null,
//         $includedData
//     );

//     return response()->json([
//         'ok'        => $response->successful(),     // true/false
//         'status'    => $response->status(),         // 200 / 400 / 403 ...
//         // 'reason'    => $response->reason(),         // "OK" / "Bad Request"
//         'json'      => $response->json(),           // decoded JSON from Amazon (null if empty)
//     ]);
// });



//     Route::get('/patchListingsItem', function (\SellingPartnerApi\Seller\SellerConnector $connector) {
//         $listingsApi = $connector->listingsItemsV20210801();

//         $sellerId       = 'A3TC0WDGAJEARB';
//         $sku            = 'ECOTC2RED1P';
//         $marketplaceIds = ['ATVPDKIKX0DER'];
//         $patches = [
//             new PatchOperation(
//                 'replace',                   // $op
//                 '/attributes/item_name',         // $path
//                 [
//                     [
//                         'value'    => 'EcoNour Car Trash Can | Portable Mini Trash Can for Car Prevents Garbage Scattering | Small and Convenient Leakproof Cup Holder Garbage Can with Lid for Cars, Home, and Office',
//                         'language' => 'en_US',
//                     ],
//                 ]                            // $value
//             ),
//         ];

//         $body = new ListingsItemPatchRequest(
//             'PRODUCT',
//             $patches
//         );

//         $response = $listingsApi->patchListingsItem(
//             $sellerId,
//             $sku,
//             $body,
//             $marketplaceIds,
//         );

//         return response()->json([
//             'ok'        => $response->successful(),     // true/false
//             'status'    => $response->status(),         // 200 / 400 / 403 ...
//             // 'reason'    => $response->reason(),         // "OK" / "Bad Request"
//             'json'      => $response->json(),           // decoded JSON from Amazon (null if empty)
//             'body'      => $response->body(),           // raw string body
//             'headers'   => $response->headers()->all(), // response headers
//         ]);
//     });
// });

// Route::get('/delete-report', function () {
//     $client = new AmazonAdsService();
//     $data = $client->deleteReport('381202bf-15f9-4f4e-abda-0eac8cbb7bd5');

//     $decoded = json_decode($data['response'] ?? '[]', true);

//     $count = isset($decoded['campaigns']) ? count($decoded['campaigns']) : 0;

//     return response()->json([
//         'success' => true,
//         'count' => $count,
//         'campaigns' => $decoded,
//     ]);
// });

Route::get('/rankedKeywordAsin', function (Request $request) {

    $country = 'US';
    $amazonAdsService = app(AmazonAdsService::class);

    $profileId = config('amazon_ads.profiles.US');
    if (!$profileId) {
        return response()->json([
            'success' => false,
            'message' => 'No profile ID configured for country: ' . $country,
        ], 500);
    }

    // ✅ Accept array, comma-separated string, or single ASIN
    $asinsInput = $request->input('asins', ['B0CJM3L6PS']);

    if (is_string($asinsInput)) {
        $asins = array_filter(array_map('trim', explode(',', $asinsInput)));
    } elseif (is_array($asinsInput)) {
        $asins = $asinsInput;
    } else {
        $asins = [];
    }

    // ✅ clean + normalize
    $asins = array_values(array_unique(array_filter(array_map(function ($a) {
        $a = strtoupper(trim((string) $a));
        return $a !== '' ? $a : null;
    }, $asins))));

    if (count($asins) === 0) {
        return response()->json([
            'success' => false,
            'message' => 'Please provide at least one ASIN',
        ], 422);
    }

    // ✅ FIX: asins must be a flat array, NOT nested
    $payload = [
        "asins"               => $asins,
        "recommendationType"  => "KEYWORDS_FOR_ASINS",
        "locale"              => "en_US",
        "maxRecommendations"  => (int) $request->input('maxRecommendations', 10),
        "sortDimension"       => (string) $request->input('sortDimension', 'CLICKS'),
    ];
    $data = $amazonAdsService->getRankedKeywordRecommendation($payload, $profileId);

    $responseRaw = $data['response'] ?? null;

    // ✅ Decode JSON string
    if (is_string($responseRaw)) {
        $decoded = json_decode($responseRaw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'JSON decode failed',
                'error' => json_last_error_msg(),
                'raw_response' => $responseRaw,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'payload_sent' => $payload,
            'decoded_json' => $decoded,
        ]);
    }

    // Already array
    if (is_array($responseRaw)) {
        return response()->json([
            'success' => true,
            'payload_sent' => $payload,
            'decoded_json' => $responseRaw,
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Unknown response type',
        'type' => gettype($responseRaw),
    ]);
});


Route::get('/rankedKeyword', function (Request $request) {
    // dd("test");
    //    Change this to whatever you actually want to test.
    $country   = 'US';
    $amazonAdsService = app(AmazonAdsService::class);

    $profileId = config('amazon_ads.profiles.US');  // or CA, etc.

    if (!$profileId) {
        dd('No profile ID configured for country: ' . $country);
    }

    // 2️⃣ Hard-code campaign + adGroup + keywords for now
    //    Replace with real IDs from your account
    $campaignId = (int) $request->input('campaign_id', 373623103722865);
    $adGroupId  = (int) $request->input('ad_group_id', 304493729870325);

    // simple keyword list
    $keywords = $request->input('keywords', [
        [
            'keyword_text' => 'usb c cable',
            'match_type'   => 'EXACT',
        ],
        [
            'keyword_text' => 'type c cable',
            'match_type'   => 'PHRASE',
        ],
    ]);

    // 3️⃣ Build targets exactly like your command did
    $targets = [];
    foreach ($keywords as $k) {
        $targets[] = [
            "keyword"             => $k['keyword_text'],
            "matchType"           => strtoupper($k['match_type'] ?? 'EXACT'),
            "userSelectedKeyword" => true,
        ];
    }

    // 4️⃣ Build payload
    $payload = [
        "adGroupId"          => $adGroupId,
        "campaignId"         => $campaignId,
        "recommendationType" => "KEYWORDS_FOR_ADGROUP",
        // "targets"            => array_values($targets),
        "locale"             => "en_US",
        "maxRecommendations" => 10,
        "sortDimension"      => "DEFAULT",
    ];

    // 5️⃣ Call service and dd() the full response
    $data = $amazonAdsService->getRankedKeywordRecommendation($payload, $profileId);

    // If your service returns ['success' => bool, 'response' => 'json-string']
    $responseRaw = $data['response'];

    // If already array → use as-is
    if (is_array($responseRaw)) {
        return response()->json([
            'success'      => true,
            'payload_sent' => $payload,
            'decoded_json' => $responseRaw,
            'notice'       => 'Response was already an array (no json_decode needed)',
        ]);
    }

    // If string → decode it
    if (is_string($responseRaw)) {
        $decoded = json_decode($responseRaw, true);

        return response()->json([
            'success'      => true,
            'payload_sent' => $payload,
            'decoded_json' => $decoded,
            'json_error'   => json_last_error_msg(),
        ]);
    }

    // Fallback (should never occur)
    return response()->json([
        'success' => false,
        'message' => 'Unknown response data type',
        'type'    => gettype($responseRaw),
    ]);
});

// Route::get('/ads/campaign/create', function (AmazonAdsService $amazonAdsService) {

//     $profileId = config('amazon_ads.profiles.US');

//     $payload = [
//         'campaigns' => [
//             [
//                 'name'          => 'TEST_AUTO_CAMPAIGN_' . now()->format('Ymd_His'),
//                 'campaignType'  => 'SPONSORED_PRODUCTS',
//                 'targetingType' => 'AUTO',
//                 'state'         => 'ENABLED',
//                 'startDate'     => now()->format('Y-m-d'),

//                 'budget' => [
//                     'budget'     => 1.00,
//                     'budgetType' => 'DAILY',
//                 ],
//             ],
//         ],
//     ];
//     // dd($payload);
//     $response = $amazonAdsService->createCampaigns($payload, $profileId);

//     dd($response);
// });

// Route::get('/ads/adgroup/create', function (AmazonAdsService $amazonAdsService) {

//     $profileId  = config('amazon_ads.profiles.US');
//     $campaignId = '475522641725869'; // newly created campaign

//     $payload = [
//         'adGroups' => [
//             [
//                 'campaignId' => $campaignId,
//                 'name'       => 'TEST_ADGROUP_' . now()->format('His'),
//                 'defaultBid' => 0.1, // REQUIRED by Amazon
//                 'state'      => 'ENABLED',
//             ],
//         ],
//     ];

//     $response = $amazonAdsService->createSPAdGroups($payload, $profileId);

//     dd($response);
// });

// Route::get('/ads/productad/create', function (AmazonAdsService $amazonAdsService) {

//     $profileId  = config('amazon_ads.profiles.US');

//     $payload = [
//         'productAds' => [
//             [
//                 'campaignId' => '475522641725869',
//                 'adGroupId'  => '409706768361459',
//                 'sku'        => 'ECO_HON_SECU_Blue', // REQUIRED
//                 'asin'       => 'B0D56Y8YWQ',           // optional for you, keep if you want
//                 'state'      => 'ENABLED',
//             ],
//         ],
//     ];

//     dd($amazonAdsService->createSPProductAds($payload, $profileId));
// });




// Route::get('/ads/keywords/create', function (AmazonAdsService $amazonAdsService) {

//     //  Static profile ID (US)
//     $profileId = config('amazon_ads.profiles.US');

//     //  Static payload (example keywords)
//     $payload = [
//         'keywords' => [
//             [
//                 'campaignId'            => '466233576529408',
//                 'adGroupId'             => '435320905451090',
//                 'keywordText'           => 'keyword test 1',
//                 'matchType'             => 'BROAD',
//                 'bid'                   => 1.2,
//                 'state'                 => 'ENABLED',
//             ],
//             [
//                 'campaignId'            => '466233576529408',
//                 'adGroupId'             => '435320905451090',
//                 'keywordText'           => 'keyword test 2',
//                 'matchType'             => 'BROAD',
//                 'bid'                   => 1.2,
//                 'state'                 => 'ENABLED',
//             ],
//         ],
//     ];


//     $response = $amazonAdsService->createKeywords($payload, $profileId);
//     dd($response);
//     if (!empty($response) && isset($response['success']) && $response['success'] === true) {
//     }

//     return response()->json([
//         'success'    => true,
//         'profileId'  => $profileId,
//         'count'      => count($payload['keywords']),
//         'payload'    => $payload,
//     ]);
// });



// // delete a keyword manually
// Route::post('/ads/keywords/delete', function (Request $request, AmazonAdsService $amazonAdsService) {

//     // Manual default keyword IDs (used if none passed)
//     $keywordIds = $request->input('keyword_ids', [
//         '1234567890',
//         '9876543210',
//     ]);

//     //  Resolve profileId (simple)
//     $country = strtoupper($request->input('country', 'US'));

//     $profileId = match ($country) {
//         'US' => config('amazon_ads.profiles.US'),
//         'CA' => config('amazon_ads.profiles.CA'),
//         default => throw new Exception("Unhandled country: {$country}"),
//     };

//     // Build payload
//     $payload = [
//         'keywordIdFilter' => [
//             'include' => array_values($keywordIds),
//         ],
//     ];

//     //  Debug (uncomment if needed)
//     // dd($payload, $profileId);

//     //  Amazon API call
//     $response = $amazonAdsService->deleteKeywords($payload, $profileId);

//     return response()->json([
//         'status'     => 'success',
//         'country'    => $country,
//         'profile_id' => $profileId,
//         'count'      => count($keywordIds),
//         'payload'    => $payload,
//         'amazon_response' => $response,
//     ]);
// });


Route::get('budgetRules', function (Request $request, AmazonAdsService $amazonAdsService) {

    $country = $request->get('country', 'US');

    $profileId = match ($country) {
        'US' => config('amazon_ads.profiles.US'),
        'CA' => config('amazon_ads.profiles.CA'),
        default => throw new Exception("Unhandled country"),
    };

    $query = [
        'pageSize' => (int) $request->get('pageSize', 30),
    ];

    $data = $amazonAdsService->getSPBudgetRulesForAdvertiser($profileId, $query);
    $response = json_decode($data['response'], true);

    return response()->json([
        'status'          => 'success',
        'amazon_response' => $response,
    ]);
});

/**
 * 🧪 Campaign AI Service Test Endpoints
 * Test the CampaignAiService and CampaignKeywords Tool manually
 */
Route::prefix('test')->group(function () {
    Route::controller(\App\Http\Controllers\Api\CampaignAiTestController::class)->group(function () {
        // Basic test with hardcoded values
        Route::get('/campaign-keywords', 'testCampaignKeywords')
            ->name('test.campaign-keywords');

        // Test search functionality
        Route::get('/campaign-keywords/search', 'testSearchKeywords')
            ->name('test.search-keywords');

        // Test with custom filters from query params
        Route::get('/campaign-keywords/custom', 'testCustomFilters')
            ->name('test.custom-filters');

        // Test the CampaignKeywords Tool directly
        Route::get('/campaign-keywords-tool', 'testCampaignKeywordsTool')
            ->name('test.campaign-keywords-tool');

        // Test deep analysis functionality
        Route::get('/campaign-keywords/deep-analysis', 'testDeepAnalysis')
            ->name('test.deep-analysis');
    });
});

