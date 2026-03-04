<?php

namespace App\Services\Seller;

use Carbon\Carbon;
use App\Models\DailySales;
use App\Models\WeeklySales;

class ReportService
{
    public function buildDailyReport(string $asin): array
    {
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);

        $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $todayIndex = $today->dayOfWeekIso - 1;

        $rotatedDays = array_merge(
            array_slice($weekDays, $todayIndex + 1),
            array_slice($weekDays, 0, $todayIndex + 1)
        );

        $dayLabels = [];
        $dateMap = [];

        foreach ($rotatedDays as $i => $dayName) {
            $date = $today->copy()->subDays(6 - $i);
            $dayLabels['D' . ($i + 1)] = $dayName;
            $dateMap[$date->toDateString()] = 'D' . ($i + 1);
        }

        $days = array_keys($dayLabels);

        $marketplaceMap = [
            'USA' => ['Amazon.com'],
            'CA'  => ['Amazon.ca'],
            'MX'  => ['Amazon.com.mx'],
        ];

        $dailySummary = [];
        foreach (array_keys($marketplaceMap) as $region) {
            foreach ($days as $day) {
                $dailySummary[$region][$day] = ['units' => 0, 'revenue' => 0.0];
            }
        }

        $salesRecords = DailySales::whereIn('marketplace_id', ['Amazon.com', 'Amazon.ca', 'Amazon.com.mx'])
            ->where('asin', $asin)
            ->whereBetween('sale_date', [$today->copy()->subDays(6), $today])
            ->get(['marketplace_id', 'sale_date', 'total_units', 'total_revenue']);

        foreach ($salesRecords as $record) {
            foreach ($marketplaceMap as $region => $ids) {
                if (in_array($record->marketplace_id, $ids)) {
                    $dayKey = $dateMap[\Carbon\Carbon::parse($record->sale_date)->toDateString()] ?? null;
                    if ($dayKey && isset($dailySummary[$region][$dayKey])) {
                        $dailySummary[$region][$dayKey]['units'] += $record->total_units;
                        $dailySummary[$region][$dayKey]['revenue'] += $record->total_revenue;
                    }
                }
            }
        }

        return ['daily' => $dailySummary, 'days' => $days, 'dayNames' => $dayLabels];
    }

    public function buildWeeklyReport(string $asin): array
    {
        $marketTz = config('timezone.market');
        $today = Carbon::today($marketTz);
        $lastFullWeek = $today->copy()->subWeek()->isoWeek();
        $year = $today->year;

        // Last 6 full weeks including last week
        $weeks = range($lastFullWeek - 5, $lastFullWeek);

        // Week labels: W1 oldest, W6 most recent
        $weekLabels = [];
        $reversed = array_reverse($weeks);
        foreach ($reversed as $i => $week) {
            $weekLabels['W' . ($i + 1)] = $week;
        }

        $columns = array_reverse(array_keys($weekLabels));
        $columns[] = 'Sales'; // current week cumulative

        $marketplaceMap = [
            'USA' => ['Amazon.com'],
            'CA'  => ['Amazon.ca'],
            'MX'  => ['Amazon.com.mx'],
        ];

        // Initialize summary
        $summary = [];
        foreach ($marketplaceMap as $region => $_) {
            foreach ($columns as $col) {
                $summary[$region][$col] = ['units' => 0, 'revenue' => 0.0];
            }
        }

        // Weekly sales data
        $weeklySales = WeeklySales::whereIn('marketplace_id', ['Amazon.com', 'Amazon.ca', 'Amazon.com.mx'])
            ->where('asin', $asin)
            ->whereIn('week_number', $weeks)
            ->get(['marketplace_id', 'week_number', 'total_units', 'total_revenue']);

        foreach ($weeklySales as $record) {
            foreach ($marketplaceMap as $region => $ids) {
                if (in_array($record->marketplace_id, $ids)) {
                    foreach ($weekLabels as $label => $weekNum) {
                        if ((int)$record->week_number === $weekNum) {
                            $summary[$region][$label]['units'] += $record->total_units;
                            $summary[$region][$label]['revenue'] += $record->total_revenue;
                            break;
                        }
                    }
                }
            }
        }

        // Current week cumulative (Mon → yesterday)
        $dailySales = DailySales::where('asin', $asin)
            ->whereBetween('sale_date', [$today->copy()->startOfWeek(), $today->copy()->subDay()])
            ->get(['marketplace_id', 'total_units', 'total_revenue']);

        foreach ($dailySales as $record) {
            foreach ($marketplaceMap as $region => $ids) {
                if (in_array($record->marketplace_id, $ids)) {
                    $summary[$region]['Sales']['units'] += $record->total_units;
                    $summary[$region]['Sales']['revenue'] += $record->total_revenue;
                }
            }
        }

        // Campaign data placeholder (replace with actual service call)
        $campaignData = getCampaignDataWeekly($asin, 'asin', $weeks, $year, $marketplaceMap, $marketTz);

        return [
            'summary' => $summary,
            'weeks' => $columns,
            'sp' => $campaignData['spSummary'],
            'sb' => $campaignData['sbSummary'],
            'campaignMetrics' => $campaignData['campaignMetrics'],
        ];
    }
}
