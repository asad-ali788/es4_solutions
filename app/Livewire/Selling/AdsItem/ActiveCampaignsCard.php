<?php

namespace App\Livewire\Selling\AdsItem;

use App\Models\AmzAdsProductPerformanceReport;
use App\Models\AmzAdsSbPurchasedProductReport;
use App\Models\AmzAdsProductPerformanceReportSd;
use Livewire\Component;
use Livewire\Attributes\Lazy;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzCampaignsSd;
use Carbon\Carbon;
use App\Models\ProductAsins;

#[Lazy]
class ActiveCampaignsCard extends Component
{
    public bool $ready = false;

    public int $spEnabled = 0;
    public int $sbEnabled = 0;
    public int $sdEnabled = 0;

    public ?string $spLastUpdated = null;
    public ?string $sbLastUpdated = null;
    public ?string $sdLastUpdated = null;
    public array $skuOptions = [];

    //  locked values from page / url
    public string $asin = '';
    public string $country = 'US';
    public string $campaignType = 'SP'; // from ?campaign=SP (default SP)

    public function refreshCounts(): void
    {
        /** ---------------- SP ---------------- */
        $spCampaignIds = AmzAdsProductPerformanceReport::query()
            ->where('asin', $this->asin)
            ->pluck('campaign_id')
            ->unique();

        $this->spEnabled = AmzCampaigns::query()
            ->whereIn('campaign_id', $spCampaignIds)
            ->where('campaign_state', 'ENABLED')
            ->count();

        $this->spLastUpdated = AmzCampaigns::query()
            ->whereIn('campaign_id', $spCampaignIds)
            ->max('updated_at');

        /** ---------------- SB ---------------- */
        $sbCampaignIds = AmzAdsSbPurchasedProductReport::query()
            ->where('asin', $this->asin)
            ->pluck('campaign_id')
            ->unique();

        $this->sbEnabled = AmzCampaignsSb::query()
            ->whereIn('campaign_id', $sbCampaignIds)
            ->where('campaign_state', 'ENABLED')
            ->count();

        $this->sbLastUpdated = AmzCampaignsSb::query()
            ->whereIn('campaign_id', $sbCampaignIds)
            ->max('updated_at');

        /** ---------------- SD ---------------- */
        $sdCampaignIds = AmzAdsProductPerformanceReportSd::query()
            ->where('asin', $this->asin)
            ->pluck('campaign_id')
            ->unique();

        $this->sdEnabled = AmzCampaignsSd::query()
            ->whereIn('campaign_id', $sdCampaignIds)
            ->where('campaign_state', 'ENABLED')
            ->count();

        $this->sdLastUpdated = AmzCampaignsSd::query()
            ->whereIn('campaign_id', $sdCampaignIds)
            ->max('updated_at');
    }

    public bool $showCreateModal = false;

    // user inputs
    public int $campaignCount    = 1;
    public float $totalBudget    = 10.00;
    public string $targetingType = 'AUTO';   // AUTO | MANUAL
    public string $matchType     = 'EXACT';  // BROAD | PHRASE | EXACT
    public ?string $selectedSku  = null;

    // generated
    public array $generatedCampaigns = [];
    public ?string $pstDate = null;

    public function mount(string $asin): void
    {
        $this->asin = strtoupper(trim($asin));
        $this->campaignType = strtoupper(request('campaign', 'SP'));
        $this->refreshCounts();
        $this->ready = true;
    }

    public function refreshCountsAction(): void
    {
        $this->refreshCounts();
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->generatedCampaigns = [];
        $this->pstDate = \Carbon\Carbon::now('America/Los_Angeles')->format('d-m-Y');
        $asinRow = ProductAsins::with(['products:id,sku'])
            ->where('asin1', $this->asin)
            ->first();
        $this->skuOptions = $asinRow?->products
            ?->pluck('sku', 'sku')  // value=sku, label=sku
            ->unique()
            ->values()
            ->toArray() ?? [];

        // default selection
        $this->selectedSku = $this->skuOptions[0] ?? null;
    }


    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function generateCampaignNames(): void
    {
        $this->validate([
            'campaignCount' => ['required', 'integer', 'min:1', 'max:50'],
            'totalBudget'   => ['required', 'numeric', 'min:0.01'],
            'targetingType' => ['required', 'in:AUTO,MANUAL'],
            'matchType'     => ['required', 'in:BROAD,PHRASE,EXACT'],
            'selectedSku'   => ['required'],
        ]);

        $pstDate       = \Carbon\Carbon::now('America/Los_Angeles')->format('d-m-Y');
        $this->pstDate = $pstDate;
        $asin          = $this->asin;                                                  // locked from page
        $type          = strtoupper($this->campaignType);                              // locked from URL (SP default)
        $targeting     = strtoupper($this->targetingType);
        $match         = strtoupper($this->matchType);
        $source        = 'SYSTEM';

        $perBudget = $this->campaignCount > 0
            ? round(((float)$this->totalBudget) / $this->campaignCount, 2)
            : 0.00;

        $items = [];
        // Generate random starting CMP ONCE
        $baseCmp = $this->randomCmpBase();
        for ($i = 0; $i < $this->campaignCount; $i++) {
            // Generate unique CMP_XXXX
            $cmpNumber = ($baseCmp + $i) % 10000;                                                  // wrap if > 9999
            $cmp       = str_pad((string) $cmpNumber, 4, '0', STR_PAD_LEFT);
            $country   = $this->country;
            $sku       = $this->selectedSku;
            $name      = "{$asin}_{$type}_{$targeting}_{$match}_{$source}_{$pstDate}_CMP_{$cmp}";

            $items[] = [
                'name'      => $name,
                'country'   => $country,
                'budget'    => $perBudget,
                'sku'       => $sku,
                'asin'      => $asin,
                'type'      => $type,
                'targeting' => $targeting,
                'match'     => $match,
                'date'      => $pstDate,
                'cmp'       => $cmp,
            ];
        }

        $this->generatedCampaigns = $items;
    }
    private function randomCmpBase(): int
    {
        return random_int(0, 9999); // 0000–9999
    }
    public function perCampaignBudget(): float
    {
        return $this->campaignCount > 0
            ? round(((float)$this->totalBudget) / $this->campaignCount, 2)
            : 0.00;
    }

    public function placeholder()
    {
        return view('livewire.selling.ads-item.skeleton.active-keywords-card-skeleton');
    }

    public function render()
    {
        return view('livewire.selling.ads-item.active-campaigns-card');
    }
}
