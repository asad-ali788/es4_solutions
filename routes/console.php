<?php

// ============================================================
// SP (Selling Partner API)
// ============================================================
use App\Console\Commands\PowerBi\SyncTopSearchBi;
use App\Console\Commands\Sp\AfnInventoryDataReport;
use App\Console\Commands\Sp\AfnInventoryDataReportSave;
use App\Console\Commands\Sp\CreateFeed;
use App\Console\Commands\Sp\DailySalesReport;
use App\Console\Commands\Sp\DailySalesReportSave;
use App\Console\Commands\Sp\FbaInventoryUsaReport;
use App\Console\Commands\Sp\FbaInventoryUsaReportSave;
use App\Console\Commands\Sp\GetCatalogItem;
use App\Console\Commands\Sp\GetCompetitivePricingReport;
use App\Console\Commands\Sp\GetInboundShipmentsReport;
use App\Console\Commands\Sp\GetProductPricingReport;
use App\Console\Commands\Sp\HourlyProductSalesReport;
use App\Console\Commands\Sp\HourlyProductSalesReportSave;
use App\Console\Commands\Sp\ListCatalogCategoryReport;
use App\Console\Commands\Sp\MonthlySalesReport;
use App\Console\Commands\Sp\NewProductsReport;
use App\Console\Commands\Sp\WeeklySalesReport;
use App\Console\Commands\Sp\WeeklySalesReportSave;

// ============================================================
// Ads
// ============================================================
use App\Console\Commands\Ads\AdGroupTargetBidRecommendation;
use App\Console\Commands\Ads\Ai\AiCampaignRecommendations;
use App\Console\Commands\Ads\Ai\AiKeywordRecommendations;
use App\Console\Commands\Ads\AmzCampaignUpdates;
use App\Console\Commands\Ads\AmzKeywordUpdates;
use App\Console\Commands\Ads\AmzScheduleCampaignUpdates;
use App\Console\Commands\Ads\AsinRecommendationsWeekly;
use App\Console\Commands\Ads\BrandKeywordGetReportSave;
use App\Console\Commands\Ads\BrandKeywordRequestReport;
use App\Console\Commands\Ads\CampaignGetReportSave;
use App\Console\Commands\Ads\CampaignKeywordRecommendation;
use App\Console\Commands\Ads\CampaignRecommendationsWeekly;
use App\Console\Commands\Ads\CampaignRequestReport;
use App\Console\Commands\Ads\CampaignSbGetReportSave;
use App\Console\Commands\Ads\CampaignSbRequestReport;
use App\Console\Commands\Ads\CampaignsBudgetUsage;
use App\Console\Commands\Ads\CampaignSdGetReportSave;
use App\Console\Commands\Ads\CampaignSdRequestReport;
use App\Console\Commands\Ads\DeleteOldTempReports;
use App\Console\Commands\Ads\DispatchDailyAdReportJobs;
use App\Console\Commands\Ads\GetSbBidRecommendations;
use App\Console\Commands\Ads\KeywordGetReportSave;
use App\Console\Commands\Ads\KeywordRecommendations;
use App\Console\Commands\Ads\KeywordRequestReport;
use App\Console\Commands\Ads\ListAdGroups;
use App\Console\Commands\Ads\ListAdGroupsSd;
use App\Console\Commands\Ads\ListCampaigns;
use App\Console\Commands\Ads\ListCampaignsSb;
use App\Console\Commands\Ads\ListCampaignsSd;
use App\Console\Commands\Ads\ListProductAds;
use App\Console\Commands\Ads\ListProductAdsSb;
use App\Console\Commands\Ads\ListProductAdsSd;
use App\Console\Commands\Ads\ListProductsKeywords;
use App\Console\Commands\Ads\ListProductsKeywordSb;
use App\Console\Commands\Ads\ListSponsoredBrandsTargetingClauses;
use App\Console\Commands\Ads\ListSponsoredProductsTargetingClauses;
use App\Console\Commands\Ads\ListTargetsSd;
use App\Console\Commands\Ads\ProductGetReportSave;
use App\Console\Commands\Ads\ProductGetReportSaveSd;
use App\Console\Commands\Ads\ProductRequestReport;
use App\Console\Commands\Ads\ProductRequestReportSd;
use App\Console\Commands\Ads\PurchasedProductGetReportSave;
use App\Console\Commands\Ads\PurchasedProductRequestReport;
use App\Console\Commands\Ads\RankedKeywordRecommendation;
use App\Console\Commands\Ads\RequestDailyAdReports;
use App\Console\Commands\Ads\SpSearchTermSummaryRequestReport;
use App\Console\Commands\Ads\SpSearchTermSummaryRequestReportSave;
use App\Console\Commands\Ads\TargetsSbRequestReport;
use App\Console\Commands\Ads\TargetsSbRequestReportSave;
use App\Console\Commands\Ads\TargetsSdRequestReport;
use App\Console\Commands\Ads\TargetsSdRequestReportSave;
use App\Console\Commands\Ads\Updates\BrandKeywordUpdateReportSave;
use App\Console\Commands\Ads\Updates\CampaignSbUpdateReportSave;
use App\Console\Commands\Ads\Updates\CampaignSdUpdateReportSave;
use App\Console\Commands\Ads\Updates\CampaignUpdateReportSave;
use App\Console\Commands\Ads\Updates\KeywordUpdateReportSave;
use App\Console\Commands\Ads\Updates\ProductUpdateReportSave;
use App\Console\Commands\Ads\Updates\ProductUpdateReportSdSave;
use App\Console\Commands\Ads\Updates\PurchasedProductUpdateReportSave;

