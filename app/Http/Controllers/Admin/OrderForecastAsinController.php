<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\OrderForecastEnum;
use App\Exports\AsinForecastTemplateExport;
use App\Exports\OrderForecastAsinMonthlyExport;
use App\Exports\OrderForecastSnapshotAsinsExport;
use App\Http\Controllers\Controller;
use App\Imports\ForecastAsinImport;
use App\Jobs\Forecast\GenerateAsinAIForecastBatch;
use App\Jobs\Forecast\GenerateAsinAIForecast;
use App\Jobs\Forecast\ProcessAsinForecastUploadJob;
use App\Models\OrderForecast;
use App\Models\OrderForecastMetricAsins;
use App\Models\OrderForecastSnapshotAsins;
use App\Models\ProductCategorisation;
use App\Services\OrderForecastSnapshotService;
use App\Traits\OrderForecastQueryTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class OrderForecastAsinController extends Controller
{
    use OrderForecastQueryTrait;

    protected OrderForecastSnapshotService $snapshotService;

    public function __construct(OrderForecastSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    /**
     * Display ASIN forecast metrics for a given forecast.
     */
    public function show($id, Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastAsin);

        try {
            $user   = Auth::user();
            $forecast = OrderForecast::findOrFail($id);

            $query = OrderForecastSnapshotAsins::where('order_forecast_id', $id);

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('product_asin', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('product_title', 'like', "%{$search}%")
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->selectRaw(1)
                                ->from('product_categorisations as pc')
                                ->whereNull('pc.deleted_at')
                                ->whereColumn('pc.child_asin', 'order_forecast_snapshot_asins.product_asin')
                                ->where('pc.child_short_name', 'like', "%{$search}%");
                        });
                });
            }


            $snapshots = $query->orderByDesc('last12_total_sold')->paginate($request->input('per_page', 50));
            $processedSnapshots = $this->snapshotService->processSnapshots($snapshots, $forecast->order_date);

            $asins = collect($processedSnapshots)
                ->pluck('product_asin')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $asinProductNameMap = ProductCategorisation::whereIn('child_asin', $asins)
                ->pluck('child_short_name', 'child_asin')
                ->toArray();

            $runningCount = OrderForecastSnapshotAsins::where('order_forecast_id', $forecast->id)
                ->whereIn('run_status', ['running', 'dispatched'])
                ->count();

            $promptConfig = config('ai_forecast_prompts');
            $modalPrompt = $promptConfig['demand_forecast_asin_new'];

            return view('pages.admin.orderforecastasins.show', [
                'forecast'          => $forecast,
                'snapshots'         => $processedSnapshots,
                'snapshotPaginator' => $snapshots,
                'runningCount'      => $runningCount,
                'currentMonthLabel' => now()->format('M Y'),
                'monthsLast3'       => $this->snapshotService->generateLast3Months(),
                'monthsNext12'      => $this->snapshotService->generateNext12Months(),
                'modalPrompt'       => $modalPrompt,
                'asinProductNameMap'  => $asinProductNameMap,
                'user'              => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error loading ASIN forecast details [id={$id}]: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.orderforecast.index')
                ->with('error', 'Could not load the ASIN forecast details.');
        }
    }

    public function downloadForecastSnapshotsAsin(Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastAsinExport);
        try {
            $id = $request->query('id');
            return Excel::download(new OrderForecastSnapshotAsinsExport($id), 'order_forecast_snapshots_asins' . now()->timestamp . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Snapshot export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please contact admin.');
        }
    }

    public function generateAI(Request $request)
    {
        return $this->snapshotService->handleGenerateAI($request, OrderForecastSnapshotAsins::class, GenerateAsinAIForecast::class);
    }

    public function getStatus($id)
    {
        return $this->snapshotService->handleGetStatus($id, OrderForecastSnapshotAsins::class);
    }

    // public function generateBulkAI(Request $request, $forecastId)
    // {
    //     return $this->snapshotService->handleBulkAIGenerate($request, $forecastId, OrderForecastSnapshotAsins::class, GenerateAsinAIForecastBatch::class);
    // }

    public function asinDetailByMonth(Request $request)
    {
        $month   = $request->query('month', now()->format('Y-m'));
        $search  = $request->query('search');
        $perPage = (int) $request->query('per_page', 15);
        $forecastFilter = $request->query('forecast_filter');

        $data = $this->getAsinMonthlyForecast(
            $month,
            $search,
            $forecastFilter,
            true,
            $perPage
        );

        return view('pages.admin.orderforecastasins.asinDetailByMonth', [
            'asins'         => $data['records']->appends($request->query()),
            'selectedMonth' => $data['monthStart'],
            'search'        => $search,
        ]);
    }

    public function downloadOrderForecastAsinMonthlyExport(Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastAsinMonthlyExport);

        try {
            return Excel::download(
                new OrderForecastAsinMonthlyExport($request),
                'asin_monthly_forecast_' . now()->timestamp . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Snapshot export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please contact admin.');
        }
    }


    public function asinSysBreakdown(string $asin, int $forecastId)
    {
        $metricItem = OrderForecastMetricAsins::where('product_asin', $asin)->firstOrFail();
        $metrics = $metricItem->metrics_by_month ?? [];
        $now     = now();

        /* -----------------------------
     * 1. Monthly Sales (12 months)
     * ----------------------------- */
        $monthlySales = [];
        foreach ($metrics as $key => $data) {
            $month = str_replace('fc_month_', '', $key);
            $monthlySales[$month] = (int) ($data['sold'] ?? 0);
        }

        foreach (range(1, 12) as $m) {
            $month = $now->copy()->month($m)->format('Y-m');
            $monthlySales[$month] = $monthlySales[$month] ?? 0;
        }

        ksort($monthlySales);
        $monthlySales = array_slice($monthlySales, -12, 12, true);

        /* -----------------------------
     * 2. SYS Sold (SOURCE OF TRUTH)
     * ----------------------------- */
        $growthPercent = $metricItem->growth_percent ?? 0.10;
        $sysSold       = $this->snapshotService->calculateSysSold($monthlySales, $growthPercent);

        /* -----------------------------
     * 3. Annual & Avg
     * ----------------------------- */
        $annualTotal = array_sum($monthlySales);
        $annualTotal = $annualTotal > 0 ? $annualTotal : 1;
        $monthlyAvg  = $annualTotal / 12;

        /* -----------------------------
     * 4. Seasonality
     * ----------------------------- */
        $peakMonthValue = max($monthlySales);
        $isSeasonal = ($peakMonthValue / $monthlyAvg) >= 1.2;

        $seasonalityIndex = [];
        foreach ($monthlySales as $month => $value) {
            $seasonalityIndex[$month] = $value / $annualTotal;
        }

        $effectiveGrowth = $isSeasonal
            ? min($growthPercent, 0.07)
            : $growthPercent;

        $nextYearTotal = $annualTotal * (1 + $effectiveGrowth);

        $baseForecast = [];
        foreach ($seasonalityIndex as $month => $index) {
            $baseForecast[$month] = round($index * $nextYearTotal);
        }

        /* -----------------------------
     * 5. Recent Avg & Peaks
     * ----------------------------- */
        $recentSales = array_slice($monthlySales, -3);
        $recentAvg   = array_sum($recentSales) / max(count($recentSales), 1);

        $peakThreshold = $monthlyAvg * 1.2;
        $peakMonths = array_keys(array_filter(
            $monthlySales,
            fn($v) => $v >= $peakThreshold
        ));

        /* -----------------------------
     * 6. Explainable Breakdown
     * ----------------------------- */
        $breakdown = [];

        foreach ($baseForecast as $month => $seasonalValue) {

            if (in_array($month, $peakMonths, true)) {
                $type = 'Peak';
                $seasonalWeight = 0.95;
                $recentWeight   = 0.05;
            } elseif ($isSeasonal) {
                $type = 'Off-Season';
                $seasonalWeight = 0.85;
                $recentWeight   = 0.15;
            } else {
                $type = 'Normal';
                $seasonalWeight = 0.80;
                $recentWeight   = 0.20;
            }

            // Blended forecast
            $adjusted = ($seasonalValue * $seasonalWeight) + ($recentAvg * $recentWeight);

            // Safety floor
            $lastYear = $monthlySales[$month] ?? 0;
            $floor = $lastYear * 0.6;
            $final = max($adjusted, $floor);

            /* -----------------------------
         * Off-season inflation guard
         * ----------------------------- */
            $isOffSeason = !in_array($month, $peakMonths, true);
            $upliftedSales = $lastYear * (1 + $effectiveGrowth);

            $overrideApplied = false;
            if ($isOffSeason && $final > $upliftedSales) {
                // Override with base seasonal forecast
                $final = $seasonalValue;
                $overrideApplied = true;
            }

            $breakdown[$month] = [
                'actual_sold'         => $lastYear,
                'seasonality_index'   => round($seasonalityIndex[$month], 4),
                'base_forecast'       => $seasonalValue,
                'recent_avg'          => round($recentAvg, 2),
                'month_type'          => $type,
                'weights'             => "{$seasonalWeight} / {$recentWeight}",
                'safety_floor'        => round($floor),
                'final_without_floor' => round($adjusted),
                'override_applied'    => $overrideApplied,
                'final_sys_sold'      => $sysSold[$month], // ✅ matches calculateSysSold
            ];
        }

        return view('pages.admin.orderforecastasins.sys-breakdown', compact(
            'asin',
            'breakdown',
            'annualTotal',
            'monthlyAvg',
            'effectiveGrowth',
            'isSeasonal',
            'forecastId'
        ));
    }

    public function bulkUpload(Request $request)
    {
        try {
            // 1️⃣ Allow ONLY Excel files
            $request->validate([
                'file' => 'required|mimes:xlsx,xls|max:5120',
                'order_forecast_id' => 'required|exists:order_forecasts,id',
            ]);

            $rows = Excel::toArray(new ForecastAsinImport, $request->file('file'))[0];

            if (empty($rows)) {
                return back()->withErrors(['file' => 'Uploaded file is empty']);
            }

            // 2️⃣ Filter rows that have at least ONE unit value
            $filteredRows = [];
            $hasAnyUnits = false;

            foreach ($rows as $row) {
                $dataColumns = collect($row)->except(['asin']);

                $rowHasUnits = false;

                foreach ($dataColumns as $value) {
                    if (is_numeric($value) && $value > 0) {
                        $rowHasUnits = true;
                        $hasAnyUnits = true;
                        break;
                    }
                }

                // 3️⃣ Keep only ASINs with units
                if ($rowHasUnits) {
                    $filteredRows[] = $row;
                }
            }

            if (!$hasAnyUnits) {
                return back()->with('error', 'Excel contains ASINs but no forecast unit values. Please enter at least one unit value.');
            }

            if (empty($filteredRows)) {
                return back()->with('error', 'No valid ASINs found with forecast unit values. Please check your file and try again.');
            }

            $cacheKey = 'asin_forecast_upload_' . Auth::id();
            $forecastId = $request->order_forecast_id;

            cache()->tags(['forecast_upload'])->put(
                $cacheKey,
                $filteredRows,
                now()->addMinutes(30)
            );

            return view(
                'pages.admin.orderforecastasins.asin-upload-preview',
                [
                    'rows' => $filteredRows,
                    'cacheKey' => $cacheKey,
                    'forecastId' => $forecastId,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Bulk Upload Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'file' => 'An error occurred while processing the file. Please try again.'
            ]);
        }
    }


    public function confirmBulkUpload(Request $request)
    {
        try {
            $cacheKey = $request->cache_key;
            $forecastId = $request->order_forecast_id;

            $rows = cache()->tags(['forecast_upload'])->get($cacheKey);

            if (!$rows) {
                return redirect()->route('admin.orderforecastasin.show', ['orderforecastasin' => $forecastId])
                    ->withErrors('Upload session expired.');
            }

            ProcessAsinForecastUploadJob::dispatchSync($rows, Auth::id(), (int) $request->order_forecast_id);

            cache()->tags(['forecast_upload'])->flush();

            return redirect()->route('admin.orderforecastasin.show', ['orderforecastasin' => $forecastId])
                ->with('success', 'Bulk upload queued successfully.');
        } catch (\Exception $e) {
            Log::error('Confirm Bulk Upload Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->route('admin.orderforecastasin.show', ['orderforecastasin' => $forecastId])
                ->withErrors('An error occurred while processing the bulk upload.');
        }
    }

    public function cancelBulkUpload(Request $request)
    {
        $cacheKey = $request->cache_key;
        $forecastId = $request->order_forecast_id;

        if ($cacheKey) {
            cache()->tags(['forecast_upload'])->forget($cacheKey);
        }

        return redirect()->route('admin.orderforecastasin.show', ['orderforecastasin' => $forecastId])
            ->with('info', 'Bulk upload canceled.');
    }

    public function exportTemplate()
    {
        $asinList = DB::table('product_asins')
            ->select('asin1')
            ->groupBy('asin1')
            ->pluck('asin1')
            ->toArray();

        $fileName = 'asin_forecast_template_' . now()->format('Y_m') . '.xlsx';

        return Excel::download(new AsinForecastTemplateExport($asinList), $fileName);
    }

    public function weatherTableMultiCountry()
    {
        $apiKey  = config('services.openweather.key');
        $baseUrl = config('services.openweather.url', 'https://api.openweathermap.org/data/2.5/weather');
        $units   = 'metric';

        // Toggle debug payload with ?debug=1
        $debug = request()->boolean('debug', false);

        if (empty($apiKey)) {
            return response()->json([
                'ok' => false,
                'meta' => [
                    'service' => 'openweather',
                    'endpoint' => $baseUrl,
                    'units' => $units,
                ],
                'error' => [
                    'code' => 'CONFIG_MISSING',
                    'message' => 'OPENWEATHER_API_KEY missing in config/services.php or .env',
                ],
            ], 500);
        }

        // Country codes: UK -> GB
        $cities = [
            'US' => 'New York,US',
            'CA' => 'Toronto,CA',
            'MX' => 'Mexico City,MX',
            'GB' => 'London,GB',
        ];

        $startedAt = microtime(true);

        $results = [];
        $summary = [
            'requested' => count($cities),
            'successful' => 0,
            'failed' => 0,
        ];

        foreach ($cities as $country => $q) {
            $reqStarted = microtime(true);

            try {
                $response = Http::connectTimeout(5)
                    ->timeout(15)
                    // Do not throw exceptions after retries; we want to inspect status/body
                    ->retry(3, 2000, null, false)
                    ->acceptJson()
                    ->get($baseUrl, [
                        'q'     => $q,
                        'appid' => $apiKey,
                        'units' => $units,
                    ]);

                $durationMs = (int) round((microtime(true) - $reqStarted) * 1000);
                $status = $response->status();

                if (!$response->successful()) {
                    $summary['failed']++;

                    $body = $response->json();
                    // Fallback to raw string if json decoding fails
                    if ($body === null) {
                        $body = $response->body();
                    }

                    $results[] = [
                        'country' => $country,
                        'query' => $q,
                        'ok' => false,
                        'http' => [
                            'status' => $status,
                            'duration_ms' => $durationMs,
                        ],
                        'error' => [
                            // OpenWeather typically returns {cod: ..., message: "..."}
                            'provider_code' => is_array($body) ? ($body['cod'] ?? null) : null,
                            'provider_message' => is_array($body) ? ($body['message'] ?? null) : null,
                            'message' => 'OpenWeather request failed',
                        ],
                        'debug' => $debug ? [
                            'response' => $body,
                        ] : null,
                    ];

                    continue;
                }

                $data = $response->json();
                $summary['successful']++;

                $results[] = [
                    'country' => $country,
                    'query' => $q,
                    'ok' => true,
                    'http' => [
                        'status' => $status,
                        'duration_ms' => $durationMs,
                    ],
                    'location' => [
                        'name' => data_get($data, 'name'),
                        'country' => data_get($data, 'sys.country'),
                        'coord' => [
                            'lat' => data_get($data, 'coord.lat'),
                            'lon' => data_get($data, 'coord.lon'),
                        ],
                        'timezone_offset_sec' => data_get($data, 'timezone'),
                    ],
                    'weather' => [
                        'main' => data_get($data, 'weather.0.main'),
                        'description' => data_get($data, 'weather.0.description'),
                        'icon' => data_get($data, 'weather.0.icon'),
                    ],
                    'measurements' => [
                        'temp' => data_get($data, 'main.temp'),
                        'feels_like' => data_get($data, 'main.feels_like'),
                        'humidity' => data_get($data, 'main.humidity'),
                        'pressure' => data_get($data, 'main.pressure'),
                        'visibility_m' => data_get($data, 'visibility'),
                        'wind' => [
                            'speed' => data_get($data, 'wind.speed'),
                            'deg' => data_get($data, 'wind.deg'),
                            'gust' => data_get($data, 'wind.gust'),
                        ],
                        'clouds_pct' => data_get($data, 'clouds.all'),
                    ],
                    'timestamps' => [
                        'dt_unix' => data_get($data, 'dt'),
                        'sunrise_unix' => data_get($data, 'sys.sunrise'),
                        'sunset_unix' => data_get($data, 'sys.sunset'),
                    ],
                    'debug' => $debug ? [
                        'raw' => $data,
                    ] : null,
                ];
            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $reqStarted) * 1000);

                Log::error('OpenWeather fetch failed', [
                    'country' => $country,
                    'query' => $q,
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                ]);

                $summary['failed']++;

                $results[] = [
                    'country' => $country,
                    'query' => $q,
                    'ok' => false,
                    'http' => [
                        'status' => null,
                        'duration_ms' => $durationMs,
                    ],
                    'error' => [
                        'code' => 'REQUEST_EXCEPTION',
                        'message' => $e->getMessage(),
                    ],
                ];
            }
        }

        $totalMs = (int) round((microtime(true) - $startedAt) * 1000);

        return response()->json([
            'ok' => $summary['failed'] === 0,
            'meta' => [
                'service' => 'openweather',
                'endpoint' => $baseUrl,
                'units' => $units,
                'requested_at' => now()->toISOString(),
                'total_duration_ms' => $totalMs,
            ],
            'summary' => $summary,
            'results' => array_values(array_map(function ($r) {
                // Remove debug=null to keep response clean
                if (array_key_exists('debug', $r) && $r['debug'] === null) {
                    unset($r['debug']);
                }
                return $r;
            }, $results)),
        ]);
    }
}
