<?php

use App\Http\Controllers\Admin\AdsOverviewController;
use App\Http\Controllers\Admin\AiRecommendationController;
use App\Http\Controllers\Admin\AmzAdsCampaignSchedulerController;
use App\Http\Controllers\Admin\BrandAnalyticsController;
use App\Http\Controllers\Admin\CurrencyController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AiExportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SourcingController;
use App\Http\Controllers\Admin\SellingController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\OrderForecastController;
use App\Http\Controllers\Admin\SellingAsinController;
use App\Http\Controllers\Admin\AmzAdsController;
use App\Http\Controllers\Admin\AmzAdsPerformanceController;
use App\Http\Controllers\Admin\AmzAdsSearchTermsController;
use App\Http\Controllers\Admin\AmzAdsViewLive;
use App\Http\Controllers\Admin\AssignAsinController;
use App\Http\Controllers\Admin\AdsBudgetController;
use App\Http\Controllers\Admin\CampaignRecommendationRulesController;
use App\Http\Controllers\Admin\KeywordRecommendationRulesController;
use App\Http\Controllers\Admin\OrderForecastAsinController;
use App\Http\Controllers\Admin\OrderForecastPerformanceController;
use App\Http\Controllers\Admin\SellingAdsItemController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Admin\StocksController;
use App\Http\Controllers\Admin\TargetRecommendationRulesController;
use App\Http\Controllers\Admin\CampaignCreationController;
use App\Http\Controllers\Dev\DatabaseBackUpController;
use App\Http\Controllers\Dev\JobMonitorController;
use App\Http\Controllers\Dev\RolesAndPermissionController;
use App\Http\Controllers\Dev\ScheduleController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Livewire\Dashboard\HourlyProductSalesPage;
use App\Livewire\Ai\AiPlayground;
use App\Models\RagDocument;
use Illuminate\Support\Facades\Log;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
use App\Services\Rag\QdrantClient;
use Illuminate\Support\Facades\DB;

Route::get('/rag/self-test/campaign', function (QdrantClient $qdrant) {
    $qdrant->ensureCollection();

    $source = 'amz_ads_campaign_performance_report';

    // ✅ Pick a row that we KNOW was embedded+inserted (committed) successfully
    $doc = RagDocument::query()
        ->where('doc_key', 'like', $source . ':%')
        ->whereNotNull('embedded_at')
        ->orderByDesc('source_row_id')
        ->first();

    if (!$doc) {
        return response()->json(['error' => 'No embedded documents found in rag_documents for this source.'], 404);
    }

    $row = DB::table($source)->where('id', $doc->source_row_id)->first();

    if (!$row) {
        return response()->json([
            'error' => 'Embedded row id not found in source table.',
            'source_row_id' => $doc->source_row_id,
            'doc_key' => $doc->doc_key,
        ], 404);
    }

    // Build query from reliable fields (no budget_gap)
    $query = implode(' ', array_filter([
        'country',
        (string) ($row->country ?? ''),
        'status',
        (string) ($row->c_status ?? ''),
        'campaign',
        (string) $row->campaign_id,
        'adgroup',
        (string) ($row->ad_group_id ?? ''),
        'cost',
        number_format((float) $row->cost, 2, '.', ''),
        'clicks',
        (string) ((int) ($row->clicks ?? 0)),
        'sales7d',
        isset($row->sales7d) ? number_format((float) $row->sales7d, 2, '.', '') : '',
        'date',
        substr((string) $row->c_date, 0, 10),
    ]));

    $vector = Str::of($query)->toEmbeddings();
    $results = $qdrant->search($vector, 10);

    $targetRowId = (int) $row->id;
    $hitIndex = null;

    foreach ($results as $i => $r) {
        $payload = $r['payload'] ?? [];
        if ((int) ($payload['row_id'] ?? 0) === $targetRowId) {
            $hitIndex = $i;
            break;
        }
    }

    return response()->json([
        'picked_from_rag_documents' => [
            'doc_key' => $doc->doc_key,
            'source_row_id' => (int) $doc->source_row_id,
            'embedded_at' => (string) $doc->embedded_at,
        ],
        'db_row' => [
            'id' => (int) $row->id,
            'campaign_id' => (string) $row->campaign_id,
            'ad_group_id' => (string) ($row->ad_group_id ?? ''),
            'country' => (string) ($row->country ?? ''),
            'c_status' => (string) ($row->c_status ?? ''),
            'c_date' => substr((string) $row->c_date, 0, 19),
            'cost' => (float) $row->cost,
            'clicks' => (int) ($row->clicks ?? 0),
            'sales7d' => isset($row->sales7d) ? (float) $row->sales7d : null,
        ],
        'query_used' => $query,
        'found_in_top_10' => $hitIndex !== null,
        'rank_if_found_zero_based' => $hitIndex,
        'top_10' => $results,
    ]);
});
Route::get('/rag/search', function (QdrantClient $qdrant) {
    $qdrant->ensureCollection();

    $q = request('q', 'budget gap yes low cost campaign');
    $k = (int) request('k', 10);

    $vector = Str::of($q)->toEmbeddings();

    return response()->json([
        'query' => $q,
        'results' => $qdrant->search($vector, $k),
    ]);
});
Route::get('/test-search', function (QdrantClient $qdrant) {
    $qdrant->ensureCollection();

    $q = request('q', 'high cost campaign budget gap');
    $vector = Str::of($q)->toEmbeddings();

    return response()->json($qdrant->search($vector, 5));
});
Route::get('/qdrant-info', function () {

    $url = rtrim(config('rag.qdrant.url'), '/');
    $collection = config('rag.qdrant.collection');
    $apiKey = config('rag.qdrant.api_key');

    $response = Http::withHeaders([
        'api-key' => $apiKey,
    ])->get("{$url}/collections/{$collection}");

    return $response->json();
});
use Illuminate\Support\Str;

