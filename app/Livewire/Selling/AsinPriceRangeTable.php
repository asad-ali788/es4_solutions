<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductRanking;
use Illuminate\Support\Facades\DB;

class AsinPriceRangeTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $asin;
    public $price_days = 7;
    public $price_country = 'all';

    protected $queryString = [
        'price_days'    => ['except' => 7],
        'price_country' => ['except' => 'all'],
    ];

    public function mount($asin)
    {
        $this->asin = $asin;
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
        $query = ProductRanking::join('product_asins as pa', 'product_rankings.product_id', '=', 'pa.product_id')
            ->where('pa.asin1', $this->asin)
            ->where('product_rankings.current_price', '>', 0)
            ->where('product_rankings.date', '>=', now()->subDays($this->price_days)->toDateString());

        if ($this->price_country !== 'all') {
            $query->where('product_rankings.country', $this->price_country);
        }

        return $query
            ->select(
                'product_rankings.country',
                DB::raw('MAX(product_rankings.current_price) AS min_price'),
                DB::raw('DATE(product_rankings.date) AS date')
            )
            ->groupBy('product_rankings.country', DB::raw('DATE(product_rankings.date)'))
            ->orderBy('date', 'desc')
            ->paginate(10, ['*'], 'pricePage');
    }

    public function render()
    {
        $uniqueCountries = ProductRanking::join('product_asins as pa', 'product_rankings.product_id', '=', 'pa.product_id')
            ->where('pa.asin1', $this->asin)
            ->pluck('product_rankings.country')
            ->unique()
            ->values();

        return view('livewire.selling.asin-price-range-table', [
            'priceData'       => $this->data,
            'uniqueCountries' => $uniqueCountries,
        ]);
    }
}
