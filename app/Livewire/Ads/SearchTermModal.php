<?php

namespace App\Livewire\Ads;

use Livewire\Component;
use App\Models\SpSearchTermSummaryReport;

class SearchTermModal extends Component
{
    public bool $show = false;
    public bool $loaded = false;

    public $keywordId;
    public $days;
    public array $rows = [];

    protected $listeners = [
        'open-search-term-modal' => 'open',
    ];

    public function open($keywordId, $days): void
    {
        $this->keywordId = $keywordId;
        $this->days = $days;
        $this->show = true;

        $query = SpSearchTermSummaryReport::where('keyword_id', $keywordId);

        if ($days && in_array((int)$days, [7, 14])) {
            $query->whereDate('date', '>=', now()->subDays((int)$days)->toDateString());
        }

        $this->rows = $query
            ->orderBy('date', 'desc')
            ->get([
                'date',
                'keyword',
                'search_term',
                'impressions',
                'clicks',
                'cost_per_click',
                'cost',
                'purchases_7d',
                'sales_7d'
            ])
            ->map(fn($row) => [
                'date' => $row->date,
                'keyword' => $this->stripAsin($row->keyword),
                'search_term' => $this->stripAsin($row->search_term),
                'impressions' => number_format($row->impressions),
                'clicks' => number_format($row->clicks),
                'cost_per_click' => '$' . number_format($row->cost_per_click, 2),
                'cost' => '$' . number_format($row->cost, 2),
                'purchases_7d' => number_format($row->purchases_7d),
                'sales_7d' => '$' . number_format($row->sales_7d, 2),
            ])
            ->toArray();

        $this->loaded = true;
    }

    /**
     * Strip "asin=" prefix and quotes from a string
     */
    private function stripAsin(?string $value): ?string
    {
        if (!$value) return $value;

        $value = str_replace('asin=', '', $value);

        $value = trim($value, '"\'');

        return $value;
    }


    public function close(): void
    {
        $this->reset(['show', 'loaded', 'rows']);
    }

    public function render()
    {
        return view('livewire.ads.search-term-modal');
    }
}
