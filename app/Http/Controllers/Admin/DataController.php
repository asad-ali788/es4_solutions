<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\DataEnum;
use App\Exports\AdsCampaignPerformanceSummaryExport;
use App\Exports\AdsKeywordPerformanceReportExport;
use App\Exports\AdsPerformanceByProductCampaignExport;
use App\Exports\AdsPerformanceSummaryByAsinExport;
use App\Exports\AdsPerformanceSummaryExport;
use App\Exports\AdsPerformanceSummaryExportSd;
use App\Exports\AsinPerformanceReportExport;
use App\Exports\CampaignPerformanceExport;
use App\Exports\CartonSizeExport;
use App\Exports\CombinedAdsPerformanceExport;
use App\Exports\ItemPriceExport;
use App\Exports\KeywordRecommendationsExport;
use App\Exports\KeywordRecommendationsLast7DaysExport;
use App\Exports\RankingReportExport;
use App\Exports\LibraryImagesExport;
use App\Exports\MasterDataExport;
use App\Exports\orderForecastFinaliseExport;
use App\Exports\salesDailyReportExport;
use App\Exports\salesMonthlyReportExport;
use App\Exports\StockRunDownReportExport;
use App\Exports\StocksExportAsin;
use App\Exports\WarehousesAsinExport;
use App\Exports\WarehouseStockExport;
use App\Exports\WeeklySalesPerformaceReportExport;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\OrderForecastPerformanceService;
use Illuminate\Http\Request;
class DataController extends Controller
{
    public function index()
    {
        $this->authorize(DataEnum::Data);
        $currencies = Currency::orderBy('country_code')->paginate(10);
        return view('pages.admin.data.index', compact('currencies'));
    }

    public function itemPriceDownload()
    {
        $this->authorize(DataEnum::DataDownloadUSMaster);
        try {
            return Excel::download(new ItemPriceExport(), 'item_price_export_' . now()->timestamp . '.xlsx');
        } catch (Exception $e) {
            Log::error('ItemPriceExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading. Please contact admin.');
        }
    }

    public function masterDataDownload()
    {
        $this->authorize(DataEnum::DataDownloadUSMaster);
        try {
            return Excel::download(new MasterDataExport(), 'master_data_export_' . now()->timestamp . '.xlsx');
        } catch (Exception $e) {
            Log::error('MasterDataExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading. Please contact admin.');
        }
    }

    public function libraryImagesDownload()
    {
        $this->authorize(DataEnum::DataDownloadUSMaster);
        try {
            return Excel::download(new LibraryImagesExport(), 'library_images_export_' . now()->timestamp . '.xlsx');
        } catch (Exception $e) {
            Log::error('LibraryImagesExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading. Please contact admin.');
        }
    }

    public function cartonSizeDownload()
    {
        $this->authorize(DataEnum::DataDownloadUSMaster);
        try {
            return Excel::download(new CartonSizeExport(), 'carton_size_export_' . now()->timestamp . '.xlsx');
        } catch (Exception $e) {
            Log::error('CartonSizeExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading. Please contact admin.');
        }
    }

    public function adsPerformanceLast7Days()
    {
        $this->authorize(DataEnum::DataDownloadAdsCampaignPerformance);

        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsPerformance(
            $today->copy()->subDays(6)->toDateString(),
            $today->toDateString(),
            'ads_performance_last7days'
        );
    }

    public function adsPerformanceLast4Weeks()
    {
        $this->authorize(DataEnum::DataDownloadAdsCampaignPerformance);
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsPerformance(
            $today->copy()->subWeeks(4)->toDateString(),
            $today->toDateString(),
            'ads_performance_last4weeks',
            'week'
        );
    }

    public function adsPerformanceLast3Months()
    {
        $this->authorize(DataEnum::DataDownloadAdsCampaignPerformance);
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsPerformance(
            $today->copy()->subMonths(3)->startOfMonth()->toDateString(),
            $today->copy()->subMonth()->endOfMonth()->toDateString(),
            'ads_performance_last3months',
            'month'
        );
    }


