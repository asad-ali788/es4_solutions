<?php

namespace App\Livewire;

use App\Models\ProductAsins;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class SkuSearch extends Component
{
    #[Reactive]
    public ?string $asin = null;

    #[Reactive]
    public ?string $error = null;

    /** @var array<int, string> */
    public array $skuOptions = [];

    /** @var array<int, string> */
    public array $selectedSkus = [];

    public function mount(): void
    {
        $this->refresh();
        $this->dispatchSelected();
        $this->dispatch('sku-select2-refresh');
    }

    public function updatedAsin(): void
    {
        $this->refresh();
        $this->dispatchSelected();
        $this->dispatch('sku-select2-refresh');
    }

    /**
     * Called from JS (Select2 change)
     *
     * @param array<int, string>|string|null $value
     */
    public function setSelectedSkus(string|array|null $value): void
    {
        // Select2 multi -> array, but normalize anyway
        $skus = [];

        if (is_array($value)) {
            $skus = $value;
        } elseif (is_string($value) && $value !== '') {
            $skus = [$value];
        }

        $skus = collect($skus)
            ->map(fn($v) => is_string($v) ? trim($v) : '')
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        // Only allow values that exist in options
        $skus = array_values(array_intersect($skus, $this->skuOptions));

        $this->selectedSkus = $skus;

        $this->dispatchSelected();
    }

    #[On('sku-refresh')]
    public function forceRefresh(?string $asin = null): void
    {
        if ($asin !== null) {
            $this->asin = $asin;
        }

        $this->refresh();
        $this->dispatchSelected();
        $this->dispatch('sku-select2-refresh');
    }

    private function refresh(): void
    {
        $this->skuOptions = [];
        $this->selectedSkus = [];

        if (!$this->asin) {
            return;
        }

        $asinRow = ProductAsins::query()
            ->with(['products:id,sku'])
            ->where('asin1', $this->asin)
            ->first();

        $this->skuOptions = $asinRow?->products
            ?->pluck('sku')
            ->filter()
            ->unique()
            ->values()
            ->toArray() ?? [];

        $this->selectedSkus = [];
    }

    private function dispatchSelected(): void
    {
        // Parent must handle skus array
        $this->dispatch('sku-selected', skus: $this->selectedSkus);
    }
    public function render()
    {
        return view('livewire.sku-search');
    }
}
