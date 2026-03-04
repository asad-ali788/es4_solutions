<?php

namespace App\Services\Seller;

use Illuminate\Http\Request;
use App\Services\Seller\AsinRepositoryService;
use App\Services\Seller\ReportService;
use App\Services\Seller\StockService;
use App\Services\Seller\CampaignService;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SellingAsinService
{
    public function __construct(
        protected AsinRepositoryService $asinRepo,
        protected ReportService $reportService,
        protected StockService $stockService,
        protected CampaignService $campaignService
    ) {}

    public function getAsinsForIndex(Request $request): array
    {
        $user = Auth::user();
        $reportingUsers = $this->asinRepo->getReportingUsers($user->id);

        $paginator = $this->asinRepo->getAsinsForIndex($request, $user, $reportingUsers);

        return [
            'asins' => $paginator,
            'reportingUsers' => $reportingUsers,
            'targetUserId' => $request->input('select'),
        ];
    }

    public function getAsinDetails(string $asin): array
    {
        $asinProduct = $this->asinRepo->getAsinWithProductOrFail($asin);

        $weeklyData  = $this->reportService->buildWeeklyReport($asin);
        $dailydata   = $this->reportService->buildDailyReport($asin);

        $weeklyReport = [
            'summary'         => $weeklyData['summary'],
            'weeks'           => $weeklyData['weeks'],
            'sp'              => $weeklyData['sp'],
            'sb'              => $weeklyData['sb'],
            'campaignMetrics' => $weeklyData['campaignMetrics'],
        ];

        $dailyReport = [
            'summary'  => $dailydata['daily'],
            'days'     => $dailydata['days'],
            'dayNames' => $dailydata['dayNames'],
        ];

        $startMonth = Carbon::now(config('timezone.market'))->startOfMonth();

        // Build 12 months dynamically
        $forecastMonths = collect(range(0, 11))
            ->map(fn($i) => $startMonth->copy()->addMonths($i)->toDateString());

        // Fetch forecast data
        $forecastData = $this->asinRepo->getForecastForMonths($asin, $forecastMonths);

        // Build map once
        $forecastMap = $forecastMonths->mapWithKeys(function ($month) use ($forecastData) {
            return [$month => $forecastData[$month]->total_forecast_units ?? 0];
        });

        // Split months
        $leftMonths  = $forecastMonths->take(6)->values();
        $rightMonths = $forecastMonths->slice(6, 6)->values();

        // Build rows (NO Carbon in Blade)
        $forecastRows = collect();

        $rows = max($leftMonths->count(), $rightMonths->count());

        for ($i = 0; $i < $rows; $i++) {
            $left  = $leftMonths[$i] ?? null;
            $right = $rightMonths[$i] ?? null;

            $forecastRows->push([
                'left' => [
                    'month' => $left ? Carbon::parse($left)->format('M-y') : null,
                    'units' => $left ? ($forecastMap[$left] ?? 0) : null,
                ],
                'right' => [
                    'month' => $right ? Carbon::parse($right)->format('M-y') : null,
                    'units' => $right ? ($forecastMap[$right] ?? 0) : null,
                ],
            ]);
        }

        // Range label (once)
        $forecastRangeLabel = sprintf(
            '%s – %s',
            Carbon::parse($forecastMonths->first())->format('M Y'),
            Carbon::parse($forecastMonths->last())->format('M Y')
        );

        $productIds = $this->asinRepo->getProductIdsForAsin($asin);
        $skus = $this->asinRepo->getSkusForProductIds($productIds);

        $stockSummary = $this->stockService->buildStockSummary($productIds, $skus, $asin);

        $currencies = Currency::pluck('currency_symbol', 'country_code');

        $recommendations = $this->asinRepo->getRecommendations($asin);

        $campaignReport = $this->getCampaignReport($asin);

        return compact(
            'asin',
            'weeklyReport',
            'dailyReport',
            'forecastRows',
            'forecastRangeLabel',
            'stockSummary',
            'currencies',
            'recommendations',
            'campaignReport'
        );
    }

    public function getCampaignReport(string $asin): array
    {
        try {
            return $this->campaignService->getCampaignReportDataDaily($asin);
        } catch (\Throwable $e) {
            return ['sp' => [], 'sb' => [], 'campaignMetrics' => [], 'days' => [], 'dayNames' => []];
        }
    }
}
