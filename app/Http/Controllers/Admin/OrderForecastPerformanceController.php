<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderForecastPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exports\AsinPerformanceReportExport;
use Maatwebsite\Excel\Facades\Excel;

class OrderForecastPerformanceController extends Controller
{
    protected OrderForecastPerformanceService $service;

    public function __construct(OrderForecastPerformanceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $marketTz = config('timezone.market');

            $month = $request->query('month', now($marketTz)->format('Y-m'));

            $monthStart = Carbon::createFromFormat('Y-m', $month, $marketTz)->startOfMonth();
            $monthLabel = $monthStart->format('F Y');

            $data = $this->service->getData($request);

            $records = $data['records'];

            return view('pages.admin.orderforecastperformance.show', [
                'records'    => $records,
                'monthStart' => $monthStart,
                'monthLabel' => $monthLabel,
                'month'      => $month,
            ]);
        } catch (\Throwable $e) {
            Log::error('OrderForecastPerformance failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load forecast performance.');
        }
    }


    public function clearCache()
    {
        Cache::tags(['forecast_perf'])->flush();
        return back()->with('success', 'Forecast cache cleared');
    }

    public function export(Request $request)
    {
        $marketTz = config('timezone.market');

        $month = $request->query('month');
        $monthStart = $month
            ? Carbon::createFromFormat('Y-m', $month, $marketTz)->startOfMonth()
            : Carbon::now($marketTz)->startOfMonth();

        return Excel::download(
            new AsinPerformanceReportExport($this->service, $monthStart, $request->query()),
            'asin_performance_' . $monthStart->format('Y_m') . '.xlsx'
        );
    }
}
