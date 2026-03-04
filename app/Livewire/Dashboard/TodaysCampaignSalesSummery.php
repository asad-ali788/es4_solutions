<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Livewire\Component;

class TodaysCampaignSalesSummery extends Component
{
    public array $adSales = [];
    public string $marketTz = 'America/Los_Angeles';

    public function mount(DashboardService $dashboardService, ?string $marketTz = null)
    {
        if ($marketTz) $this->marketTz = $marketTz;

        $this->adSales = $dashboardService->getTodayAdReportSales($this->marketTz);
    }

    //This renders while lazy component is NOT hydrated yet
    public function placeholder()
    {
        return view('livewire.dashboard.skeleton.todays-and-top-sales-skeleton');
    }

    public function render()
    {
        return view('livewire.dashboard.todays-campaign-sales-summery');
    }
}
