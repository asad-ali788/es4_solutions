<?php

namespace App\Livewire\Selling;

use App\Models\AmazonSoldPrice;
use App\Models\PriceUpdateReason;
use Livewire\Component;
use App\Models\ProductListing;

class SellingPrice extends Component
{
    public $uuid;
    public $selling_price;
    public $profit;
    public $new_price;
    public $disableSetPriceButton = true;

    public $listing;
    public $pricing;
    public $reasons;

    public function mount($uuid)
    {
        $this->uuid = $uuid;
        $this->listing = ProductListing::with('pricing', 'product')->where('uuid', $uuid)->firstOrFail();
        $this->pricing = $this->listing->pricing;
        $this->reasons = PriceUpdateReason::all();

        $sku = $this->listing->product?->sku;
        $marketplaceId = config('marketplaces.marketplace_ids')[$this->listing->country] ?? null;

        $amazonSoldPrice = AmazonSoldPrice::where('seller_sku', $sku)
            ->when($marketplaceId, fn($q) => $q->where('marketplace_id', $marketplaceId))
            ->orderByDesc('created_at')
            ->first();

        $this->selling_price = $amazonSoldPrice->listing_price ?? ($this->pricing->base_price ?? '');
        $this->profit = '';
    }

    // Update profit whenever selling_price changes
    public function updatedSellingPrice()
    {
        if (!$this->selling_price || !$this->pricing) {
            $this->profit = $this->selling_price ? 'N/A' : '';
            return;
        }

        $landedCost = landed_cost(
            $this->pricing->item_price,
            $this->pricing->postage,
            $this->pricing->duty
        );

        $this->profit = Profit_made_USA(
            $this->selling_price,
            $landedCost,
            $this->pricing->fba_fee
        );
    }

    // Enable/disable button whenever new_price changes
    public function updatedNewPrice($value)
    {
        $this->disableSetPriceButton = empty($value);
    }

    // Trigger modal
    public function openModal()
    {
        if (!$this->new_price) return;
        $this->dispatch('openSetPriceModal', newPrice: $this->new_price);
    }

    public function render()
    {
        return view('livewire.selling.selling-price');
    }
}
