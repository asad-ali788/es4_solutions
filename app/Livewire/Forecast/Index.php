<?php

namespace App\Livewire\Forecast;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OrderForecast;
use App\Models\OrderForecastMetric;
use App\Models\OrderForecastMetricAsins;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    protected $queryString = ['search'];

    protected $listeners = ['refreshList' => '$refresh'];

    public function getMetricsNotReadyProperty(): bool
    {
        return
            OrderForecastMetric::where('is_not_ready', 1)->exists() ||
            OrderForecastMetricAsins::where('is_not_ready', 1)->exists();
    }

    public function updatingSearch()
    {
        $this->resetPage(); // when the search changes, go to page 1
    }

    public function getForecastsProperty()
    {
        return OrderForecast::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('order_name', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }


    public function render()
    {
        return view('livewire.forecast.index', [
            'forecasts' => $this->forecasts,
        ]);
    }
}
