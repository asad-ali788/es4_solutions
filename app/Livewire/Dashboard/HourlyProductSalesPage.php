<?php

namespace App\Livewire\Dashboard;

use App\Models\Currency;
use App\Models\HourlyProductSales;
use App\Models\ProductCategorisation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Hourly Product Sales')]
class HourlyProductSalesPage extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function paginationView(): string
    {
        return 'vendor.livewire.bootstrap-custom-pagination';
    }

    // Livewire scroll preservation (keeps scroll during updates)
    protected bool $preserveScroll = true;

    public string $marketTz = 'America/Los_Angeles';
    // Table labels (dynamic)
    public string $tableLabelCurrent = 'Today';
    public string $tableLabelPrev = 'Yesterday';

    // Store individual day labels for the table
    public array $dayLabels = [];

    #[Url]
    public string $dateRange = 'current';

    #[Url]
    public string $date = '';

    #[Url]
    public string $search = '';

    #[Url]
    public int $perPage = 15;

    #[Url]
    public ?string $salesChannel = null;

    #[Url]
    public ?string $selectedAsin = null;

    #[Url]
    public ?string $selectedSku = null;

    public bool $booted = false;

    // Backend-rendered title/subtitle (no JS dependency)
    public string $chartTitle = '—';
    public string $chartSubtitle = '—';

    public function mount(?string $marketTz = null): void
    {
        $this->marketTz = $marketTz ?: config('timezone.market', $this->marketTz);

        if ($this->date === '') {
            $this->date = Carbon::now($this->marketTz)->toDateString();
        }

        // Validate and reset to today if invalid
        if (!$this->isValidDate($this->date)) {
            $this->date = Carbon::now($this->marketTz)->toDateString();
        }

        // Set initial title/subtitle for first paint
        $day = Carbon::createFromFormat('Y-m-d', $this->date, $this->marketTz)->startOfDay();
        $this->chartTitle = $this->selectionTitle();
        $this->chartSubtitle = $this->buildSubtitle($day);
        $this->applyDateLabels();
    }

    public function bootPage(): void
    {
        $this->booted = true;
        $this->loadChart(); // default TOTAL
    }

    public function updated(string $name, mixed $value): void
    {
        if (!in_array($name, ['date', 'search', 'perPage', 'salesChannel', 'dateRange'], true)) {
            return;
        }

        // Validate date if it changed
        if ($name === 'date' && !$this->isValidDate($this->date)) {
            $this->date = Carbon::now($this->marketTz)->toDateString();
        }

        $this->applyDateLabels();
        $this->resetPage();
        $this->clearSelection();
        $this->loadChart();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->loadChart();
    }

    /**
     * Pagination change hook (page changes)
     */
    public function updatedPage(): void
    {
        $this->loadChart();
    }

    /**
     * Safety for multiple paginator names (Livewire internal)
     */
    public function updatedPaginators(): void
    {
        $this->loadChart();
    }

    public function selectProduct(?string $asin, ?string $sku): void
    {
        $asin = $asin ?: null;
        $sku  = $sku ?: null;

        $isSame =
            ($this->selectedAsin !== null && $asin !== null && $this->selectedAsin === $asin)
            || ($this->selectedAsin === null && $this->selectedSku !== null && $sku !== null && $this->selectedSku === $sku);

        if ($isSame) {
            $this->clearSelection();
        } else {
            // Prefer ASIN if available
            $this->selectedAsin = $asin;
            $this->selectedSku  = $asin ? null : $sku;
        }

        $this->loadChart();
    }

    public function clearAll(): void
    {
        $this->date = Carbon::now($this->marketTz)->toDateString();
        $this->dateRange = 'current';
        $this->salesChannel = null;
        $this->search = '';

        $this->clearSelection();
        $this->resetPage();
        $this->applyDateLabels();
        $this->loadChart();
    }

    public function clearSelection(): void
    {
        $this->selectedAsin = null;
        $this->selectedSku  = null;
    }

    /**
     * CHANGED: chart shows individual days (today + 7 separate lines when last_7_days is selected)
     */
    public function loadChart(): void
    {
        $this->applyDateLabels();

        // Validate date before processing
        if (!$this->isValidDate($this->date)) {
            $this->date = Carbon::now($this->marketTz)->toDateString();
        }

        $tz = $this->marketTz;

        $selectedDay = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();

        $nowMarket = Carbon::now($tz);
        $isToday   = $selectedDay->isSameDay($nowMarket);

        // Cutoff only if selected date is today
        $cutoff = $isToday ? $nowMarket : null;

        $selectedRows = $this->fetchDayRows($selectedDay);
        $selectedPoints = $this->toOverlayPoints($selectedRows, $tz, $cutoff);

        $series = [];

        // Handle comparison period based on dateRange
        if ($this->dateRange === 'last_7_days') {
            // Add individual lines for each of the 7 days
            for ($i = 1; $i <= 7; $i++) {
                $day = $selectedDay->copy()->subDays($i);
                $dayRows = $this->fetchDayRows($day);
                $dayPoints = $this->toOverlayPoints($dayRows, $tz, null);
                
                if ($dayPoints !== []) {
                    $series[] = [
                        'name' => $day->format('M d') . ' (' . $day->format('D') . ')',
                        'data' => $dayPoints,
                    ];
                }
            }
        } else {
            // Default: yesterday
            $prevDay = $selectedDay->copy()->subDay();
            $prevRows = $this->fetchDayRows($prevDay);
            $prevPoints = $this->toOverlayPoints($prevRows, $tz, null);
            
            if ($prevPoints !== []) {
                $series[] = [
                    'name' => $isToday ? 'Yesterday' : $prevDay->toDateString(),
                    'data' => $prevPoints,
                ];
            }
        }

        if ($selectedPoints !== []) {
            $series[] = [
                'name' => $isToday ? 'Today' : $selectedDay->toDateString(),
                'data' => $selectedPoints,
            ];
        }

        $this->chartTitle = $this->selectionTitle();
        $this->chartSubtitle = $this->buildSubtitle($selectedDay);

        $this->dispatch('hourlyProductOverlayReady', payload: [
            'series' => $series,
        ]);
    }

    private function isSelectedDayToday(): bool
    {
        // Validate date before processing
        if (!$this->isValidDate($this->date)) {
            return true; // Reset to today if invalid
        }

        $tz = $this->marketTz;

        $selectedDay = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();
        $nowMarket   = Carbon::now($tz);

        return $selectedDay->isSameDay($nowMarket);
    }

    private function applyDateLabels(): void
    {
        // Validate date before processing
        if (!$this->isValidDate($this->date)) {
            $this->date = Carbon::now($this->marketTz)->toDateString();
        }

        $tz = $this->marketTz;
        $selectedDay = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();
        $isToday = $this->isSelectedDayToday();

        if ($this->dateRange === 'last_7_days') {
            // Build labels for 7 individual days
            $this->dayLabels = [];
            for ($i = 1; $i <= 7; $i++) {
                $day = $selectedDay->copy()->subDays($i);
                $this->dayLabels[] = [
                    'label' => $day->format('M d'),
                    'full' => $day->toDateString(),
                ];
            }
            
            $this->tableLabelCurrent = $isToday ? 'Today' : $selectedDay->toDateString();
            $this->tableLabelPrev = 'Last 7 Days';
            return;
        }

        // Default: current (today + yesterday)
        $prevDay = $selectedDay->copy()->subDay();
        $this->dayLabels = [];

        if ($isToday) {
            $this->tableLabelCurrent = 'Today';
            $this->tableLabelPrev    = 'Yesterday';
            return;
        }

        $this->tableLabelCurrent = $selectedDay->toDateString();
        $this->tableLabelPrev    = $prevDay->toDateString();
    }


    public function render()
    {
        [$todayStart, $todayEnd] = $this->dayRangeMarket($this->date);

        $products = $this->productsQuery($todayStart, $todayEnd)
            ->paginate($this->perPage);

        $channels = $this->salesChannelsForDay($todayStart, $todayEnd);
        
        // Calculate totals across all products (not just current page)
        $totals = $this->calculateTotals($todayStart, $todayEnd);

        return view('livewire.dashboard.hourly-product-sales-page', [
            'products' => $products,
            'channels' => $channels,
            'dayLabels' => $this->dayLabels,
            'totals' => $totals,
        ]);
    }

    private function selectionTitle(): string
    {
        if ($this->selectedAsin) {
            return "Hourly Sales — ASIN: {$this->selectedAsin}";
        }

        if ($this->selectedSku) {
            return "Hourly Sales — SKU: {$this->selectedSku}";
        }

        return 'Hourly Sales — TOTAL (All Products)';
    }

    /**
     * Improved subtitle: shows selected date and compare date.
     */
    private function buildSubtitle(Carbon $selectedDay): string
    {
        $tz       = $this->marketTz;
        $nowMarket = Carbon::now($tz);
        $isToday  = $selectedDay->isSameDay($nowMarket);

        if ($this->dateRange === 'last_7_days') {
            $compareLabel = 'Last 7 Days (Individual)';
        } else {
            $compareLabel = $isToday
                ? 'Yesterday'
                : $selectedDay->copy()->subDay()->toDateString();
        }

        $mainLabel = $isToday
            ? 'Today'
            : $selectedDay->toDateString();

        return "Date: {$mainLabel} | Compare: {$compareLabel}"
            . ($this->salesChannel ? " | Channel: {$this->salesChannel}" : ' | Channel: All');
    }

    private function dayRangeMarket(string $date): array
    {
        // Validate date before processing
        if (!$this->isValidDate($date)) {
            $date = Carbon::now($this->marketTz)->toDateString();
        }

        $day = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz)->startOfDay();

        return [
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
        ];
    }

    private function productsQuery(Carbon $todayStart, Carbon $todayEnd): Builder
    {
        $salesTable = (new HourlyProductSales())->getTable();
        $catTable   = (new ProductCategorisation())->getTable();
        $currencyTable = (new Currency())->getTable();

        $query = HourlyProductSales::query()
            ->from($salesTable)
            ->leftJoin($catTable . ' as pc', 'pc.child_asin', '=', $salesTable . '.asin')
            ->leftJoin($currencyTable . ' as cur', 'cur.currency_code', '=', $salesTable . '.currency')
            ->select([
                $salesTable . '.asin',
                $salesTable . '.sku',
            ])
            ->addSelect(DB::raw('pc.child_short_name as child_short_name'))
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_today",
                [$todayStart, $todayEnd]
            )
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN ({$salesTable}.item_price * COALESCE(cur.conversion_rate_to_usd, 1)) ELSE 0 END) as sales_today",
                [$todayStart, $todayEnd]
            );

        // Determine comparison period based on dateRange
        if ($this->dateRange === 'last_7_days') {
            // Add individual columns for each of the 7 days
            for ($i = 1; $i <= 7; $i++) {
                $dayStart = $todayStart->copy()->subDays($i)->startOfDay();
                $dayEnd = $todayStart->copy()->subDays($i)->endOfDay();
                
                $query->selectRaw(
                    "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_day_{$i}",
                    [$dayStart, $dayEnd]
                )
                ->selectRaw(
                    "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN ({$salesTable}.item_price * COALESCE(cur.conversion_rate_to_usd, 1)) ELSE 0 END) as sales_day_{$i}",
                    [$dayStart, $dayEnd]
                );
            }
            
            $comparisonStart = $todayStart->copy()->subDays(7)->startOfDay();
            $comparisonEnd   = $todayStart->copy()->subSecond();
        } else {
            // Default: current (yesterday only)
            $comparisonStart = $todayStart->copy()->subDay();
            $comparisonEnd   = $todayEnd->copy()->subDay();
            
            $query->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_yesterday",
                [$comparisonStart, $comparisonEnd]
            )
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN ({$salesTable}.item_price * COALESCE(cur.conversion_rate_to_usd, 1)) ELSE 0 END) as sales_yesterday",
                [$comparisonStart, $comparisonEnd]
            );
        }

        return $query
            ->whereBetween($salesTable . '.sale_hour', [$comparisonStart, $todayEnd])
            ->when($this->salesChannel, fn(Builder $q) => $q->where($salesTable . '.sales_channel', $this->salesChannel))
            ->when($this->search !== '', function (Builder $q) use ($salesTable) {
                $s = '%' . str_replace('%', '\\%', $this->search) . '%';

                $q->where(function (Builder $w) use ($s, $salesTable) {
                    $w->where($salesTable . '.asin', 'like', $s)
                        ->orWhere($salesTable . '.sku', 'like', $s)
                        ->orWhere('pc.child_short_name', 'like', $s);
                });
            })
            ->groupBy($salesTable . '.asin', $salesTable . '.sku', 'pc.child_short_name')
            ->orderByDesc('units_today')
            ->orderByDesc('sales_today');
    }

    private function fetchDayRows(Carbon $dayMarket): Collection
    {
        $start = $dayMarket->copy()->startOfDay();
        $end   = $dayMarket->copy()->endOfDay();

        return HourlyProductSales::query()
            ->select([
                'sale_hour',
                DB::raw('SUM(total_units) as total_units'),
            ])
            ->whereBetween('sale_hour', [$start, $end])
            ->when($this->salesChannel, fn(Builder $q) => $q->where('sales_channel', $this->salesChannel))
            ->when($this->selectedAsin, fn(Builder $q) => $q->where('asin', $this->selectedAsin))
            ->when(!$this->selectedAsin && $this->selectedSku, fn(Builder $q) => $q->where('sku', $this->selectedSku))
            ->groupBy('sale_hour')
            ->orderBy('sale_hour')
            ->get();
    }

    private function toOverlayPoints(Collection $rows, string $marketTz, ?Carbon $cutoffMarket): array
    {
        $points = [];

        foreach ($rows as $row) {
            $tsMarket = Carbon::parse($row->sale_hour, $marketTz);

            if ($cutoffMarket && $tsMarket->gt($cutoffMarket)) {
                continue;
            }

            $minuteOfDay = ((int) $tsMarket->format('H') * 60) + (int) $tsMarket->format('i');

            $points[] = [
                'x' => $minuteOfDay,
                'y' => (int) $row->total_units,
            ];
        }

        return $points;
    }

    private function salesChannelsForDay(Carbon $start, Carbon $end): array
    {
        return HourlyProductSales::query()
            ->whereBetween('sale_hour', [$start, $end])
            ->whereNotNull('sales_channel')
            ->select('sales_channel')
            ->distinct()
            ->orderBy('sales_channel')
            ->pluck('sales_channel')
            ->map(fn($v) => (string) $v)
            ->values()
            ->all();
    }

    /**
     * Calculate totals across all products (respecting filters)
     */
    private function calculateTotals(Carbon $todayStart, Carbon $todayEnd): object
    {
        $salesTable = (new HourlyProductSales())->getTable();
        $currencyTable = (new Currency())->getTable();
        
        $query = HourlyProductSales::query()
            ->from($salesTable)
            ->leftJoin($currencyTable . ' as cur', 'cur.currency_code', '=', $salesTable . '.currency')
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_today",
                [$todayStart, $todayEnd]
            )
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN ({$salesTable}.item_price * COALESCE(cur.conversion_rate_to_usd, 1)) ELSE 0 END) as sales_today",
                [$todayStart, $todayEnd]
            );

        // Determine comparison period based on dateRange
        if ($this->dateRange === 'last_7_days') {
            // Add individual columns for each of the 7 days
            for ($i = 1; $i <= 7; $i++) {
                $dayStart = $todayStart->copy()->subDays($i)->startOfDay();
                $dayEnd = $todayStart->copy()->subDays($i)->endOfDay();
                
                $query->selectRaw(
                    "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_day_{$i}",
                    [$dayStart, $dayEnd]
                )
                ->selectRaw(
                    "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN ({$salesTable}.item_price * COALESCE(cur.conversion_rate_to_usd, 1)) ELSE 0 END) as sales_day_{$i}",
                    [$dayStart, $dayEnd]
                );
            }
            
            $comparisonStart = $todayStart->copy()->subDays(7)->startOfDay();
            $comparisonEnd   = $todayStart->copy()->subSecond();
        } else {
            // Default: current (yesterday only)
            $comparisonStart = $todayStart->copy()->subDay();
            $comparisonEnd   = $todayEnd->copy()->subDay();
            
            $query->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_yesterday",
                [$comparisonStart, $comparisonEnd]
            )
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN ({$salesTable}.item_price * COALESCE(cur.conversion_rate_to_usd, 1)) ELSE 0 END) as sales_yesterday",
                [$comparisonStart, $comparisonEnd]
            );
        }

        $query->whereBetween($salesTable . '.sale_hour', [$comparisonStart, $todayEnd])
            ->when($this->salesChannel, fn(Builder $q) => $q->where($salesTable . '.sales_channel', $this->salesChannel))
            ->when($this->selectedAsin, fn(Builder $q) => $q->where($salesTable . '.asin', $this->selectedAsin))
            ->when(!$this->selectedAsin && $this->selectedSku, fn(Builder $q) => $q->where($salesTable . '.sku', $this->selectedSku))
            ->when($this->search !== '', function (Builder $q) use ($salesTable) {
                $s = '%' . str_replace('%', '\\%', $this->search) . '%';
                $q->where(function (Builder $w) use ($s, $salesTable) {
                    $w->where($salesTable . '.asin', 'like', $s)
                        ->orWhere($salesTable . '.sku', 'like', $s);
                });
            });

        return (object) ($query->first() ?? []);
    }

    /**
     * Validate if a date string is in the correct format and represents a valid date
     */
    private function isValidDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz);
            // Ensure the parsed date matches the input to catch invalid dates like 00-02-2026
            return $parsed->toDateString() === $date;
        } catch (\Exception) {
            return false;
        }
    }
}
