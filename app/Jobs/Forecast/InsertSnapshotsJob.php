<?php

namespace App\Jobs\Forecast;

use App\Models\OrderForecast;
use App\Services\OrderForecastSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class InsertSnapshotsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $timeout = 1800; // 30 minutes
    protected $forecastId;

    public function __construct(int $forecastId)
    {
        $this->forecastId = $forecastId;
    }

    public function handle(OrderForecastSnapshotService $snapshotService)
    {
        $forecast = OrderForecast::find($this->forecastId);

        if (!$forecast) {
            Log::warning("Forecast {$this->forecastId} not found.");
            return;
        }

        try {
            $snapshotService->insertSnapshots($forecast);
            $forecast->update(['status_flag' => 'ready']);
        } catch (\Throwable $e) {
            Log::error("InsertSnapshotsJob failed for forecast {$this->forecastId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $forecast->update(['status_flag' => 'failed']);
        }
    }
}
