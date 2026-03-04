<?php

namespace App\Livewire\Selling;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;

class AsinCampaignsTabs extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $asin;
    public $campaign_type = 'sp';     // sp or sb
    public $country = 'all';          // dropdown filter

    protected $queryString = [
        'campaign_type' => ['except' => 'sp'],
        'country'       => ['except' => 'all'],
    ];

    public function mount($asin)
    {
        $this->asin = $asin;
    }

    public function updatingCampaignType()
    {
        $this->resetPage();
    }

    public function updatingCountry()
    {
        $this->resetPage();
    }

    public function getDataProperty()
    {
        if ($this->campaign_type === 'sp') {
            $query = AmzCampaigns::query()
                ->select(
                    'amz_campaigns.campaign_id',
                    'amz_campaigns.campaign_name',
                    'amz_campaigns.campaign_state',
                    'amz_campaigns.daily_budget',
                    'amz_campaigns.targeting_type',
                    'amz_campaigns.country'
                )
                ->join('amz_ads_products as ap', 'amz_campaigns.campaign_id', '=', 'ap.campaign_id')
                ->where('amz_campaigns.campaign_state', 'ENABLED')
                ->where('ap.asin', $this->asin)
                ->whereIn('ap.country', ['US', 'CA']);

            if ($this->country !== 'all') {
                $query->where('amz_campaigns.country', $this->country);
            }

            return $query->distinct()->paginate(10);
        }

        // SB campaigns
        $query = AmzCampaignsSb::query()
            ->select(
                'amz_campaigns_sb.campaign_id',
                'amz_campaigns_sb.campaign_name',
                'amz_campaigns_sb.campaign_state',
                'amz_campaigns_sb.daily_budget',
                'amz_campaigns_sb.targeting_type',
                'amz_campaigns_sb.country'
            )
            ->join('amz_ads_products_sb as apsb', 'amz_campaigns_sb.campaign_id', '=', 'apsb.campaign_id')
            ->where('amz_campaigns_sb.campaign_state', 'ENABLED')
            ->where('apsb.asin', $this->asin)
            ->whereIn('apsb.country', ['US', 'CA']);

        if ($this->country !== 'all') {
            $query->where('amz_campaigns_sb.country', $this->country);
        }

        return $query->distinct()->paginate(10);
    }

    public function render()
    {
        // Load unique countries for dropdown
        $countries = collect(['US', 'CA']);

        return view('livewire.selling.asin-campaigns-tabs', [
            'campaignData' => $this->data,
            'uniqueCountries' => $countries
        ]);
    }
}
