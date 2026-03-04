<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Services\Dashboard\DashboardService;

class MonthToDateYearComparisons extends Component
{
    public array $monthToDateSummary = [];
    public string $marketTz = 'America/Los_Angeles';
    public array $marketplaceMap = [];

    public function mount(DashboardService $dashboardService, array $marketplaceMap = [], ?string $marketTz = null)
    {
        if ($marketTz) $this->marketTz = $marketTz;
        $this->marketplaceMap = $marketplaceMap;

        // load data (lazy component will show placeholder until hydrated)
        $this->monthToDateSummary = $dashboardService->getMonthToDateAndYearComparisons($this->marketTz, $this->marketplaceMap);
    }

    public function placeholder()
    {
        return view('livewire.dashboard.skeleton.month-to-date-year-comparisons-skeleton');
    }

    public function render()
    {
        return view('livewire.dashboard.month-to-date-year-comparisons');
    }
}
