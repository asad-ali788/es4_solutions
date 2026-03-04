<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class PerformanceCharts extends Component
{
    public array $adsCharts = [];
    public string $marketTz = 'America/Los_Angeles';

    // (Optional) if you want to load charts after first paint
    public bool $loaded = false;

    public function mount(DashboardService $dashboardService, ?string $marketTz = null)
    {
        if ($marketTz) {
            $this->marketTz = $marketTz;
        }

        // If you DON'T want lazy load, you can keep the old mount logic
        // But for skeleton, lazy is better -> we load on wire:init
    }

    public function loadCharts(DashboardService $dashboardService): void
    {
        if ($this->loaded) return;

        $tz = $this->marketTz;

        $this->adsCharts = [
            'daily' => Cache::remember("ads_charts_daily:$tz", 900, function () use ($tz, $dashboardService) {
                return $dashboardService->adsPerformanceChart(
                    Carbon::now($tz)->subDays(7)->toDateString(),
                    Carbon::now($tz)->subDay()->toDateString(),
                    'day'
                );
            }),

            'weekly' => Cache::remember("ads_charts_weekly:$tz", 21600, function () use ($tz, $dashboardService) {
                return $dashboardService->adsPerformanceChart(
                    Carbon::now($tz)->subWeeks(4)->toDateString(),
                    Carbon::now($tz)->subWeek()->toDateString(),
                    'week'
                );
            }),

            'monthly' => Cache::remember("ads_charts_monthly:$tz", 43200, function () use ($tz, $dashboardService) {
                return $dashboardService->adsPerformanceChart(
                    Carbon::now($tz)->subMonths(3)->startOfMonth()->toDateString(),
                    Carbon::now($tz)->subMonth()->endOfMonth()->toDateString(),
                    'month'
                );
            }),
        ];

        $this->loaded = true;

        // Tell browser to render charts now (after DOM is ready)
        $this->dispatch('adsChartsReady', charts: $this->adsCharts);
    }

    public function render()
    {
        return view('livewire.dashboard.performance-charts');
    }
}