Route::get('/test-embedding', function () {
    $vec = Str::of('hello world')->toEmbeddings();

    return response()->json([
        'dimension' => count($vec),
        'first5' => array_slice($vec, 0, 5),
    ]);
});
// Redirect root to login or dashboard
Route::get('/', function () {
    if (Auth::check()) {
        return to_route('admin.dashboard');
    }
    Auth::logout();
    return to_route('login');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Guest Only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::match(['get', 'post'], '/check-email', [LoginController::class, 'checkEmail'])->name('check.email');
    Route::get('reset-password/{token}', [LoginController::class, 'passwordReset'])->name('password.reset');
    Route::post('reset-password', [LoginController::class, 'passwordStore'])->name('reset.password.update');
    Route::get('password/notice', [LoginController::class, 'passwordNotice'])->name('password.notice');


    Route::controller(ForgotPasswordController::class)->group(function () {
        // Step 1: Forgot password (enter email)
        Route::get('password/forgot', 'index')->name('password.forgot');
        Route::post('password/verify-email', 'verifyEmail')->name('verifyEmail');

        // Step 2: Verify OTP
        Route::get('password/verify-otp/{email}', 'showVerifyOtpForm')->name('password.verify.otp.form');
        Route::post('password/verify-otp', 'verifyOtp')->name('password.verify.otp');

        // Step 3: Change password (secured with token, not email)
        Route::get('password/change/{token}', 'showChangePasswordForm')->name('password.change.form');
        Route::post('password/change/{token}', 'updatePassword')->name('password.update');
    });
});

