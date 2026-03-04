<?php

namespace App\Livewire\Selling\AdsItem;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Services\Seller\SellingAdsItemService;

class SalesData extends Component
{
    public string $asin;

    public array $data = [];

    public function mount(string $asin, SellingAdsItemService $service): void
    {
        $this->asin = $asin;

        $cacheKey = "asin_sales_data:{$this->asin}";

        $this->data = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            fn() => $service->getAsinDetails($this->asin)
        );
    }

    public function render()
    {
        return view('livewire.selling.ads-item.sales-data',
            $this->data
        );
    }
    public function placeholder()
    {
        return view('livewire.selling.ads-item.skeleton.sales-data-skeleton');
    }
}
