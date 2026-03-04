<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductRanking;
use Illuminate\Support\Facades\DB;

class SkuPriceRangeTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $pageName = 'price_page';

    public $product_id;
    public $price_days = 7;
    public $price_country = 'all';

    protected $queryString = [
        'price_days'     => ['except' => 7],
        'price_country'  => ['except' => 'all'],
    ];

    public function mount($product_id)
    {
        $this->product_id = $product_id;
    }

    public function updatingPriceDays()
    {
        $this->resetPage();
    }

    public function updatingPriceCountry()
    {
        $this->resetPage();
    }

    public function getDataProperty()
    {
        $query = ProductRanking::where('product_id', $this->product_id)
            ->whereNotNull('current_price')
            ->where('current_price', '>', 0)
            ->where('date', '>=', now()->subDays($this->price_days));

        if ($this->price_country !== 'all') {
            $query->where('country', $this->price_country);
        }

        return $query
            ->select(
                'country',
                DB::raw('MIN(current_price) AS min_price'),
                DB::raw('MAX(current_price) AS max_price'),
                'date'
            )
            ->groupBy('country', 'date')
            ->orderBy('date', 'desc')
            ->paginate(10);
    }

    public function render()
    {
        $uniqueCountries = ProductRanking::where('product_id', $this->product_id)
            ->pluck('country')
            ->unique()
            ->values();

        return view('livewire.selling.sku-price-range-table', [
            'priceRangeData' => $this->data,
            'uniqueCountries' => $uniqueCountries,
        ]);
    }
}
