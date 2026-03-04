<?php

namespace App\Livewire\Forecast;

use Livewire\Component;
use App\Models\OrderForecast;

class ForecastRow extends Component
{
    public $forecast;

    public function mount($forecast)
    {
        $this->forecast = $forecast;
    }

    public function refreshRow()
    {
        $this->forecast = OrderForecast::find($this->forecast->id);

        // tell parent to refresh pagination if needed
        $this->dispatch('refreshList');
    }

    public function render()
    {
        return view('livewire.forecast.forecast-row');
    }
}
