<?php

namespace App\Livewire\Forecast;

use Livewire\Component;
use App\Models\OrderForecastSnapshotAsins;

class ForecastAsinSoldInput extends Component
{
    public int $snapshotId;
    public string $monthKey;
    public $soldValue;

    public function mount(int $snapshotId, string $monthKey, $soldValue = null)
    {
        $this->snapshotId = $snapshotId;
        $this->monthKey = $monthKey;
        $this->soldValue = $soldValue;
    }

    public function updatedSoldValue($value)
    {
        
        $clean = preg_replace('/\D/', '', $value);

        $clean = min((int)$clean, 999999);

        $this->soldValue = $clean !== '' ? (int)$clean : null;
        // Save to DB
        $snapshot = OrderForecastSnapshotAsins::find($this->snapshotId);
        if ($snapshot) {
            $metrics = is_array($snapshot->sold_values_by_month)
                ? $snapshot->sold_values_by_month
                : json_decode($snapshot->sold_values_by_month, true) ?? [];

            $metrics[$this->monthKey] = $this->soldValue;

            $snapshot->sold_values_by_month = $metrics;
            $snapshot->save();
        }

        // Show toast
        $message = $this->soldValue === null
            ? "Sold value cleared"
            : "Sold value updated to {$this->soldValue}";

        $this->dispatch('show-toast', type: 'success', message: $message);
    }

    public function render()
    {
        return view('livewire.forecast.forecast-asin-sold-input');
    }
}