// ============================================================
// Forecast
// ============================================================
use App\Console\Commands\Forecast\AggregateSkuAsinMetrics;
use App\Console\Commands\Forecast\ProcessSubMonthForecastMetrics;
use App\Console\Commands\Forecast\ProcessSubMonthForecastMetricsAsin;
use App\Console\Commands\Forecast\SyncDailyAdsProductPerformance;

// ============================================================
// Warehouse
// ============================================================
use App\Console\Commands\Wh\AwdWHInventory;
use App\Console\Commands\Wh\ShipOutProdWHInventory;
use App\Console\Commands\Wh\ShipOutQueryList;
use App\Console\Commands\Wh\TacticalWHInventory;

// ============================================================
// Monitoring
// ============================================================
use App\Console\Commands\Monitoring\CheckDailyDataAvailability;

// ============================================================
// PowerBI
// ============================================================
use App\Console\Commands\PowerBi\ProductCategorisationSync;
use App\Console\Commands\PowerBi\SyncAdvertisedProductBi;
use App\Console\Commands\PowerBi\SyncAsinInventorySummaryBi;
use App\Console\Commands\PowerBi\SyncBrandAnalyticsWeeklyDataBi;
use App\Console\Commands\PowerBi\SyncBrandAnalytics2024Bi;
use App\Console\Commands\PowerBi\SyncNightSupportCampaignBi;
use App\Console\Commands\PowerBi\SyncCompetitorRank360Bi;
use App\Console\Commands\PowerBi\SyncKeywordRankReport360Bi;
use App\Console\Commands\PowerBi\SyncModifiedCategoryRankHistoryBi;
use App\Console\Commands\PowerBi\SyncModifiedSubCategoryRankHistoryBi;
use App\Console\Commands\PowerBi\SyncSearchTermReportBi;
use App\Console\Commands\PowerBi\SyncTargetingReportBi;

// ============================================================
// AI / Cache Sync
// ============================================================
use App\Console\Commands\PowerBi\SyncCompMappingBi;
use App\Console\Commands\Ai\SyncAmzKeywordRecommendationsLite;
use App\Console\Commands\Ai\SyncCampaignPerformanceLite;
use App\Console\Commands\SyncUnifiedPerformanceLite;

// ============================================================
// Other / Shared
// ============================================================
use App\Console\Commands\CleanOldReports;
use App\Console\Commands\RetryTokenFailedJobs;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Commands Definition
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| 🛒 SP — Selling Partner API
|--------------------------------------------------------------------------
| Sales reports, FBA/AFN inventory, pricing, catalog, and feeds.
*/

// Hourly Sales
Schedule::command(HourlyProductSalesReport::class)->hourlyAt(32);
Schedule::command(HourlyProductSalesReportSave::class)->hourlyAt(37);

// Daily Sales
Schedule::command(DailySalesReport::class)->dailyAt('13:35');
Schedule::command(DailySalesReportSave::class)->dailyAt('13:50');