    private function exportAdsPerformance($start, $end, $filename, $grouping = 'day')
    {
        try {
            return Excel::download(
                new AdsPerformanceSummaryExport($start, $end, 'US', $grouping),
                $filename . '_' . now()->timestamp . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('AdsPerformanceExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the report.');
        }
    }


    public function adsPerformanceSdLast7Days()
    {
        $this->authorize(DataEnum::DataDownloadPerformanceReport);
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsPerformanceSd(
            $today->copy()->subDays(6)->toDateString(),
            $today->toDateString(),
            'ads_performance_sd_last7days'
        );
    }

    public function adsPerformanceSdLast4Weeks()
    {
        $this->authorize(DataEnum::DataDownloadPerformanceReport);
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsPerformanceSd(
            $today->copy()->subWeeks(4)->toDateString(),
            $today->toDateString(),
            'ads_performance_sd_last4weeks',
            'week'
        );
    }

    public function adsPerformanceSdLast3Months()
    {
        $this->authorize(DataEnum::DataDownloadPerformanceReport);
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsPerformanceSd(
            $today->copy()->subMonths(3)->startOfMonth()->toDateString(),
            $today->copy()->subMonth()->endOfMonth()->toDateString(),
            'ads_performance_sd_last3months',
            'month'
        );
    }


    private function exportAdsPerformanceSd($start, $end, $filename, $grouping = 'day')
    {
        try {
            return Excel::download(
                new AdsPerformanceSummaryExportSd($start, $end, 'US', $grouping),
                $filename . '_' . now()->timestamp . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('AdsPerformanceExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the report.');
        }
    }

    public function adsPerformanceByAsinDownload()
    {
        $this->authorize(DataEnum::DataDownloadPerformanceReport);
        try {
            $country = 'US';

            return Excel::download(
                new AdsPerformanceSummaryByAsinExport($country),
                "asin_performance_{$country}_" . now()->timestamp . ".xlsx"
            );
        } catch (\Throwable $e) {
            Log::error("ASIN performance export failed for US: " . $e->getMessage());
            return back()->with('error', 'Failed to generate ASIN performance report.');
        }
    }

    public function adsPerformanceByProductCampaignDownload()
    {
        $this->authorize(DataEnum::DataDownloadPerformanceReport);
        try {
            return Excel::download(
                new CampaignPerformanceExport([], 'download'),
                'campaign_performance_last7days.xlsx'
            );
            // $country = 'US';
            // return Excel::download(new AdsPerformanceByProductCampaignExport($country), "campaign_performance_by_product_{$country}_" . now()->timestamp . ".xlsx");
        } catch (\Throwable $e) {
            Log::error("Campaign performance by product export failed for US: " . $e->getMessage());
            return back()->with('error', 'Failed to generate campaign performance report.');
        }
    }

    public function adsKeywordPerfomanceDownload7days()
    {
        $this->authorize(DataEnum::DataDownloadPerformanceReport);
        try {
            // $fileName = "keyword_recommendations_last7days.xlsx";

            // return Excel::download(
            //     new KeywordRecommendationsLast7DaysExport(),
            //     $fileName
            // );
            $country = 'US';
            return Excel::download(new AdsKeywordPerformanceReportExport($country), "keyword_performance_report{$country}_" . now()->timestamp . ".xlsx");
        } catch (\Throwable $e) {
            Log::error("Keyword performance by product export failed for US: " . $e->getMessage());
            return back()->with('error', 'Failed to generate Keyword performance report.');
        }
    }
    public function asinWarehouseStockDownload()
    {
        $this->authorize(DataEnum::DataDownloadWarehouseReport);
        try {
            return Excel::download(new WarehousesAsinExport(), "warehouse_Asin" . now()->timestamp . ".xlsx");
        } catch (\Throwable $e) {
            Log::error("Warehouse Asin Export Failed: " . $e->getMessage());
            return back()->with('error', 'Failed to generate Warehouse Asin Export.');
        }
    }

    public function combinedAdsPerformanceExport()
    {
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        $periods = [
            [
                'start'    => $today->copy()->subDays(6)->toDateString(),
                'end'      => $today->toDateString(),
                'grouping' => 'day'
            ],
            [
                'start'    => $today->copy()->subWeeks(4)->toDateString(),
                'end'      => $today->toDateString(),
                'grouping' => 'week'
            ],
            [
                'start'    => $today->copy()->subMonths(3)->startOfMonth()->toDateString(),
                'end'      => $today->copy()->subMonth()->endOfMonth()->toDateString(),
                'grouping' => 'month'
            ],
        ];

        return Excel::download(new CombinedAdsPerformanceExport($periods), 'combined_ads_performance_' . now()->timestamp . '.xlsx');
    }

    public function adsCampaignLast7Days()
    {
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsCampaignPerformance(
            $today->copy()->subDays(6)->toDateString(),
            $today->toDateString(),
            'ads_campaign_last7days',
            'day'
        );
    }

    public function adsCampaignLast4Weeks()
    {
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsCampaignPerformance(
            $today->copy()->subWeeks(4)->toDateString(),
            $today->toDateString(),
            'ads_campaign_last4weeks',
            'week'
        );
    }

    public function adsCampaignLast3Months()
    {
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        return $this->exportAdsCampaignPerformance(
            $today->copy()->subMonths(3)->startOfMonth()->toDateString(),
            $today->toDateString(),
            'ads_campaign_last3months',
            'month'
        );
    }

    private function exportAdsCampaignPerformance($start, $end, $filename, $grouping = 'day')
    {
        try {
            return Excel::download(new AdsCampaignPerformanceSummaryExport($start, $end, 'US', $grouping), $filename . '_' . now()->timestamp . '.xlsx');
        } catch (\Exception $e) {
            Log::error('AdsCampaignPerformanceExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the campaign report.');
        }
    }

    public function StockRunDownReport()
    {
        try {
            $marketTz = config('timezone.market');
            $today = Carbon::today($marketTz);
            return Excel::download(new StockRunDownReportExport(), "Stock_Run_Down_Report{$today}_" . now()->timestamp . ".xlsx");
        } catch (\Exception $e) {
            Log::error('StockRunDownReport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the Stock Run Down report.');
        }
    }
    public function salesDailyReport()
    {
        try {
            $marketTz = config('timezone.market');
            $today = Carbon::today($marketTz);
            return Excel::download(new salesDailyReportExport(), "sales_daily_Report{$today}_" . now()->timestamp . ".xlsx");
        } catch (\Exception $e) {
            Log::error('salesDailyReportExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the Daily Sales Report.');
        }
    }

    public function salesMonthlyReport()
    {
        try {
            $marketTz = config('timezone.market');
            $today = Carbon::today($marketTz);
            return Excel::download(new salesMonthlyReportExport(), "sales_monthly_Report{$today}_" . now()->timestamp . ".xlsx");
        } catch (\Exception $e) {
            Log::error('salesMonthlyReport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the Monthly Sales report.');
        }
    }

    public function rankingReport()
    {
        try {
            $marketTz = config('timezone.market');
            $today = Carbon::today($marketTz);
            return Excel::download(new RankingReportExport(), "Ranking_Report{$today}_" . now()->timestamp . ".xlsx");
        } catch (\Exception $e) {
            Log::error('RankingReportExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the Ranking Report Export.');
        }
    }

    public function weeklySalesPerformanceReport()
    {
        try {
            $warehouses = Warehouse::get();
            return Excel::download(
                new WeeklySalesPerformaceReportExport($warehouses),
                'weekly_sales_performance_report' . now()->format('Y-m-d_His') . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('weeklySalesPerformanceReport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the Weekly Sales Peformance Report Export.');
        }
    }

    public function orderForecastFinaliseExport()
    {
        try {
            $marketTz = config('timezone.market');
            $today = Carbon::today($marketTz);
            return Excel::download(new orderForecastFinaliseExport(), "Order_Forecast_Finalise_Export{$today}_" . now()->timestamp . ".xlsx");
        } catch (\Exception $e) {
            Log::error('OrderForecastFinaliseExport failed: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while downloading the Order Forecast Finalise Export.');
        }
    }

    public function AsinPerformanceReportExport(Request $request)
    {
        try {
            $marketTz = config('timezone.market');

            $month = $request->query('month');
            $monthStart = $month
                ? Carbon::createFromFormat('Y-m', $month, $marketTz)->startOfMonth()
                : Carbon::now($marketTz)->startOfMonth();

            $today = Carbon::today($marketTz)->format('Y-m-d');

            $service = app(OrderForecastPerformanceService::class);

            return Excel::download(
                new AsinPerformanceReportExport($service, $monthStart),
                "Asin_Performance_Report_{$today}_" . now()->timestamp . ".xlsx"
            );
        } catch (\Throwable $e) {
            Log::error('AsinPerformanceReportExport failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Something went wrong while downloading the ASIN Performance Report.'
            );
        }
    }
}
