<?php

namespace App\Livewire\Selling\AdsItem;

use Livewire\Component;
use Livewire\Attributes\Lazy;
use Carbon\Carbon;

use App\Models\AmzAdsProductPerformanceReport;
use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordPerformanceReportSb;
use App\Models\AmzAdsKeywordSb;

#[Lazy]
class ActiveKeywordsCard extends Component
{
    public bool $ready = false;

    // counts
    public int $spEnabled = 0;
    public int $sbEnabled = 0;

    // last updated
    public ?string $spLastUpdated = null;
    public ?string $sbLastUpdated = null;

    // locked from page
    public string $asin = '';
    public string $country = 'US';

    public function mount(string $asin): void
    {
        $this->asin = strtoupper(trim($asin));
        $this->refreshCounts();
        $this->ready = true;
    }

    public function refreshCounts(): void
    {
        /** ---------------- SP Keywords ---------------- */
        $spCampaignIds = AmzAdsProductPerformanceReport::query()
            ->where('asin', $this->asin)
            ->pluck('campaign_id')
            ->unique();

        $this->spEnabled = AmzAdsKeywords::query()
            ->whereIn('campaign_id', $spCampaignIds)
            ->where('state', 'ENABLED')
            ->count();

        $this->spLastUpdated = AmzAdsKeywords::query()
            ->whereIn('campaign_id', $spCampaignIds)
            ->max('updated_at');

        /** ---------------- SB Keywords ---------------- */
        $sbCampaignIds = AmzAdsKeywordPerformanceReportSb::query()
            ->pluck('campaign_id')
            ->unique();

        $this->sbEnabled = AmzAdsKeywordSb::query()
            ->whereIn('campaign_id', $sbCampaignIds)
            ->where('state', 'ENABLED')
            ->count();

        $this->sbLastUpdated = AmzAdsKeywordSb::query()
            ->whereIn('campaign_id', $sbCampaignIds)
            ->max('updated_at');
    }

    public function refreshCountsAction(): void
    {
        $this->refreshCounts();
    }

    public function placeholder()
    {
        return view('livewire.selling.ads-item.skeleton.active-keywords-card-skeleton');
    }

    public function render()
    {
        return view('livewire.selling.ads-item.active-keywords-card');
    }
}
