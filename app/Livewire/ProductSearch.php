<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\ProductCategorisation;

class ProductSearch extends Component
{
    public string $search = '';
    public array $results = [];

    #[Url(as: 'product', except: '')]
    public ?string $selectedProduct = null;

    public string $name = 'product';

    public function mount(string $name = 'product')
    {
        $this->name = $name;
        $this->search = $this->selectedProduct ?? '';
    }

    public function updatedSearch()
    {
        $term = trim($this->search);

        if ($term === '') {
            $this->results = [];
            return;
        }

        $this->results = ProductCategorisation::query()
            ->whereNull('deleted_at')
            ->whereNotNull('child_short_name')
            ->where('child_short_name', 'like', '%' . $term . '%')
            ->select('child_short_name')
            ->distinct()
            ->orderBy('child_short_name')
            ->limit(20)
            ->pluck('child_short_name')
            ->toArray();
    }

    public function selectProduct(string $productName): void
    {
        $this->selectedProduct = $productName; // URL sync
        $this->search = $productName;
        $this->results = [];

        $this->dispatch('product-selected', product: $productName);
    }

    public function submitTypedProduct(): void
    {
        $this->selectedProduct = trim($this->search) ?: null;
        $this->results = [];

        $this->dispatch('product-selected', product: $this->selectedProduct);
    }

    public function clearProduct(): void
    {
        $this->selectedProduct = null;
        $this->search = '';
        $this->results = [];

        $this->dispatch('product-selected', product: null);
    }

    #[On('product-clear')]
    public function clearFromParent(): void
    {
        $this->clearProduct();
    }

    public function render()
    {
        return view('livewire.product-search');
    }
}
