<?php

use App\Console\Commands\Ads\AdGroupTargetBidRecommendation;
use App\Console\Commands\Ads\Ai\AiCampaignRecommendations;
use App\Console\Commands\Ads\Ai\AiKeywordRecommendations;
use App\Console\Commands\Ads\AmzCampaignUpdates;
use App\Console\Commands\Ads\AmzKeywordUpdates;
use App\Console\Commands\Ads\AmzScheduleCampaignUpdates;
use App\Console\Commands\Ads\AsinRecommendationsWeekly;
use App\Console\Commands\Ads\BrandKeywordGetReportSave;
use App\Console\Commands\Ads\BrandKeywordRequestReport;
use App\Console\Commands\Sp\AfnInventoryDataReport;
use App\Console\Commands\Sp\AfnInventoryDataReportSave;
use App\Console\Commands\CleanOldReports;
use App\Console\Commands\Sp\CreateFeed;
use App\Console\Commands\Sp\DailySalesReport;
use App\Console\Commands\Sp\DailySalesReportSave;
use App\Console\Commands\Sp\FbaInventoryUsaReport;
use App\Console\Commands\Sp\FbaInventoryUsaReportSave;
use App\Console\Commands\Sp\GetCatalogItem;
use App\Console\Commands\Sp\ListCatalogCategoryReport;
use App\Console\Commands\Sp\GetProductPricingReport;
use App\Console\Commands\Sp\GetInboundShipmentsReport;
use App\Console\Commands\Sp\MonthlySalesReport;
use App\Console\Commands\Sp\WeeklySalesReport;
use App\Console\Commands\Sp\WeeklySalesReportSave;

use App\Console\Commands\Wh\ShipOutProdWHInventory;
use App\Console\Commands\Wh\ShipOutQueryList;
use App\Console\Commands\Wh\TacticalWHInventory;

use App\Console\Commands\Ads\ListAdGroups;
use App\Console\Commands\Ads\ListCampaigns;
use App\Console\Commands\Ads\ListProductAds;
use App\Console\Commands\Ads\ListProductsKeywords;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\Ads\CampaignRequestReport;
use App\Console\Commands\Ads\CampaignGetReportSave;
use App\Console\Commands\Ads\CampaignKeywordRecommendation;
use App\Console\Commands\Ads\CampaignRecommendationsWeekly;
use App\Console\Commands\Ads\CampaignSbGetReportSave;
use App\Console\Commands\Ads\CampaignSbRequestReport;
use App\Console\Commands\Ads\CampaignSdGetReportSave;
use App\Console\Commands\Ads\CampaignSdRequestReport;
use App\Console\Commands\Ads\DeleteOldTempReports;
use App\Console\Commands\Ads\DispatchDailyAdReportJobs;
use App\Console\Commands\Ads\KeywordGetReportSave;
use App\Console\Commands\Ads\KeywordRecommendations;
use App\Console\Commands\Ads\KeywordRequestReport;
use App\Console\Commands\Ads\ListAdGroupsSd;
use App\Console\Commands\Ads\ListCampaignsSb;
use App\Console\Commands\Ads\ListCampaignsSd;
use App\Console\Commands\Ads\ListProductAdsSb;
use App\Console\Commands\Ads\ListProductAdsSd;
use App\Console\Commands\Ads\ListProductsKeywordSb;
use App\Console\Commands\Ads\ListTargetsSd;
use App\Console\Commands\Ads\ProductGetReportSave;
use App\Console\Commands\Ads\ProductGetReportSaveSd;
use App\Console\Commands\Ads\ProductRequestReport;
use App\Console\Commands\Ads\ProductRequestReportSd;
use App\Console\Commands\Ads\PurchasedProductGetReportSave;
use App\Console\Commands\Ads\PurchasedProductRequestReport;
use App\Console\Commands\Ads\RankedKeywordRecommendation;
use App\Console\Commands\Ads\RequestDailyAdReports;
use App\Console\Commands\Ads\TargetsSdRequestReport;
use App\Console\Commands\Ads\TargetsSdRequestReportSave;
use App\Console\Commands\Forecast\ProcessSubMonthForecastMetrics;
use App\Console\Commands\Forecast\ProcessSubMonthForecastMetricsAsin;
use App\Console\Commands\RetryTokenFailedJobs;
use App\Console\Commands\Sp\GetCompetitivePricingReport;
use App\Console\Commands\Sp\HourlyProductSalesReport;
use App\Console\Commands\Sp\HourlyProductSalesReportSave;
use App\Console\Commands\Wh\AwdWHInventory;
use App\Console\Commands\Ads\TargetsSbRequestReport;
use App\Console\Commands\Ads\TargetsSbRequestReportSave;
use App\Console\Commands\Ads\ListSponsoredProductsTargetingClauses;
use App\Console\Commands\Ads\GetSbBidRecommendations;
use App\Console\Commands\Ads\ListSponsoredBrandsTargetingClauses;
use App\Console\Commands\Ads\SpSearchTermSummaryRequestReport;
use App\Console\Commands\Ads\SpSearchTermSummaryRequestReportSave;
use App\Console\Commands\Forecast\AggregateSkuAsinMetrics;
use App\Console\Commands\Forecast\SyncDailyAdsProductPerformance;
use App\Console\Commands\Sp\NewProductsReport;
use App\Console\Commands\PowerBi\ProductCategorisationSync;
use App\Console\Commands\Monitoring\CheckDailyDataAvailability;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Ads\CampaignsBudgetUsage;
use App\Console\Commands\SyncUnifiedPerformanceLite;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * 🕐 Hourly Sales Report (10 mins job)
 */
