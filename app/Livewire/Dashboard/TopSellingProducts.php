<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Livewire\Component;

class TopSellingProducts extends Component
{
    public array $topSelling;
    public string $marketTz = 'America/Los_Angeles';

    public function mount(DashboardService $dashboardService, ?string $marketTz = null)
    {
        if ($marketTz) $this->marketTz = $marketTz;

        $this->topSelling = $dashboardService
            ->topSellingProducts($this->marketTz);
    }


    //This renders while lazy component is NOT hydrated yet
    public function placeholder()
    {
        return view('livewire.dashboard.skeleton.top-selling-products-skeleton');
    }

    public function render()
    {
        return view('livewire.dashboard.top-selling-products');
    }
}
