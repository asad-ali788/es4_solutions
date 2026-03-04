<?php

namespace App\Livewire\Dashboard;

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
        if (!in_array($name, ['date', 'search', 'perPage', 'salesChannel'], true)) {
            return;
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
     * CHANGED: chart always uses selected date and selected date - 1 day.
     * No more "today/yesterday" unless selected date is literally today.
     */
    public function loadChart(): void
    {
        $this->applyDateLabels();

        $tz = $this->marketTz;

        $selectedDay = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();
        $prevDay     = $selectedDay->copy()->subDay();

        $nowMarket = Carbon::now($tz);
        $isToday   = $selectedDay->isSameDay($nowMarket);

        // Cutoff only if selected date is today
        $cutoff = $isToday ? $nowMarket : null;

        $selectedRows = $this->fetchDayRows($selectedDay);
        $prevRows     = $this->fetchDayRows($prevDay);

        $selectedPoints = $this->toOverlayPoints($selectedRows, $tz, $cutoff);
        $prevPoints     = $this->toOverlayPoints($prevRows, $tz, null);

        $series = [];

        if ($prevPoints !== []) {
            $series[] = [
                'name' => $isToday ? 'Yesterday' : $prevDay->toDateString(),
                'data' => $prevPoints,
            ];
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
        $tz = $this->marketTz;

        $selectedDay = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();
        $nowMarket   = Carbon::now($tz);

        return $selectedDay->isSameDay($nowMarket);
    }

    private function applyDateLabels(): void
    {
        $tz = $this->marketTz;

        $selectedDay = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();
        $prevDay     = $selectedDay->copy()->subDay();

        if ($this->isSelectedDayToday()) {
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

        return view('livewire.dashboard.hourly-product-sales-page', [
            'products' => $products,
            'channels' => $channels,
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

        $compareLabel = $isToday
            ? 'Yesterday'
            : $selectedDay->copy()->subDay()->toDateString();

        $mainLabel = $isToday
            ? 'Today'
            : $selectedDay->toDateString();

        return "Date: {$mainLabel} | Compare: {$compareLabel}"
            . ($this->salesChannel ? " | Channel: {$this->salesChannel}" : ' | Channel: All');
    }

    private function dayRangeMarket(string $date): array
    {
        $day = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz)->startOfDay();

        return [
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
        ];
    }

    private function productsQuery(Carbon $todayStart, Carbon $todayEnd): Builder
    {
        $yesterdayStart = $todayStart->copy()->subDay();
        $yesterdayEnd   = $todayEnd->copy()->subDay();

        $salesTable = (new HourlyProductSales())->getTable();
        $catTable   = (new ProductCategorisation())->getTable();

        return HourlyProductSales::query()
            ->from($salesTable)
            ->leftJoin($catTable . ' as pc', 'pc.child_asin', '=', $salesTable . '.asin')
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
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.total_units ELSE 0 END) as units_yesterday",
                [$yesterdayStart, $yesterdayEnd]
            )
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.item_price ELSE 0 END) as sales_today",
                [$todayStart, $todayEnd]
            )
            ->selectRaw(
                "SUM(CASE WHEN {$salesTable}.sale_hour BETWEEN ? AND ? THEN {$salesTable}.item_price ELSE 0 END) as sales_yesterday",
                [$yesterdayStart, $yesterdayEnd]
            )
            ->whereBetween($salesTable . '.sale_hour', [$yesterdayStart, $todayEnd])
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
            ->orderByDesc('units_yesterday');
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
}