// Weekly Sales (Every Monday)
Schedule::command(WeeklySalesReport::class)->weeklyOn(1, '03:10');
Schedule::command(WeeklySalesReportSave::class)->weeklyOn(1, '03:30');

// Monthly Sales (On the 2nd)
Schedule::command(MonthlySalesReport::class)->monthlyOn(2, '04:00');
Schedule::exec("php -d memory_limit=1024M artisan app:monthly-sales-report-save")
    ->monthlyOn(2, '05:00');

// FBA / AFN Inventory
Schedule::command(FbaInventoryUsaReport::class)->hourlyAt(2);
Schedule::command(FbaInventoryUsaReportSave::class)->hourlyAt(34);
Schedule::command(AfnInventoryDataReport::class)->hourlyAt(15);
Schedule::command(AfnInventoryDataReportSave::class)->hourlyAt(45);

// Catalog & Product Data
Schedule::command(GetCatalogItem::class)->dailyAt('20:40');
Schedule::command(ListCatalogCategoryReport::class)
    ->dailyAt('22:00')
    ->withoutOverlapping()
    ->runInBackground();
Schedule::command(NewProductsReport::class)->dailyAt('22:00');

// Pricing & Competitor Data
Schedule::command(GetProductPricingReport::class)->everyFourHours(10);
Schedule::command(GetCompetitivePricingReport::class)->dailyAt('06:30');
Schedule::command(GetInboundShipmentsReport::class)->everyFourHours(40);

// Feed Submissions (Price Updates)
Schedule::command(CreateFeed::class)
    ->cron('2,17,32,47 * * * *')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 🎯 Ads — Amazon Advertising
|--------------------------------------------------------------------------
| Metadata sync, performance reports, recommendations, and correction cycle.
*/

// --- Live Status Updates ---
Schedule::command(AmzCampaignUpdates::class)->everyTenMinutes();
Schedule::command(AmzKeywordUpdates::class)->everyTenMinutes();
Schedule::command(AmzScheduleCampaignUpdates::class)->everyThirtyMinutes();
Schedule::command(RequestDailyAdReports::class)->everyOddHour($minutes = 54);
Schedule::command(DispatchDailyAdReportJobs::class)->hourlyAt(22);

// --- List Fetching (Campaigns / Ad Groups / Keywords) ---
Schedule::command(ListCampaigns::class)->cron('30 2,5,8,13,16,19 * * *')->withoutOverlapping();
Schedule::command(ListCampaignsSd::class)->cron('35 2,5,8,13,16,19 * * *')->withoutOverlapping();
Schedule::command(ListCampaignsSb::class)->cron('40 2,5,8,13,16,19 * * *')->withoutOverlapping();

Schedule::command(ListProductAds::class)->twiceDaily(7, 19);
Schedule::command(ListProductAdsSb::class)->twiceDaily(7, 19);
Schedule::command(ListProductAdsSd::class)->twiceDaily(10, 22);

Schedule::command(ListProductsKeywords::class)->cron('45 2,5,8,13,15,22 * * *')->withoutOverlapping();
Schedule::command(ListProductsKeywordSb::class)->cron('50 2,5,8,13,15,22 * * *')->withoutOverlapping();

Schedule::command(ListAdGroups::class)->twiceDailyAt(1, 13, 32);
Schedule::command(ListAdGroupsSd::class)->twiceDaily(11, 23);
Schedule::command(ListTargetsSd::class)->twiceDaily(12, 0);

Schedule::command(ListSponsoredProductsTargetingClauses::class)->twiceDaily(6, 18);
Schedule::command(ListSponsoredBrandsTargetingClauses::class)->twiceDaily(8, 23)->withoutOverlapping();

// --- Performance Reports (SP / SB / SD) ---

// Campaign Reports
Schedule::command(CampaignRequestReport::class)->dailyAt('13:40')->withoutOverlapping();
Schedule::command(CampaignGetReportSave::class)->hourlyAt(50)->between('13:30', '20:00');
Schedule::command(CampaignUpdateReportSave::class)->hourly()->between('2:00', '05:00');

Schedule::command(CampaignSbRequestReport::class)->dailyAt('13:38')->withoutOverlapping();
Schedule::command(CampaignSbGetReportSave::class)->hourlyAt(05)->between('13:30', '20:00');
Schedule::command(CampaignSbUpdateReportSave::class)->hourly()->between('2:10', '05:00');

