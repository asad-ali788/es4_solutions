<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductRanking;
use Illuminate\Support\Facades\DB;

class AsinRankingTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $asin;
    public $ranking_days = 7;
    public $ranking_country = 'all';

    protected $queryString = [
        'ranking_days'     => ['except' => 7],
        'ranking_country'  => ['except' => 'all'],
    ];

    public function mount($asin)
    {
        $this->asin = $asin;
    }

    public function updatingRankingDays()
    {
        $this->resetPage();
    }

    public function updatingRankingCountry()
    {
        $this->resetPage();
    }

    public function getDataProperty()
    {
        $query = ProductRanking::join('product_asins as pa', 'product_rankings.product_id', '=', 'pa.product_id')
            ->where('pa.asin1', $this->asin)
            ->where('product_rankings.rank', '>', 0)
            ->where('product_rankings.date', '>=', now()->subDays($this->ranking_days)->toDateString());

        if ($this->ranking_country !== 'all') {
            $query->where('product_rankings.country', $this->ranking_country);
        }

        return $query
            ->select(
                'product_rankings.country',
                DB::raw('MIN(product_rankings.rank) AS min_rank'),
                DB::raw('DATE(product_rankings.date) AS date')
            )
            ->groupBy('product_rankings.country', DB::raw('DATE(product_rankings.date)'))
            ->orderBy('date', 'desc')
            ->paginate(10, ['*'], 'rankingPage');
    }

    public function render()
    {
        $uniqueCountries = ProductRanking::join('product_asins as pa', 'product_rankings.product_id', '=', 'pa.product_id')
            ->where('pa.asin1', $this->asin)
            ->pluck('product_rankings.country')
            ->unique()
            ->values();

        return view('livewire.selling.asin-ranking-table', [
            'productRankingData' => $this->data,
            'uniqueCountries'    => $uniqueCountries,
        ]);
    }
}