/*
|--------------------------------------------------------------------------
| Logout Route
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->get('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (Authenticated Only)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->as('admin.')->middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-cache-clear', [DashboardController::class, 'clearCache'])->name('dashboard.clearCache');
    Route::get('/dashboard-monthToDateDailyView', [DashboardController::class, 'monthToDateDailyView'])->name('dashboard.monthToDateDailyView');
    Route::get('/dashboard-flushMtdDailyCache', [DashboardController::class, 'flushMtdDailyCache'])->name('dashboard.flushMtdDailyCache');

    Route::get('/dashboard/hourly-sales/products', HourlyProductSalesPage::class)
        ->name('dashboard.hourly-sales.products');
    Route::get('/dashboard/detailed-todays-sales-summery', [DashboardController::class,'detailedTodaysSalesSummery'])
        ->name('dashboard.detailed-todays-sales-summery');
    Route::get('/dashboard/snapshot-todays-sales-summery', [DashboardController::class, 'snapshotTodaysSalesSummery'])
        ->name('dashboard.snapshot-todays-sales-summery');
    // Profile
    Route::post('/change-password', [ProfileController::class, 'updatePassword'])->name('updatePassword');

    // Users
    Route::get('changeStatus/{id}', [UserController::class, 'changeStatus'])->name('users.changeStatus');
    Route::get('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::get('users/leave-impersonation', [UserController::class, 'leave'])->name('users.leave');

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->as('products.')->controller(ProductController::class)->group(function () {
        Route::post('/createSku', 'createSku')->name('createSku');
        Route::post('/productUpdate/{id}', 'update')->name('updateProducts');
        Route::get('/syncProductDetails/{id}', 'syncProductDetails')->name('syncProductDetails');
        Route::get('/createSelling/{id}', 'createSelling')->name('createSelling');
        Route::get('/inActive/{id}', 'inActive')->name('inActive');
        Route::match(['get', 'post'], '/updateProduct/{id?}', 'updateProduct')->name('updateProduct');
    });

    /*
    |--------------------------------------------------------------------------
    | Sourcing / Containers
    |--------------------------------------------------------------------------
    */
    Route::prefix('sourcing')->as('sourcing.')->controller(SourcingController::class)->group(function () {
        Route::post('/createContainer', 'createContainer')->name('createContainer');
        Route::post('/createItemList', 'createListingItem')->name('createItemList');
        Route::get('/{uuid}/chats', 'getChats')->name('chats.get');
        Route::post('/chat', 'saveChats')->name('chat.save');
        Route::put('/update/{uuid}', 'update')->name('updateSourcing');
        Route::post('/{uuid}', 'archive')->name('archive');
        Route::get('/exportExcel', 'exportExcel')->name('exportExcel');
        Route::put('/moveContainer', 'moveToContainer')->name('moveContainer');
    });

    /*
    |--------------------------------------------------------------------------
    | Selling
    |--------------------------------------------------------------------------
    */
    Route::prefix('selling')->as('selling.')->controller(SellingController::class)->group(function () {
        // Route::get('sellingItems', 'sellingItems')->name('sellingItems');
        Route::get('/selling-details/{uuid}', 'sellingItems')->name('createSelling');
        Route::post('discontinueProduct/{uuid}', 'discontinueProduct')->name('discontinueProduct');
        Route::post('setAmazonPrice', 'setAmazonPrice')->name('setAmazonPrice');
        Route::post('updateProfit', 'updateProfit')->name('updateProfit');
    });

    Route::prefix('asin-selling')->as('asin-selling.')->controller(SellingAsinController::class)->group(function () {
        Route::get('asin-details/{asin}', 'details')->name('details');
    });

    Route::prefix('sellingAdsItem')->as('selling.adsItems.')->controller(SellingAdsItemController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{asin}', 'details')->name('details');
        Route::post('/create-keywords', 'createKeywords')->name('createKeywords');
        Route::get('/keywordsDetail/{asin}', 'keywordsDetail')->name('keywordsDetail');
    });

    /*
    |--------------------------------------------------------------------------
    | Campaign Creation Auto and Manual
    |--------------------------------------------------------------------------
    */
    Route::prefix('ads/campaigns')->as('campaigns.')->controller(CampaignCreationController::class)->group(function () {
        Route::post('/generated', 'storeGenerated')->name('generated.store');
        Route::post('manual/{draft}', 'manualCreateFromDraft')->name('manual.store');
        Route::post('auto/{draft}', 'autoCreateFromDraft')->name('auto.store');

        Route::get('drafts', 'draftsPage')->name('drafts.page'); // show the page
    });

    /*
    |--------------------------------------------------------------------------
    | Shipments
    |--------------------------------------------------------------------------
    */
    Route::prefix('shipments')->as('shipments.')->controller(ShipmentController::class)->group(function () {
        Route::post('/updateShipments/{id}', 'updateShipments')->name('updateShipments');
        Route::get('/items/{id}', 'items')->name('items');
        Route::get('/items/create/{id}', 'itemCreate')->name('itemCreate');
        Route::post('/items/store', 'itemStore')->name('itemStore');
        Route::get('/items/edit/{id}', 'itemEdit')->name('itemEdit');
        Route::put('/items/update/{id}', 'itemUpdate')->name('itemUpdate');
        Route::delete('/items/delete/{id}', 'itemDelete')->name('itemDelete');
        Route::get('/shipmentItemExport/{id}', 'shipmentItemExport')->name('itemExport');
    });

    Route::get('/shipmentLists', [ShipmentController::class, 'shipmentLists'])->name('shipments.shipmentLists');

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | Warehouse
    |--------------------------------------------------------------------------
    */
    Route::prefix('warehouse')->as('warehouse.')->controller(WarehouseController::class)->group(function () {
        Route::post('/createWarehouse', 'createWarehouse')->name('createWarehouse');
        Route::get('/{uuid}/quantities', 'quantities')->name('quantities');
        Route::get('/allWarehouseInventory', 'allWarehouseInventory')->name('allWarehouseInventory');
        Route::post('/{uuid}/importInventory', 'importInventory')->name('importInventory');
        Route::get('/inventoryForm/{id}/{inventoryId?}', 'inventoryForm')->name('inventoryForm');
        Route::post('/addInventory', 'addInventory')->name('addInventory');
        Route::put('/editInventory/{id}', 'editInventory')->name('editInventory');
        Route::delete('/deleteInventory/{id}', 'deleteInventory')->name('deleteInventory');
        Route::get('/exportExcelAll', 'warehouseStockDownload')->name('exportExcel');
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase order
    |--------------------------------------------------------------------------
    */
    Route::prefix('purchaseOrder')->as('purchaseOrder.')->controller(PurchaseOrderController::class)->group(function () {
        Route::get('{id}/items',  'items')->name('items');
        Route::get('{id}/items/create', 'itemCreate')->name('itemCreate');
        Route::post('items', 'itemStore')->name('itemStore');
        Route::get('items/{id}/edit', 'itemEdit')->name('itemEdit');
        Route::put('items/{id}', 'itemUpdate')->name('itemUpdate');
        Route::delete('items/{id}', 'itemDelete')->name('itemDelete');
        Route::post('/updatePurchaseOrderItems/{id}', 'updatePurchaseOrderItems')->name('updatePurchaseOrderItems');
    });
    Route::get('/allPurchaseOrderList', [PurchaseOrderController::class, 'allPurchaseOrders'])->name('purchaseOrder.allPurchaseOrders');
    Route::get('/allPurchaseOrderList/delayed/{sku}', [PurchaseOrderController::class, 'delayedLists'])->name('purchaseOrder.delayedLists');;

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    Route::prefix('notification')->as('notification.')->controller(NotificationController::class)->group(function () {
        Route::get('clear-cache', 'clearCache')->name('refresh');
        Route::get('/{id?}', 'index')->name('index');
        Route::post('/{id}/assign', 'assignUser')->name('assign-user');
        Route::patch('{id}/toggle-status', 'toggleStatus')->name('toggle-status');
        Route::get('delete/{id?}', 'destroy')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Order forecasts
    |--------------------------------------------------------------------------
    */
    Route::prefix('orderforecast')->as('orderforecast.')->controller(OrderForecastController::class)->group(function () {
        Route::put('/updateSoldValue', 'updateSoldValue')->name('updateSoldValue');
        Route::put('/updateOrderAmount', 'updateOrderAmount')->name('updateOrderAmount');
        Route::get('/downloadForecastSnapshots', 'downloadForecastSnapshots')->name('downloadForecastSnapshots');
        Route::get('/downloadForecastSnapshotsSku', 'downloadForecastSnapshotsSku')->name('downloadForecastSnapshotsSku');
        Route::post('/generateAI', 'generateAI')->name('generateAI');
        Route::get('/getStatus/{id?}', 'getStatus')->name('getStatus');
        Route::post('/generateBulkAI/{id?}', 'generateBulkAI')->name('generateBulkAI');
    });

    /*
    |--------------------------------------------------------------------------
    | Order forecasts Asins
    |--------------------------------------------------------------------------
    */
    Route::prefix('orderforecastasin')->as('orderforecastasin.')->controller(OrderForecastAsinController::class)->group(function () {
        Route::get('/downloadForecastSnapshotsAsin', 'downloadForecastSnapshotsAsin')->name('downloadForecastSnapshotsAsin');
        Route::post('/generateAI', 'generateAI')->name('generateAI');
        Route::get('/getStatus/{id?}', 'getStatus')->name('getStatus');
        Route::post('/generateBulkAI/{id?}', 'generateBulkAI')->name('generateBulkAI');
        Route::get('/asinSysBreakdown/{asin}/{forecastId}', 'asinSysBreakdown')->name('asinSysBreakdown');
        Route::get('/asinDetailByMonth', 'asinDetailByMonth')->name('asinDetailByMonth');
        Route::get('/downloadOrderForecastAsinMonthlyExport', 'downloadOrderForecastAsinMonthlyExport')->name('downloadOrderForecastAsinMonthlyExport');
        Route::post('/bulkUpload', 'bulkUpload')->name('bulkUpload');
        Route::post('/confirmBulkUpload', 'confirmBulkUpload')->name('confirmBulkUpload');
        Route::post('/cancelBulkUpload', 'cancelBulkUpload')->name('cancelBulkUpload');
        Route::get('/exportTemplate', 'exportTemplate')->name('exportTemplate');
        Route::get('/weatherTableMultiCountry', 'weatherTableMultiCountry')->name('weatherTableMultiCountry');
    });

    /*
    |--------------------------------------------------------------------------
    | Download all Data
    |--------------------------------------------------------------------------
    */
    Route::prefix('data')->as('data.')->controller(DataController::class)->group(function () {
        Route::get('itemPriceDownload', 'itemPriceDownload')->name('itemPriceDownload');
        Route::get('masterDataDownload', 'masterDataDownload')->name('masterDataDownload');
        Route::get('libraryImagesDownload', 'libraryImagesDownload')->name('libraryImagesDownload');
        Route::get('cartonSizeDownload', 'cartonSizeDownload')->name('cartonSizeDownload');
        Route::get('adsPerformanceLast7Days', 'adsPerformanceLast7Days')->name('adsPerformanceLast7Days');
        Route::get('adsPerformanceLast4Weeks', 'adsPerformanceLast4Weeks')->name('adsPerformanceLast4Weeks');
        Route::get('adsPerformanceLast3Months', 'adsPerformanceLast3Months')->name('adsPerformanceLast3Months');
        Route::get('adsPerformanceByAsinDownload', 'adsPerformanceByAsinDownload')->name('adsPerformanceByAsinDownload');
        Route::get('adsPerformanceByProductCampaignDownload', 'adsPerformanceByProductCampaignDownload')->name('adsPerformanceByProductCampaignDownload');
        Route::get('adsKeywordPerformance7daysDownload', 'adsKeywordPerfomanceDownload7days')->name('adsKeywordPerfomanceDownload7days');
        Route::get('exportExcelAsin', 'asinWarehouseStockDownload')->name('asinExportExcel');
        Route::get('adsPerformanceSdLast7Days', 'adsPerformanceSdLast7Days')->name('adsPerformanceSdLast7Days');
        Route::get('adsPerformanceSdLast4Weeks', 'adsPerformanceSdLast4Weeks')->name('adsPerformanceSdLast4Weeks');
        Route::get('adsPerformanceSdLast3Months', 'adsPerformanceSdLast3Months')->name('adsPerformanceSdLast3Months');
        Route::get('combinedAdsPerformanceExport', 'combinedAdsPerformanceExport')->name('combinedAdsPerformanceExport');
        Route::get('adsCampaignLast7Days', 'adsCampaignLast7Days')->name('adsCampaignLast7Days');
        Route::get('adsCampaignLast4Weeks', 'adsCampaignLast4Weeks')->name('adsCampaignLast4Weeks');
        Route::get('adsCampaignLast3Months', 'adsCampaignLast3Months')->name('adsCampaignLast3Months');
        Route::get('StockRunDownReport', 'StockRunDownReport')->name('StockRunDownReport');
        Route::get('salesDailyReport', 'salesDailyReport')->name('salesDailyReport');
        Route::get('salesMonthlyReport', 'salesMonthlyReport')->name('salesMonthlyReport');
        Route::get('rankingReport', 'rankingReport')->name('rankingReport');
        Route::get('weeklySalesPerformanceReport', 'weeklySalesPerformanceReport')->name('weeklySalesPerformanceReport');
        Route::get('orderForecastFinaliseExport', 'orderForecastFinaliseExport')->name('orderForecastFinaliseExport');
        Route::get('AsinPerformanceReportExport', 'AsinPerformanceReportExport')->name('AsinPerformanceReportExport');
    });

    /*
    |--------------------------------------------------------------------------
    | Amz Ads Capnaings and Keywords
    |--------------------------------------------------------------------------
    */
    Route::prefix('ads/overview')->as('ads.overview.')->controller(AdsOverviewController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/campaignOverview', 'campaignOverview')->name('campaignOverview');
        Route::get('/CacheClear', 'cacheClear')->name('cacheClear');
        Route::get('/keywordOverview', 'keywordOverview')->name('keywordOverview');
        Route::get('/keywordDashboard', 'keywordDashboard')->name('keywordDashboard');
        Route::get('/keywordCacheClear', 'keywordCacheClear')->name('keywordCacheClear');
    });
    Route::prefix('ads')->as('ads.')->controller(AmzAdsController::class)->group(function () {
        Route::get('campaigns', 'campaigns')->name('campaigns');
        Route::get('campaigns/keywords/{id}/{type?}', 'campaignKeywords')->name('campaignKeywords');
        Route::get('campaignsSb', 'campaignsSb')->name('campaignsSb');
        Route::get('campaignsSd', 'campaignsSd')->name('campaignsSd');
        Route::post('campaign/update', 'campaignUpdate')->name('campaign.update');
        Route::get('keywords', 'keywordsSp')->name('keywords');
        Route::get('keywordsSb', 'keywordsSb')->name('keywordsSb');
        Route::get('targetsSd', 'targetsSd')->name('targetsSd');
        Route::post('keyword/update', 'keywordsUpdate')->name('keyword.update');
        Route::get('keyword/campaignAsinsSp', 'campaignAsinsSp')->name('keyword.campaignAsinsSp');
        Route::get('keyword/campaignAsinsSp/allKeywords/{asin}', 'allKeywordsAsin')->name('allKeywordsAsin');
        Route::get('keyword/campaignAsinsSb', 'campaignAsinsSb')->name('keyword.campaignAsinsSb');

        Route::get('campaigns/create/{type?}', 'campaignCreate')->name('campaigns.create');
    });

    Route::prefix('ads/brand-analytics')->as('ads.brandAnalytics.')->controller(BrandAnalyticsController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('competitor-rank', 'competitorRank')->name('competitorRank');
        Route::get('analytics-2024', 'analytics2024')->name('analytics2024');
        Route::get('weekly-analytics', 'weeklyAnalytics')->name('weeklyAnalytics');
    });

    Route::prefix('ads/performance')->as('ads.performance.')->controller(AmzAdsPerformanceController::class)->group(function () {
        Route::get('asins', 'asinPerformance')->name('asins.index');
        Route::get('campaigns', 'capaignPerformance')->name('capaigns.index');
        Route::get('keywords', 'keywordPerformance')->name('keywords.index');
        Route::get('targets', 'targetPerformance')->name('targets.index');
        Route::get('keywords/export', 'keywordPerformanceExport')->name('keywords.export');
        Route::get('campaigns/export', 'campaignPerformanceExport')->name('campaigns.export');
        Route::get('targets/export', 'targetPerformanceExport')->name('targets.export');
        Route::get('productAsins', 'productAsins')->name('productAsins');
        Route::post('/campaigns/bulk-run-update', 'runUpdate')->name('campaigns.runUpdate');
        Route::post('/campaigns/bulk-budget-update', 'bulkBudgetUpdate')->name('campaigns.bulkBudgetUpdate');
        Route::post('/keywords/runKeywordUpdate', 'runKeywordUpdate')->name('keywords.runKeywordUpdate');
        Route::get('/keywords/keywordMakeLive', 'keywordMakeLive')->name('keywords.keywordMakeLive');
        Route::post('/keywords/bulk-bid-update', 'bulkBidUpdate')->name('keywords.bulkBidUpdate');
        Route::get('campaigns/make-live', 'campaignMakeLive')->name('campaigns.makeLive');
        Route::get('showLogs/{type}/{date}', 'showLogs')->name('showLogs');
        Route::post('runPerformanceLogUpdate', 'runPerformanceLogUpdate')->name('runPerformanceLogUpdate');
        Route::post('performanceLogsMakeRevertLive', 'performanceLogsMakeRevertLive')->name('performanceLogsMakeRevertLive');

        // AI Recommendation Routes
        Route::prefix('recommendation')->as('recommendation.')->controller(AiRecommendationController::class)->group(function () {
            // Dispatch generation
            Route::post('keyword/generate/{id}', 'keywordgenerate')->name('keywordgenerate');
            // Route::post('campaign/generate/{id}', 'campaigngenerate')->name('campaigngenerate');
            Route::post('target/generate/{id}', 'targetgenerate')->name('targetgenerate');

            // Poll status
            Route::prefix('poll')->as('poll.')->group(function () {
                Route::get('keyword/status/{id}', 'keywordStatus')->name('keywordStatus');
                // Route::get('campaign/status/{id}', 'campaignStatus')->name('campaignStatus');
                Route::get('target/status/{id}', 'targetStatus')->name('targetStatus');
            });
        });

        Route::prefix('rules')->as('rules.')->controller(CampaignRecommendationRulesController::class)->group(function () {
            Route::get('rules', 'index')->name('index');
            Route::get('rules/{id}/edit', 'edit')->name('edit');
            Route::put('rules/{id}', 'update')->name('update');
        });

        Route::prefix('rules/target')->as('rules.target.')->controller(TargetRecommendationRulesController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{id}/edit', 'edit')->name('edit');
            Route::put('{id}', 'update')->name('update');
            Route::get('partials', 'partials')->name('partials');
        });

        Route::prefix('rules/keyword')->as('rules.keyword.')->controller(KeywordRecommendationRulesController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{id}/edit', 'edit')->name('edit');
            Route::put('{id}', 'update')->name('update');
        });
    });

    Route::prefix('viewLive')->as('viewLive.')->controller(AmzAdsViewLive::class)->group(function () {
        Route::get('campaign', 'campaign')->name('campaign');
        Route::get('keyword', 'keyword')->name('keyword');
        Route::get('keywordForAdGroup', 'keywordForAdGroup')->name('keywordForAdGroup');
    });

    Route::prefix('ads/schedule')->as('ads.schedule.')->controller(AmzAdsCampaignSchedulerController::class)->group(function () {
        Route::get('activeCampaigns', 'activeCampaigns')->name('activeCampaigns');
        Route::get('enable/{id}', 'enable')->name('enable');
        Route::post('runStatus', 'runStatus')->name('runStatus');

        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::put('update/{id}', 'update')->name('update');
    });

    Route::prefix('ads/budget')->as('ads.budget.')->controller(AdsBudgetController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/recommendations', 'recommendations')->name('recommendations');
    });

    Route::prefix('users')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Page Access in User Module
        |--------------------------------------------------------------------------
        */
        Route::prefix('permissions')->as('user.permissions.')->controller(UserPermissionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/edit/{user}', 'edit')->name('edit');
            Route::put('/update/{user}', 'update')->name('update');
        });
        /*
        |--------------------------------------------------------------------------
        | User Roles and Permissions
        |--------------------------------------------------------------------------
        */
        Route::prefix('roles')->name('roles.')->controller(RolesAndPermissionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{role}/edit', 'edit')->name('edit');
            Route::put('{role}', 'update')->name('update');
            Route::delete('/{role}', 'destroy')->name('destroy');

            Route::prefix('permissions')->name('permissions.')->group(function () {
                Route::get('/', 'permissions')->name('index');
                Route::get('/create', 'permissionCreate')->name('create');
                Route::post('/store', 'permissionStore')->name('store');
                Route::get('/edit/{id}', 'permissionEdit')->name('edit');
                Route::put('/update/{id}', 'permissionUpdate')->name('update');
                Route::delete('/delete/{id}', 'permissionsDestroy')->name('destroy');
            });
        });
    });
    
    /*
    |--------------------------------------------------------------------------
    | Assigning Asins in User Module
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignAsin')->as('assignAssin.')->controller(AssignAsinController::class)->group(function () {
        Route::get('create/{id}', 'assignAsin')->name('create');
        Route::get('search', 'search')->name('search');
        Route::get('example-asins', 'exampleExport')->name('example.asins');
        Route::post('store/{id}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
    });


    /*
    |--------------------------------------------------------------------------
    | Stock Module
    |--------------------------------------------------------------------------
    */
    Route::prefix('stocks')->as('stocks.')->controller(StocksController::class)->group(function () {
        Route::get('sku', 'skuStocks')->name('skuStocks');
        Route::get('exportSku', 'exportSku')->name('exportSku');
        Route::get('asin', 'asinStocks')->name('asinStocks');
        Route::get('exportAsin', 'exportAsin')->name('exportAsin');
    });

    Route::prefix('searchterms')->as('searchterms.')->controller(AmzAdsSearchTermsController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'downloadSpSearchTerms')->name('export');
        Route::get('/productAsins', 'productAsins')->name('productAsins');
    });

    Route::prefix('forecastperformance')->as('forecastperformance.')->controller(OrderForecastPerformanceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/clearCache', 'clearCache')->name('clearCache');
        Route::get('/export', 'export')->name('export');
    });

    
    /*
    |--------------------------------------------------------------------------
    | Ai chatbot route
    |--------------------------------------------------------------------------
    */
    Route::get('/ai/chat', AiPlayground::class)->name('ai.chat');
    Route::get('/ai/export', [AiExportController::class, 'download'])->name('ai.export');

    /*
    |--------------------------------------------------------------------------
    | Resource Routes
    |--------------------------------------------------------------------------
    */
    Route::resources([
        'products'          => ProductController::class,
        'sourcing'          => SourcingController::class,
        'users'             => UserController::class,
        'profile'           => ProfileController::class,
        'selling'           => SellingController::class,
        'warehouse'         => WarehouseController::class,
        'shipments'         => ShipmentController::class,
        'purchaseOrder'     => PurchaseOrderController::class,
        'currencies'        => CurrencyController::class,
        'orderforecast'     => OrderForecastController::class,
        'orderforecastasin' => OrderForecastAsinController::class,
        'data'              => DataController::class,
        'asin-selling'      => SellingAsinController::class,
        'assignAsin'        => AssignAsinController::class,
        // 'forecastperformance'       => OrderForecastPerformanceController::class,
    ]);
});


Route::prefix('dev')->as('dev.')->middleware('auth')->group(function () {
    // Developer routes
    Route::as('jobs.')->controller(JobMonitorController::class)->group(function () {
        Route::get('jobs', 'index')->name('index');
        Route::get('failed', 'failed')->name('failed');
    });
    // DB backup routes
    Route::as('backups.')->controller(DatabaseBackUpController::class)->group(function () {
        Route::get('database-backups', 'index')->name('index');
        Route::get('database-backups/download/{file}', 'download')->name('download');
    });
    // Cron Schedule table
    Route::get('schedule', [ScheduleController::class, 'index'])
        ->name('schedule.index');

    Route::get('login-history', [LoginController::class, 'loginHistory'])
        ->name('login-history.index');
});



Route::middleware('auth')->get('/logout', [LoginController::class, 'logout'])->name('logout');
