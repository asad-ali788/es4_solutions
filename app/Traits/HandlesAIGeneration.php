<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HandlesAIGeneration
{
    protected function handleBulkAIGenerate(Request $request, $forecastId, string $modelClass, string $jobClass)
    {
        try {
            // STEP 1: Mark all pending rows as dispatched
            $modelClass::where('order_forecast_id', $forecastId)
                ->where('run_status', 'pending')
                ->update(['run_status' => 'dispatched']);

            // STEP 2: Dispatch bulk job
            $jobClass::dispatch($forecastId)->onQueue('ai-long-running');

            return redirect()->back()->with('success', 'Forecast dispatched for AI.');
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch bulk AI for ID {$forecastId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to dispatch AI forecast. Please try again.');
        }
    }
}