Schedule::command(CampaignSdRequestReport::class)->dailyAt('13:40')->withoutOverlapping();
Schedule::command(CampaignSdGetReportSave::class)->hourlyAt(52)->between('13:30', '20:00');
Schedule::command(CampaignSdUpdateReportSave::class)->hourly()->between('2:10', '05:00');

// Product Reports (SP + SD)
Schedule::command(ProductRequestReport::class)->dailyAt('13:45')->withoutOverlapping();
Schedule::command(ProductGetReportSave::class)->hourlyAt(20)->between('13:30', '20:00');
Schedule::command(ProductUpdateReportSave::class)->hourly()->between('2:15', '05:00');

Schedule::command(ProductRequestReportSd::class)->dailyAt('13:50')->withoutOverlapping();
Schedule::command(ProductGetReportSaveSd::class)->hourlyAt(40)->between('13:50', '20:00');
Schedule::command(ProductUpdateReportSdSave::class)->hourly()->between('2:15', '05:00');

// Keyword Reports
Schedule::command(KeywordRequestReport::class)->dailyAt('14:30')->withoutOverlapping();
Schedule::command(KeywordGetReportSave::class)->hourlyAt(17)->between('14:30', '20:00');
Schedule::command(KeywordUpdateReportSave::class)->hourly()->between('2:15', '05:00');

Schedule::command(BrandKeywordRequestReport::class)->dailyAt('14:40');
Schedule::command(BrandKeywordGetReportSave::class)->hourlyAt(24)->between('14:30', '21:00');
Schedule::command(BrandKeywordUpdateReportSave::class)->hourly()->between('2:35', '05:00');

// Target Reports (SD + SB)
Schedule::command(TargetsSdRequestReport::class)->dailyAt('15:03');
Schedule::command(TargetsSdRequestReportSave::class)->hourlyAt(43);
Schedule::command(TargetsSbRequestReport::class)->dailyAt('15:05');
Schedule::command(TargetsSbRequestReportSave::class)->hourlyAt(47);

// Purchased Product Reports (SB)
Schedule::command(PurchasedProductRequestReport::class)->dailyAt('20:00');
Schedule::command(PurchasedProductGetReportSave::class)->hourlyAt(27)->between('19:00', '23:59');
Schedule::command(PurchasedProductUpdateReportSave::class)->dailyAt('02:55');

// Search Term Reports
Schedule::command(SpSearchTermSummaryRequestReport::class)->dailyAt(15);
Schedule::command(SpSearchTermSummaryRequestReportSave::class)->hourlyAt(23)->between('15:30', '19:00');

// --- Correction Cycle (2-Day Offset) ---
// Requests correction data for 2 days ago to account for Amazon finalization lag
Schedule::call(function () {
    $date = now(config('timezone.market'))->subDays(2)->toDateString();
    Log::channel('ads')->info("🔄 Requesting correction reports for date: $date");
    Artisan::call('app:product-request-report-sd', ['targetDate' => $date]);
    Artisan::call('app:purchased-product-request-report', ['targetDate' => $date]);
})->dailyAt('02:05');

// --- Recommendations & Bidding ---
Schedule::command(AsinRecommendationsWeekly::class)->weeklyOn(1, '16:00');
Schedule::command(CampaignRecommendationsWeekly::class)->hourlyAt(0)->between('13:30', '20:00');
Schedule::command(RankedKeywordRecommendation::class)->twiceDailyAt(1, 13, 35)->withoutOverlapping();
Schedule::command(KeywordRecommendations::class)->twiceDailyAt(3, 15, 35);
Schedule::command(CampaignKeywordRecommendation::class)->twiceDailyAt(3, 13, 15);
Schedule::command(AdGroupTargetBidRecommendation::class)->dailyAt('01:00');
Schedule::command(GetSbBidRecommendations::class)->twiceDaily(7, 19)->withoutOverlapping();
Schedule::command(CampaignsBudgetUsage::class)->twiceDailyAt(3, 13, 10);

// Ads Cleanup
Schedule::command(DeleteOldTempReports::class)->dailyAt('12:30');

