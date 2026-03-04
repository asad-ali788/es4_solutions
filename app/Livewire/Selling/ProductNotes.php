<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use App\Models\ProductListing;
use App\Models\ProductNote;
use Illuminate\Support\Facades\Auth;

class ProductNotes extends Component
{
    public string $uuid;
    public $listing;
    public string $noteText = '';
    public string $priority = 'medium';

    public function mount(string $uuid)
    {
        $this->uuid = $uuid;
        $this->listing = ProductListing::with('productNotes')->where('uuid', $uuid)->firstOrFail();
    }

    public function addNote()
    {
        $this->authorize('selling.notes'); // permission check

        $this->validate([
            'noteText' => 'required|string',
            'priority' => 'nullable|string',
        ]);

        $note = ProductNote::create([
            'product_id' => $this->listing->products_id,
            'user_id'    => Auth::id(),
            'note'       => $this->noteText,
            'priority'   => $this->priority ?? 'medium',
        ]);

        // Refresh notes list
        $this->listing->refresh();

        // Reset textarea
        $this->noteText = '';

        // Send event for UI animation if needed
        $this->dispatch('note-added');
    }

    public function render()
    {
        return view('livewire.selling.product-notes');
    }
}
