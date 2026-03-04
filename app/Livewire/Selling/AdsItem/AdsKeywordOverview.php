<?php

namespace App\Livewire\Selling\AdsItem;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Services\Ads\AdsOverviewService;


use Livewire\Attributes\Url;

class AdsKeywordOverview extends Component
{
    public string $asin;

    #[Url(as: 'date')]
    public string $selectedDate;

    public ?string $productName = null;
    public array $overview = [];

    public function mount(string $asin, ?string $productName = null): void
    {
        $this->asin = $asin;
        $this->productName = $productName;

        // If selectedDate is not set from the URL, default to yesterday
        if (empty($this->selectedDate)) {
            $this->selectedDate = now(config('timezone.market'))->subDay()->toDateString();
        }

        $cacheKey = sprintf(
            'asin_keyword_overview:%s:%s',
            $this->asin,
            $this->selectedDate
        );

        $this->overview = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () {
                /** @var AdsOverviewService $service */
                $service = app(AdsOverviewService::class);

                $overview = $service->getKeywordRangeSummaryFromReports(
                    $this->selectedDate,
                    1,
                    $this->asin,
                    $this->productName
                );

                $overview['total'] = $service
                    ->buildKeywordTotalsFromOverview($overview);

                return $overview;
            }
        );
    }

    public function render()
    {
        return view('livewire.selling.ads-item.ads-keyword-overview', [
            'overview' => $this->overview,
            'selectedDate' => $this->selectedDate,
            'asin' => $this->asin,
            'productName' => $this->productName,
        ]);
    }

    public function placeholder()
    {
        return view('livewire.selling.ads-item.skeleton.ads-keyword-overview-skeleton');
    }
}
