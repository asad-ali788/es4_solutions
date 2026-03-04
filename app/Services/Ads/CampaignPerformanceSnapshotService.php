<?php

namespace App\Services\Ads;

use App\Models\AmzAdsCampaignPerformanceSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CampaignPerformanceSnapshotService
{
    private const COUNTRIES = ['US', 'CA'];

    /**
     * How long we consider "same run window".
     * If you run every 2 hours, 10-15 minutes is enough to prevent duplicates from retries/overlaps.
     */
    private const DUPLICATE_WINDOW_MINUTES = 15;

    public function captureDeltaForType(string $type, ?Carbon $now = null): void
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['SP', 'SB', 'SD'], true)) {
            return;
        }

        foreach (self::COUNTRIES as $country) {
            $this->captureDeltaForTypeAndCountrySafely($type, $country, $now);
        }
    }

    private function captureDeltaForTypeAndCountrySafely(string $type, string $country, ?Carbon $now = null): void
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');
        $nowMarket = $now?->copy() ?? Carbon::now($marketTz);

        // 🔒 lock per type+country to stop parallel workers / retries making duplicates
        $lockKey = "ads:snapshot:{$type}:{$country}";
        $lock = Cache::lock($lockKey, self::DUPLICATE_WINDOW_MINUTES * 60);

        if (!$lock->get()) {
            // another process is doing it
            return;
        }

        try {
            // Extra guard: if we already wrote a row very recently, skip.
            // This protects against "US job + CA job" both calling captureDeltaForType().
            $recentSinceUtc = Carbon::now('UTC')->subMinutes(self::DUPLICATE_WINDOW_MINUTES);

            $recentExists = AmzAdsCampaignPerformanceSnapshot::query()
                ->where('campaign_types', $type)
                ->where('country', $country)
                ->where('snapshot_time', '>=', $recentSinceUtc)
                ->exists();

            if ($recentExists) {
                return;
            }

            $this->captureDeltaForTypeAndCountry($type, $country, $nowMarket);
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Original delta logic (unchanged) — now called only when safe.
     */
    private function captureDeltaForTypeAndCountry(string $type, string $country, Carbon $nowMarket): void
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');
        $today = $nowMarket->toDateString();

        $marketStartUtc = Carbon::parse($today . ' 00:00:00', $marketTz)->utc();
        $marketEndUtc   = Carbon::parse($today . ' 23:59:59', $marketTz)->utc();

        $current = match ($type) {
            'SP' => $this->currentTotalsSp($today, $country),
            'SB' => $this->currentTotalsSb($today, $country),
            'SD' => $this->currentTotalsSd($today, $country),
        };

        $currentSpend = (float) ($current['spend'] ?? 0);
        $currentSales = (float) ($current['sales'] ?? 0);
        $currentUnits = (int) ($current['units'] ?? 0);

        $alreadySaved = AmzAdsCampaignPerformanceSnapshot::query()
            ->where('campaign_types', $type)
            ->where('country', $country)
            ->whereBetween('snapshot_time', [$marketStartUtc, $marketEndUtc])
            ->selectRaw('
                COALESCE(SUM(total_spend),0) as spend,
                COALESCE(SUM(total_sales),0) as sales,
                COALESCE(SUM(total_units),0) as units
            ')
            ->first();

        $savedSpend = (float) ($alreadySaved->spend ?? 0);
        $savedSales = (float) ($alreadySaved->sales ?? 0);
        $savedUnits = (int) ($alreadySaved->units ?? 0);

        $deltaSpend = $currentSpend - $savedSpend;
        $deltaSales = $currentSales - $savedSales;
        $deltaUnits = $currentUnits - $savedUnits;

        $deltaSpend = $deltaSpend < 0 ? 0.0 : $deltaSpend;
        $deltaSales = $deltaSales < 0 ? 0.0 : $deltaSales;
        $deltaUnits = $deltaUnits < 0 ? 0 : $deltaUnits;

        if ($deltaSpend == 0.0 && $deltaSales == 0.0 && $deltaUnits == 0) {
            return;
        }

        $acos = null;
        if ($deltaSales > 0) {
            $acos = round(($deltaSpend / $deltaSales) * 100, 2);
        }

        AmzAdsCampaignPerformanceSnapshot::create([
            'campaign_types' => $type,
            'country'        => $country,
            'total_spend'    => round($deltaSpend, 2),
            'total_sales'    => round($deltaSales, 2),
            'total_units'    => $deltaUnits,
            'acos'           => $acos,
            'snapshot_time'  => Carbon::now('UTC'),
        ]);
    }

    private function currentTotalsSp(string $today, string $country): array
    {
        $row = DB::table('temp_amz_ads_campaign_performance_report')
            ->where('country', $country)
            ->whereDate('c_date', $today)
            ->selectRaw('
                COALESCE(SUM(cost),0) as spend,
                COALESCE(SUM(sales7d),0) as sales,
                COALESCE(SUM(purchases7d),0) as units
            ')
            ->first();

        return [
            'spend' => (float) ($row->spend ?? 0),
            'sales' => (float) ($row->sales ?? 0),
            'units' => (int) ($row->units ?? 0),
        ];
    }

    private function currentTotalsSb(string $today, string $country): array
    {
        $row = DB::table('temp_amz_ads_campaign_performance_reports_sb')
            ->where('country', $country)
            ->whereDate('date', $today)
            ->selectRaw('
                COALESCE(SUM(cost),0) as spend,
                COALESCE(SUM(sales),0) as sales,
                COALESCE(SUM(unitsSold),0) as units
            ')
            ->first();

        return [
            'spend' => (float) ($row->spend ?? 0),
            'sales' => (float) ($row->sales ?? 0),
            'units' => (int) ($row->units ?? 0),
        ];
    }

    private function currentTotalsSd(string $today, string $country): array
    {
        $row = DB::table('temp_amz_campaign_sd_performance_report')
            ->where('country', $country)
            ->whereDate('c_date', $today)
            ->selectRaw('
                COALESCE(SUM(cost),0) as spend,
                COALESCE(SUM(sales),0) as sales,
                COALESCE(SUM(units_sold),0) as units
            ')
            ->first();

        return [
            'spend' => (float) ($row->spend ?? 0),
            'sales' => (float) ($row->sales ?? 0),
            'units' => (int) ($row->units ?? 0),
        ];
    }
}
