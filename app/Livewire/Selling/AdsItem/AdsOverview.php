<?php

namespace App\Livewire\Selling\AdsItem;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Services\Ads\AdsOverviewService;

class AdsOverview extends Component
{
    public string $asin;
    public string $selectedDate;
    public array $overview = [];
    public array $filters = [];

    public function mount(string $asin, string $selectedDate): void
    {
        $this->asin = $asin;
        $this->selectedDate = $selectedDate;

        $this->filters = [
            'period' => request('period', '1d'),
            'source' => request('source', 'ads-item'),
            'date'   => $selectedDate,
            'asins[]' => $asin,
        ];

        $cacheKey = sprintf(
            'asin_ads_overview:%s:%s',
            $this->asin,
            $this->selectedDate
        );

        $this->overview = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () {
                /** @var AdsOverviewService $service */
                $service = app(AdsOverviewService::class);

                $overview = $service->getRangeSummaryFromReports(
                    $this->selectedDate,
                    1,
                    $this->asin
                );

                $overview['total'] = $service
                    ->buildTotalsFromOverview($overview);

                return $overview;
            }
        );
    }

    public function render()
    {
        return view('livewire.selling.ads-item.ads-overview',
            [
                'overview' => $this->overview,
                'filters' => $this->filters,
            ]
        );
    }
    public function placeholder()
    {
        return view('livewire.selling.ads-item.skeleton.ads-overview-skeleton');
    }
}