// Schedule::command(HourlySalesReport::class)->hourlyAt(25);
// Schedule::command(HourlySalesReportSave::class)->hourlyAt(30); // Move all logic to HourlyProductSales

Schedule::command(HourlyProductSalesReport::class)->hourlyAt(32);
Schedule::command(HourlyProductSalesReportSave::class)->hourlyAt(37);
/**
 * 📊 Daily Sales Report (lightweight)
 * Run after 12:30 PM IST so it completes before 1 PM.
 */
Schedule::command(DailySalesReport::class)->dailyAt('13:35');
Schedule::command(DailySalesReportSave::class)->dailyAt('13:50');


/**
 * 📈 Weekly Sales Report (Monday only)
 */
Schedule::command(WeeklySalesReport::class)->weeklyOn(1, '03:10');
Schedule::command(WeeklySalesReportSave::class)->weeklyOn(1, '03:30');

/**
 * 📅 Monthly Report (on 2nd)
 */
Schedule::command(MonthlySalesReport::class)->monthlyOn(2, '04:00');
Schedule::exec("php -d memory_limit=1024M artisan app:monthly-sales-report-save")
    ->monthlyOn(2, '05:00');

/**
 * 🏷️ AFN Inventory (3-hourly)
 */
Schedule::command(AfnInventoryDataReport::class)->hourlyAt(15);
Schedule::command(AfnInventoryDataReportSave::class)->hourlyAt(45); // sync

/**
 * 🇺🇸 FBA Inventory USA
 */
Schedule::command(FbaInventoryUsaReport::class)->hourlyAt(0);
Schedule::command(FbaInventoryUsaReportSave::class)->hourlyAt(30);

/**
 * 🗂️ Catalog Category (heavy)
 */
