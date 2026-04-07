<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\OrderForecastEnum;
use App\Exports\OrderForecastSnapshotExport;
use App\Exports\OrderForecastSnapshotsCombinedExport;
use App\Http\Controllers\Controller;
use App\Jobs\Forecast\BulkAIGenerateBatchSkuJob;
use App\Jobs\Forecast\GenerateSkuAIForecast;
use App\Jobs\Forecast\InsertSnapshotsJob;
use App\Models\OrderForecast;
use App\Models\OrderForecastSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\OrderForecastSnapshotService;
use Illuminate\Support\Facades\Auth;

// use App\Traits\HandlesAIGeneration;

class OrderForecastController extends Controller
{

    // use HandlesAIGeneration;

    protected OrderForecastSnapshotService $snapshotService;

    public function __construct(OrderForecastSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    /**
     * Display a list of order forecasts.
     */
    public function index()
    {
        $this->authorize(OrderForecastEnum::OrderForecast);
        return view('pages.admin.orderforecast.index');
    }


    /**
     * Show the details of a specific forecast.
     */
    public function show($id, Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastSku);
        try {
            $user   = Auth::user();
            $forecast = OrderForecast::findOrFail($id);

            $query = OrderForecastSnapshot::with('productAsin')
                ->where('order_forecast_id', $id);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_sku', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhereHas('productAsin', function ($sub) use ($search) {
                            $sub->where('asin1', 'like', "%{$search}%")
                                ->orWhere('asin2', 'like', "%{$search}%")
                                ->orWhere('asin3', 'like', "%{$search}%");
                        });
                });
            }

            $snapshots = $query->orderByDesc('last12_total_sold')->paginate($request->input('per_page', 50));
            $processedSnapshots = $this->snapshotService->processSnapshots($snapshots, $forecast->order_date);

            $promptConfig = config('ai_forecast_prompts');

            // Determine modal prompt
            $modalPrompt = $promptConfig['demand_forecast_sku_new'];

            return view('pages.admin.orderforecast.show', [
                'forecast'          => $forecast,
                'snapshots'         => $processedSnapshots,
                'snapshotPaginator' => $snapshots,
                'currentMonthLabel' => now()->format('M Y'),
                'monthsLast3'       => $this->snapshotService->generateLast3Months(),
                'monthsNext12'      => $this->snapshotService->generateNext12Months(),
                'modalPrompt'       => $modalPrompt,
                'user'              => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error loading forecast details [id={$id}]: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.orderforecast.index')
                ->with('error', 'Could not load the forecast details.');
        }
    }

    public function create()
    {
        $this->authorize(OrderForecastEnum::OrderForecastCreate);
        try {
            return view('pages.admin.orderforecast.form', ['forecast' => null]);
        } catch (\Throwable $e) {
            Log::error("Failed to load forecast creation form: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return redirect()->route('admin.orderforecast.index')->with('error', 'Failed to load the create form.');
        }
    }

    public function store(Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastCreate);
        try {
            $data = $request->validate([
                'order_name' => 'required|string|max:255',
                'order_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            $data['status_flag'] = 'pending'; // initially pending

            $exists = OrderForecast::where('order_name', $data['order_name'])
                ->whereDate('order_date', $data['order_date'])
                ->exists();

            if ($exists) {
                return redirect()->back()->withInput()->with('error', 'A forecast with this order name and date already exists.');
            }

            $forecast = OrderForecast::create($data);

            InsertSnapshotsJob::dispatch($forecast->id);

            return redirect()->route('admin.orderforecast.index')
                ->with('success', 'Forecast created successfully. Your forecast will be ready shortly.');
        } catch (\Throwable $e) {
            Log::error("Forecast creation failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the forecast.');
        }
    }


    public function edit($id)
    {
        $this->authorize(OrderForecastEnum::OrderForecastUpdate);
        try {
            $forecast = OrderForecast::findOrFail($id);
            return view('pages.admin.orderforecast.form', compact('forecast'));
        } catch (\Throwable $e) {
            Log::error("Failed to load forecast for editing [id={$id}]: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return redirect()->route('admin.orderforecast.index')->with('error', 'Failed to load the edit form.');
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorize(OrderForecastEnum::OrderForecastUpdate);
        try {
            $forecast = OrderForecast::findOrFail($id);

            $data = $request->validate([
                'order_name' => 'required|string|max:255',
                'order_date' => 'date',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:draft,finalized,archived',
            ]);

            $forecast->update($data);

            if (($data['status'] ?? null) === 'finalized') {
                // Make sure snapshots exist
                if ($forecast->snapshots->isEmpty() || $forecast->snapshotAsins->isEmpty()) {
                    // Generate snapshots if not yet created
                    $this->snapshotService->insertSnapshots($forecast);
                }

                // Sync forecasts
                $this->snapshotService->syncFinalizedForecast($forecast);
            }

            return redirect()->route('admin.orderforecast.index')
                ->with('success', 'Forecast updated successfully.');
        } catch (\Throwable $e) {
            Log::error("Failed to update forecast [id={$id}]: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'An error occurred while updating the forecast.');
        }
    }

    public function destroy($id)
    {
        $this->authorize(OrderForecastEnum::OrderForecastDelete);
        try {
            DB::beginTransaction();
            // Delete snapshots first
            OrderForecastSnapshot::where('order_forecast_id', $id)->delete();
            // Delete the forecast
            OrderForecast::where('id', $id)->delete();
            DB::commit();
            return redirect()->route('admin.orderforecast.index')->with('success', 'Order Forecast deleted successfully.');
        } catch (\Throwable $e) {
            Log::error("Failed to delete forecast [id={$id}]: {$e->getMessage()}", ['trace' => $e->getTraceAsString(),]);
            return redirect()->route('admin.orderforecast.index')->with('error', 'Failed to delete the forecast.');
        }
    }

    public function updateSoldValue(Request $request)
    {
        try {
            $request->validate([
                'snapshot_id' => 'required|integer|exists:order_forecast_snapshots,id',
                'month_key' => 'required|string',
                'sold_value' => 'nullable|string',
            ]);

            $snapshot = OrderForecastSnapshot::findOrFail($request->snapshot_id);
            $data = $snapshot->sold_values_by_month ?? [];

            $data[$request->month_key] = $request->sold_value;

            $snapshot->sold_values_by_month = $data;
            $snapshot->save();

            return response()->json([
                'success' => true,
                'message' => 'Sold value updated successfully.',
                'sold_values_sum' => collect($snapshot->sold_values_by_month)->filter()->sum(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update sold value: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function updateOrderAmount(Request $request)
    {
        try {
            $validated = $request->validate([
                'snapshot_id'   => 'required|integer|exists:order_forecast_snapshots,id',
                'order_amount'  => 'nullable|integer|min:0'
            ]);

            $snapshot = OrderForecastSnapshot::findOrFail($validated['snapshot_id']);
            $snapshot->order_amount = $validated['order_amount'];
            $snapshot->save();

            return response()->json([
                'success'       => true,
                'message'       => 'Order amount updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order amount.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function downloadForecastSnapshotsSku(Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastSkuExport);
        try {
            $id = $request->query('id');
            return Excel::download(new OrderForecastSnapshotExport($id), 'order_forecast_snapshots_skus' . now()->timestamp . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Snapshot export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please contact admin.');
        }
    }

    public function downloadForecastSnapshots(Request $request)
    {
        $this->authorize(OrderForecastEnum::OrderForecastDownloadSnapShorts);
        try {
            $id = $request->query('id');

            return Excel::download(new OrderForecastSnapshotsCombinedExport($id), 'order_forecast_snapshots_' . now()->timestamp . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Snapshot export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please contact admin.');
        }
    }

    public function generateAI(Request $request)
    {
        return $this->snapshotService->handleGenerateAI($request, OrderForecastSnapshot::class, GenerateSkuAIForecast::class);
    }

    public function getStatus($id)
    {
        return $this->snapshotService->handleGetStatus($id, OrderForecastSnapshot::class);
    }

    // public function generateBulkAI(Request $request, $forecastId)
    // {
    //     return $this->handleBulkAIGenerate($request, $forecastId, OrderForecastSnapshot::class, BulkAIGenerateBatchSkuJob::class);
    // }
}