/*
|--------------------------------------------------------------------------
| 🔮 Forecast
|--------------------------------------------------------------------------
| Monthly SKU/ASIN forecast metric aggregation.
*/

Schedule::command(AggregateSkuAsinMetrics::class)->monthlyOn(2, '21:10');
Schedule::command(ProcessSubMonthForecastMetrics::class)->monthlyOn(2, '21:25');
Schedule::command(ProcessSubMonthForecastMetricsAsin::class)->monthlyOn(2, '21:40');
Schedule::command(SyncDailyAdsProductPerformance::class)->twiceDaily(15, 20);

// Lock forecast metrics at start of month
Schedule::call(function () {
    DB::transaction(function () {
        App\Models\OrderForecastMetric::query()->update(['is_not_ready' => 1]);
        App\Models\OrderForecastMetricAsins::query()->update(['is_not_ready' => 1]);
    });
    Log::info('🔒 Forecast metrics locked');
})->monthlyOn(1, '13:00');

/*
|--------------------------------------------------------------------------
| 🏭 Warehouse
|--------------------------------------------------------------------------
| Shipout lists and warehouse inventory syncs.
*/

Schedule::command(ShipOutQueryList::class)->dailyAt('05:45');
Schedule::command(ShipOutProdWHInventory::class)->everyFourHours(20);
Schedule::command(TacticalWHInventory::class)->everyFourHours(5);
Schedule::command(AwdWHInventory::class)->everyTwoHours(16);
Schedule::command(SyncAsinInventorySummaryBi::class)->hourlyAt(12);

/*
|--------------------------------------------------------------------------
| 📡 Monitoring
|--------------------------------------------------------------------------
| Data availability checks and integrity alerts.
*/

Schedule::command(CheckDailyDataAvailability::class)->twiceDailyAt(16, 17, 0)->timezone('Asia/Kolkata');

/*
|--------------------------------------------------------------------------
| 📊 PowerBI
|--------------------------------------------------------------------------
| Syncs data to PowerBI reporting tables.
*/

// Schedule::command(SyncTargetingReportBi::class)->hourlyAt(10);
// Schedule::command(SyncNightSupportCampaignBi::class)->hourlyAt(18);
// Schedule::command(SyncAdvertisedProductBi::class)->twiceDaily(1, 15);
// Schedule::command(SyncSearchTermReportBi::class)->everyTwoHours();
// Schedule::command(SyncBrandAnalyticsWeeklyDataBi::class)->weeklyOn(1, '04:00');
// Schedule::command(SyncBrandAnalytics2024Bi::class)->weeklyOn(1, '04:30');
// Schedule::command(SyncKeywordRankReport360Bi::class)->twiceDaily(5, 23);
// Schedule::command(SyncCompetitorRank360Bi::class)->twiceDaily(6, 18);
// Schedule::command(SyncModifiedCategoryRankHistoryBi::class)->twiceDaily(7, 19);
// Schedule::command(SyncModifiedSubCategoryRankHistoryBi::class)->twiceDaily(7, 19);
// Schedule::command(ProductCategorisationSync::class)->weeklyOn(2, '06:00');
// Schedule::command(SyncTopSearchBi::class)->weeklyOn(2, '06:30');
// Schedule::command(SyncCompMappingBi::class)->weeklyOn(1, '05:00');

/*
|--------------------------------------------------------------------------
| 🤖 AI / Cache Sync
|--------------------------------------------------------------------------
| Syncs denormalized Lite tables used by AI reporting tools.
*/

Schedule::command(SyncUnifiedPerformanceLite::class)->twiceDaily(4, 18);
Schedule::command(SyncCampaignPerformanceLite::class)->twiceDailyAt(3, 15, 12);
Schedule::command(SyncAmzKeywordRecommendationsLite::class)->twiceDaily(7, 20);

/*
|--------------------------------------------------------------------------
| 🧹 Other — Maintenance & Housekeeping
|--------------------------------------------------------------------------
*/

Schedule::command(CleanOldReports::class)->sundays();
Schedule::command(RetryTokenFailedJobs::class)->everyThreeHours();

Schedule::command('backup:run --only-db')->weeklyOn(1, '01:00');
Schedule::command('backup:clean')->weeklyOn(1, '01:10');

Schedule::call(function () {
    Log::info('🔥 CRON TEST RAN SUCCESS at ' . now());
})->hourly();