Schedule::command(ListCatalogCategoryReport::class)
    ->dailyAt('22:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(GetCatalogItem::class)->dailyAt('20:40');

/**
 * 💰 Product Pricing slow job
 */
Schedule::command(GetProductPricingReport::class)->everyFourHours(10); // long-running

/**
 * ⚔️ Competitive Pricing
 */
Schedule::command(GetCompetitivePricingReport::class)->dailyAt('06:30'); // long-running

/**
 * 📦 Inbound Shipments
 */
Schedule::command(GetInboundShipmentsReport::class)->everyFourHours(40);

/**
 * 🚚 Shipout
 */
Schedule::command(ShipOutQueryList::class)->dailyAt('05:45');
Schedule::command(ShipOutProdWHInventory::class)->everyFourHours(20);

/**
 * 🏭 Tactical WH
 */
Schedule::command(TacticalWHInventory::class)->everyFourHours(5);

/**
 * 🏭 AWD WH
 */
Schedule::command(AwdWHInventory::class)->everyFourHours(16);

/**
 * 📤 New Products
 */
Schedule::command(NewProductsReport::class)->dailyAt('22:00');

/**
 * 📤 Feed Submission
 */
Schedule::command(CreateFeed::class)
    ->cron('2,17,32,47 * * * *')
    ->withoutOverlapping();

// Schedule::command(CreateFeedSave::class)
//     ->cron('9,24,39,54 * * * *')
//     ->withoutOverlapping();

/**
 * 📈 Performance Recommendations
 */

Schedule::command(AsinRecommendationsWeekly::class)->weeklyOn(1, '16:00');
// Campaign Recommendation SP/SB/SD
Schedule::command(CampaignRecommendationsWeekly::class)->hourlyAt(0)->between('13:30', '20:00');
// Schedule::command(AiCampaignRecommendations::class)->hourlyAt(20);
// keyword Recommendation
Schedule::command(RankedKeywordRecommendation::class)->twiceDailyAt(1, 13, 35)->withoutOverlapping();
Schedule::command(KeywordRecommendations::class)->twiceDailyAt(3, 15, 35);
// Schedule::command(AiKeywordRecommendations::class)->twiceDailyAt(3, 15, 40);
// Overview Campaign keyword Recommendation
Schedule::command(CampaignKeywordRecommendation::class)->dailyAt('15:00');

// Missing data alert for key daily datasets (IST: 4:00 PM and 5:00 PM)
Schedule::command(CheckDailyDataAvailability::class)->twiceDailyAt(16, 17, 0)->timezone('Asia/Kolkata');

Schedule::command(CampaignsBudgetUsage::class)->twiceDaily(3, 13, 10);
Schedule::command(CampaignKeywordRecommendation::class)->twiceDaily(3, 13, 15);

/**
 * 🎯 Ads
 */

Schedule::command(AmzCampaignUpdates::class)->everyTenMinutes();
Schedule::command(AmzKeywordUpdates::class)->everyTenMinutes(10);

Schedule::command(ListCampaigns::class)->cron('30 2,5,8,13,15,19 * * *')->withoutOverlapping();
Schedule::command(ListCampaignsSd::class)->cron('35 2,5,8,13,15,19 * * *')->withoutOverlapping();
Schedule::command(ListCampaignsSb::class)->cron('40 2,5,8,13,15,19 * * *')->withoutOverlapping();

Schedule::command(ListProductAdsSb::class)->twiceDaily(7, 19);
Schedule::command(ListProductAds::class)->twiceDaily(7, 19);
Schedule::command(ListProductAdsSd::class)->twiceDaily(10, 22);

Schedule::command(ListProductsKeywords::class)->cron('45 2,5,8,13,15,19 * * *')->withoutOverlapping();
Schedule::command(ListProductsKeywordSb::class)->cron('50 2,5,8,13,15,19 * * *')->withoutOverlapping();

Schedule::command(ListAdGroups::class)->twiceDaily(1, 13, 32);
Schedule::command(ListAdGroupsSd::class)->twiceDaily(11, 23);
Schedule::command(ListTargetsSd::class)->twiceDaily(12, 0);

/**
 * 📈 Ads Reports
 */

// --- Ads Reports: Non-overlapping schedule ---

// amz_ads_campaign_performance_report
Schedule::command(CampaignRequestReport::class)->dailyAt('13:40')->withoutOverlapping();
Schedule::command(CampaignGetReportSave::class)->hourlyAt(50)->between('13:30', '20:00');

Schedule::command(CampaignSbRequestReport::class)->dailyAt('13:38')->withoutOverlapping();
Schedule::command(CampaignSbGetReportSave::class)->hourlyAt(05)->between('13:30', '20:00');

Schedule::command(CampaignSdRequestReport::class)->dailyAt('13:40')->withoutOverlapping();
Schedule::command(CampaignSdGetReportSave::class)->hourlyAt(50)->between('13:30', '20:00');

// 
Schedule::command(ProductRequestReport::class)->dailyAt('13:45')->withoutOverlapping();
Schedule::command(ProductGetReportSave::class)->hourlyAt(20)->between('13:30', '20:00');

Schedule::command(ProductRequestReportSd::class)->dailyAt('13:50')->withoutOverlapping();
Schedule::command(ProductGetReportSaveSd::class)->hourlyAt(40)->between('13:50', '20:00');

// 
Schedule::command(KeywordRequestReport::class)->dailyAt('14:30')->withoutOverlapping();
Schedule::command(KeywordGetReportSave::class)->hourlyAt(17)->between('14:30', '20:00');

Schedule::command(BrandKeywordRequestReport::class)->dailyAt('14:40');
Schedule::command(BrandKeywordGetReportSave::class)->hourlyAt(22)->between('14:30', '21:00');

Schedule::command(PurchasedProductRequestReport::class)->dailyAt('20:00');
Schedule::command(PurchasedProductGetReportSave::class)->hourlyAt(27)->between('19:00', '23:59');



Schedule::command(TargetsSdRequestReport::class)->dailyAt('15:03');
Schedule::command(TargetsSdRequestReportSave::class)->hourlyAt(42);

Schedule::command(TargetsSbRequestReport::class)->dailyAt('15:05');
Schedule::command(TargetsSbRequestReportSave::class)->hourlyAt(47);

/**
 * 🕑 Run every 2 hours:
 * First request the reports, then dispatch polling jobs 15 mins later
 */

Schedule::command(AmzScheduleCampaignUpdates::class)->everyThirtyMinutes();

Schedule::command(RequestDailyAdReports::class)->everyOddHour($minutes = 50);
Schedule::command(DispatchDailyAdReportJobs::class)->hourlyAt(22);

Schedule::command(DeleteOldTempReports::class)->dailyAt('12:30');

// Deletes report files older than 14 days
Schedule::command(CleanOldReports::class)->sundays();
Schedule::command(RetryTokenFailedJobs::class)->everyThreeHours();

Schedule::command(ListSponsoredProductsTargetingClauses::class)->twiceDaily(6, 18); // 6 AM & 6 PM
Schedule::command(GetSbBidRecommendations::class)->twiceDaily(7, 19)->withoutOverlapping();
Schedule::command(ListSponsoredBrandsTargetingClauses::class)->twiceDaily(8, 23)->withoutOverlapping();

Schedule::command(AdGroupTargetBidRecommendation::class)->dailyAt('01:00');

// Schedule::command(CampaignRequestLastTwoDays::class)->dailyAt('05:00');
// Schedule::command(CampaignGetLastTwoDaysReportSave::class)->hourlyAt(15)   

Schedule::command(SpSearchTermSummaryRequestReport::class)->twiceDaily(3, 15);
Schedule::command(SpSearchTermSummaryRequestReportSave::class)->hourlyAt(20);

Schedule::command('backup:run --only-db')->weeklyOn(1, '01:00');
Schedule::command('backup:clean')->weeklyOn(1, '01:10');
// Schedule::command('pulse:check')->everyMinute();

Schedule::command(SyncDailyAdsProductPerformance::class)->twiceDaily(15, 20);

Schedule::command(AggregateSkuAsinMetrics::class)->monthlyOn(2, '21:10');
Schedule::command(ProcessSubMonthForecastMetrics::class)->monthlyOn(2, '21:25');
Schedule::command(ProcessSubMonthForecastMetricsAsin::class)->monthlyOn(2, '21:40');


// PowerBI API
Schedule::command(ProductCategorisationSync::class)->weeklyOn(2, '06:00');


Schedule::call(function () {
    Log::info('🔥 CRON TEST RAN SUCCESS at ' . now());
})->hourly();

Schedule::call(function () {
    DB::transaction(function () {
        App\Models\OrderForecastMetric::query()->update(['is_not_ready' => 1]);
        App\Models\OrderForecastMetricAsins::query()->update(['is_not_ready' => 1]);
    });

    Log::info('🔒 Forecast metrics locked');
})->monthlyOn(1, '13:00');

/**
 * 🚀 Sync Campaign Performance Lite (SQLite cache)
 * Runs hourly to sync campaign data from CampaignRecommendations to CampaignPerformanceLite
 * Prevents duplicate entries and maintains a fast query table for AI analysis
 */
Schedule::command(SyncUnifiedPerformanceLite::class)
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/campaign-performance-lite.log'));

