<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Livewire\Component;

class YesterdayCampaignSalesSummery extends Component
{
    public array $campaign = [];
    public string $marketTz = 'America/Los_Angeles';

    public function mount(DashboardService $dashboardService, ?string $marketTz = null)
    {
        if ($marketTz) $this->marketTz = $marketTz;

        $this->campaign = $dashboardService->getCampaignYesterdaysSalesReport($this->marketTz);
    }

    //This renders while lazy component is NOT hydrated yet
    public function placeholder()
    {
        return view('livewire.dashboard.skeleton.todays-and-top-sales-skeleton');
    }

    public function render()
    {
        return view('livewire.dashboard.yesterday-campaign-sales-summery');
    }
}
