<?php

namespace App\Livewire\Ads;

use App\Models\CampaignKeywordRecommendation;
use App\Traits\HasFilteredAdsPerformance;
use Illuminate\Http\Request;
use Livewire\Component;

class OverviewCampaignKeywords extends Component
{
    use HasFilteredAdsPerformance;

    public bool $show = false;

    // Tab-1
    public bool $loaded = false;
    public array $rules = [];

    // Tab-2
    public bool $loadedReco = false;
    public array $recommended = [];

    public ?string $date = null;
    public int $campaignId;

    public string $activeTab = 'keywords'; // keywords | new_table

    public function mount(int $campaignId): void
    {
        $this->campaignId = $campaignId;
        $this->date = request('date');
    }

    public function open(): void
    {
        $this->show = true;

        // default tab
        $this->activeTab = 'keywords';

        // reset both tab states (fresh open)
        $this->loaded = false;
        $this->rules = [];

        $this->loadedReco = false;
        $this->recommended = [];
    }

    public function close(): void
    {
        $this->reset(['show', 'loaded', 'rules', 'loadedReco', 'recommended', 'activeTab']);
        $this->activeTab = 'keywords';
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab; // UI only
    }

    /**
     * ✅ Loads BOTH tables once when modal opens.
     * Called via wire:init="initModalData"
     */
    public function initModalData(): void
    {
        if (!$this->show) {
            return;
        }

        if (!$this->loaded) {
            $this->loadRulesInternal();
        }

        if (!$this->loadedReco) {
            $this->loadRecommendedInternal();
        }
    }

    /**
     * Tab-1 internal loader (stores arrays so Livewire doesn't lose runtime props)
     */
    private function loadRulesInternal(): void
    {
        $fakeRequest = new Request([
            'search' => $this->campaignId,
            'date'   => $this->date,
        ]);

        $query = $this->getFilteredKeywordsQuery($fakeRequest);

        $keywords = $query
            ->orderByDesc('amz_keyword_recommendations.total_sales')
            ->get();

        $merged = $this->mergeKeywordAsins($keywords);

        // ✅ store as plain arrays
        $this->rules = $merged->toArray();
        $this->loaded = true;
    }

    /**
     * Tab-2 internal loader
     */
    private function loadRecommendedInternal(): void
    {
        // 1️⃣ Get latest updated_at for this campaign
        $latestUpdatedAt = CampaignKeywordRecommendation::query()
            ->where('campaign_id', $this->campaignId)
            ->max('updated_at');

        if (!$latestUpdatedAt) {
            $this->recommended = [];
            $this->loadedReco = true;
            return;
        }

        // 2️⃣ Get ALL records from that latest update batch
        $this->recommended = CampaignKeywordRecommendation::query()
            ->where('campaign_id', $this->campaignId)
            ->where('match_type', 'BROAD')
            ->where('updated_at', $latestUpdatedAt)
            ->orderBy('keyword') // optional but recommended
            ->get()
            ->toArray();

        $this->loadedReco = true;
    }


    public function render()
    {
        return view('livewire.ads.overview-campaign-keywords');
    }

    /**
     * Returns a collection of ARRAYS, not Eloquent models.
     * This prevents "ASIN disappears after tab switch".
     */
    private function mergeKeywordAsins($keywords)
    {
        return $keywords->groupBy('keyword_id')->map(function ($group) {
            $first = $group->first();

            $allAsins = $group->pluck('asin')->filter()->unique()->values()->all();

            $related = $group->pluck('related_asin')->filter()->map(function ($item) {
                if (is_array($item)) {
                    return array_map(fn($v) => json_decode($v, true) ?: $v, $item);
                } elseif (is_string($item)) {
                    return json_decode($item, true) ?: [$item];
                }
                return [];
            })->flatten()->unique()->values()->all();

            $firstArr = $first->toArray();
            $firstArr['related_asin'] = array_values(array_unique(array_merge($allAsins, $related)));

            return $firstArr;
        })->values();
    }
}
