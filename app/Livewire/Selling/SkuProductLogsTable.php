<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductListingLog;

class SkuProductLogsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $pageName = 'logs_page';

    public $listing; // ProductListing instance
    public $fieldFilter = '';
    public $marketFilter = '';
    public $fieldNames = [];

    protected $queryString = [
        'fieldFilter' => ['except' => ''],
        'marketFilter' => ['except' => ''],
    ];

    public function mount($listing)
    {
        $this->listing = $listing;
        $this->fieldNames = $this->extractFieldNames($listing);
    }

    // Reset page when filters change
    public function updatingFieldFilter()
    {
        $this->resetPage();
    }

    public function updatingMarketFilter()
    {
        $this->resetPage();
    }

    public function getLogsProperty()
    {
        $logsQuery = $this->listing->listingLog()->with('user');

        if (!empty($this->fieldFilter)) {
            $logsQuery->where('field_name', $this->fieldFilter);
        }

        if (!empty($this->marketFilter)) {
            $logsQuery->where('country', $this->marketFilter);
        }

        return $logsQuery->orderBy('updated_at', 'desc')
            ->paginate(5, ['*'], $this->pageName);
    }

    private function extractFieldNames($listing)
    {
        $relatedModels = array_merge(
            ['product', 'additionalDetail', 'pricing', 'containerInfo'],
            ['listingLog']
        );

        $fieldNames = collect((new ProductListingLog)->getFillable());

        foreach ($relatedModels as $relation) {
            if (method_exists($listing, $relation)) {
                $relatedInstance = $listing->$relation()->getRelated();
                $fieldNames = $fieldNames->merge($relatedInstance->getFillable());
            }
        }

        $excludeFields = ['uuid', 'products_id', 'user_id', 'product_id'];

        return $fieldNames
            ->unique()
            ->reject(fn($field) => in_array($field, $excludeFields))
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.selling.sku-product-logs-table', [
            'logs' => $this->logs,
        ]);
    }
}
