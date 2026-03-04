<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductRanking;
use Illuminate\Support\Facades\DB;

class SkuRankingTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $pageName = 'rank_page';
    
    public $product_id;
    public $ranking_days = 7;
    public $ranking_country = 'all';

    protected $queryString = [
        'ranking_days'     => ['except' => 7],
        'ranking_country'  => ['except' => 'all'],
    ];

    public function mount($product_id)
    {
        $this->product_id = $product_id;
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
        $query = ProductRanking::where('product_id', $this->product_id)
            ->whereNotNull('rank')
            ->where('rank', '>', 0)
            ->where('date', '>=', now()->subDays($this->ranking_days));

        if ($this->ranking_country !== 'all') {
            $query->where('country', $this->ranking_country);
        }

        return $query
            ->select(
                'country',
                DB::raw('MIN(`rank`) AS min_rank'),
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

        return view('livewire.selling.sku-ranking-table', [
            'productRankingData' => $this->data,
            'uniqueCountries'    => $uniqueCountries,
        ]);
    }
}
