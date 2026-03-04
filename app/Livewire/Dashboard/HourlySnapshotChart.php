<?php

namespace App\Livewire\Dashboard;

use App\Models\HourlyProductSales;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HourlySnapshotChart extends Component
{
    public string $marketTz = 'America/Los_Angeles';

    public function mount(?string $marketTz = null): void
    {
        $this->marketTz = $marketTz ?: config('timezone.market', $this->marketTz);
    }

    public function loadHourlyCharts(): void
    {
        $tz  = $this->marketTz;
        $now = Carbon::now($tz);

        $todayRows     = $this->fetchDay($now);
        $yesterdayRows = $this->fetchDay($now->copy()->subDay());

        // Today: cutoff at now, Yesterday: full day
        $todayPoints     = $this->toOverlayPoints($todayRows, $tz, $now);
        $yesterdayPoints = $this->toOverlayPoints($yesterdayRows, $tz, null);

        $series = [];

        if (!empty($yesterdayPoints)) {
            $series[] = ['name' => 'Yesterday', 'data' => $yesterdayPoints];
        }

        if (!empty($todayPoints)) {
            $series[] = ['name' => 'Today', 'data' => $todayPoints];
        }

        $this->dispatch('hourlySnapshotReady', series: $series);
    }

    /**
     * Convert DB rows (sale_hour, total_units) to Apex points.
     * x axis: minutes since midnight (0..1439)
     * y axis: summed units
     *
     * If $cutoffMarket is provided, remove points after now (today overlay).
     */
    private function toOverlayPoints(Collection $rows, string $marketTz, ?Carbon $cutoffMarket): array
    {
        $points = [];

        foreach ($rows as $row) {
            /**
             * sale_hour is stored in marketplace-local time.
             * Parse using marketplace TZ to avoid app timezone interference.
             */
            $tsMarket = Carbon::parse($row->sale_hour, $marketTz);

            if ($cutoffMarket && $tsMarket->gt($cutoffMarket)) {
                continue;
            }

            $h = (int) $tsMarket->format('H');
            $i = (int) $tsMarket->format('i'); // expected 00 if bucketed correctly

            $minuteOfDay = ($h * 60) + $i;

            $points[] = [
                'x' => $minuteOfDay,
                'y' => (int) $row->total_units,
            ];
        }

        return $points;
    }

    /**
     * Fetch hourly buckets for a day (market-local).
     * sale_hour is stored in marketplace-local time, so we query day boundaries in that same TZ.
     *
     * Returns rows: sale_hour, total_units (SUM across all SKUs for that hour)
     */
    private function fetchDay(Carbon $dayMarket): Collection
    {
        $start = $dayMarket->copy()->startOfDay();
        $end   = $dayMarket->copy()->endOfDay();

        return HourlyProductSales::query()
            ->select([
                'sale_hour',
                DB::raw('SUM(total_units) as total_units'),
            ])
            ->whereBetween('sale_hour', [$start, $end])
            ->groupBy('sale_hour')
            ->orderBy('sale_hour')
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.hourly-snapshot-chart');
    }
}
