<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\ProductAsins;

class AsinSearch extends Component
{
    public string $search = '';
    public array $results = [];

    #[Url(as: 'asin', except: '')]
    public ?string $selectedAsin = null;

    public string $name = 'asin';

    public function mount(string $name = 'asin')
    {
        $this->name = $name;
        $this->search = $this->selectedAsin ?? '';
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->results = [];
            return;
        }

        $this->results = ProductAsins::query()
            ->select('asin1')
            ->whereNotNull('asin1')
            ->where('asin1', 'like', '%' . $this->search . '%')
            ->distinct()
            ->orderBy('asin1')
            ->limit(20)
            ->pluck('asin1')
            ->toArray();
    }

    public function selectAsin(string $asin): void
    {
        $this->selectedAsin = $asin; // URL sync
        $this->search = $asin;
        $this->results = [];

        // notify parent
        $this->dispatch('asin-selected', asin: $asin);
    }

    public function submitTypedAsin(): void
    {
        $this->selectedAsin = $this->search;
        $this->results = [];

        $this->dispatch('asin-selected', asin: $this->selectedAsin);
    }

    public function clearAsin(): void
    {
        $this->selectedAsin = null;
        $this->search = '';
        $this->results = [];

        $this->dispatch('asin-selected', asin: null);
    }

    // optional: parent "Clear" button clears this input UI too
    #[On('asin-clear')]
    public function clearFromParent(): void
    {
        $this->clearAsin();
    }

    public function render()
    {
        return view('livewire.asin-search');
    }
}
