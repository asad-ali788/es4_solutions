<?php

namespace App\Livewire\Ads;

use Livewire\Component;
use App\Models\CampaignRecommendations;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Jobs\Ai\GenerateAiRecommendation;
use Illuminate\Support\Facades\Log;

class CampaignAiRecommendation extends Component
{
    public int $campaignId;
    public string $column = 'rec';

    public $aiStatus;
    public $aiRecommendation;
    public $aiSuggestedBudget;

    public bool $polling   = false;
    public bool $clickable = true;

    protected $listeners = [
        'ai-campaign-updated' => 'onAiCampaignUpdated',
    ];

    public function mount(int $campaignId, string $column = 'rec'): void
    {
        $this->campaignId = $campaignId;
        $this->column     = $column;

        $this->loadCampaign();
        if ($this->column === 'rec') {
            $this->clickable = !in_array($this->aiStatus, ['pending', 'done'], true);
        }
    }

    protected function loadCampaign(): void
    {
        $campaign = CampaignRecommendations::findOrFail($this->campaignId);

        $this->aiStatus          = $campaign->ai_status;
        $this->aiRecommendation  = $campaign->ai_recommendation;
        $this->aiSuggestedBudget = $campaign->ai_suggested_budget;

        if ($this->column === 'rec') {
            // clickable only if not currently pending
            $this->clickable = $this->aiStatus !== 'pending';
        }
    }

    public function generate(): void
    {
        if ($this->column !== 'rec') {
            return;
        }
        $this->aiStatus          = 'pending';
        $this->aiRecommendation  = '⏳ Generating...';
        $this->aiSuggestedBudget = null;

        $this->clickable = false;
        $this->polling   = true;
        try {
            $campaign = CampaignRecommendations::findOrFail($this->campaignId);

            $query = $campaign->campaign_types === 'SP'
                ? AmzCampaigns::where('campaign_id', $campaign->campaign_id)->first()
                : AmzCampaignsSb::where('campaign_id', $campaign->campaign_id)->first();

            $campaign->update([
                'ai_status'         => 'pending',
                'ai_recommendation' => null,
            ]);

            $metrics = [
                'campaign_id'         => (string) $campaign->campaign_id ?? 'N/A',
                'country'             => $campaign->country ?? 'N/A',
                'campaign_types'      => $campaign->campaign_types ?? 'N/A',
                'daily_budget'        => $query->daily_budget ?? $campaign->total_daily_budget ?? 0,
                'yesterday_spend'     => $campaign->total_spend ?? 0,
                'yesterday_sales'     => $campaign->total_sales ?? 0,
                'yesterday_purchases' => $campaign->purchases7d ?? 0,
                'total_spend_7d'      => $campaign->total_spend_7d ?? 0,
                'total_sales_7d'      => $campaign->total_sales_7d ?? 0,
                'purchases7d_7d'      => $campaign->purchases7d_7d ?? 0,
                'acos_7d'             => $campaign->acos_7d ?? 0,
                'total_spend_14d'     => $campaign->total_spend_14d ?? 0,
                'total_sales_14d'     => $campaign->total_sales_14d ?? 0,
                'purchases7d_14d'     => $campaign->purchases7d_14d ?? 0,
                'type'                => 'campaign',
            ];

            $instruction = "Here are the campaign metrics:\n" . json_encode($metrics, JSON_PRETTY_PRINT);
            if (!empty($metrics['daily_budget'])) {
                $instruction .= "\n\nPlease analyze the performance and suggest whether the current bid ({$metrics['daily_budget']}) should be increased, decreased, or kept the same to improve results.";
            } else {
                $instruction .= "\n\nNote: No current bid is available. Please suggest an appropriate bid adjustment strategy based on the performance metrics.";
            }
            GenerateAiRecommendation::dispatch(
                'campaign',
                CampaignRecommendations::class,
                $campaign->id,
                $instruction,
                'ai_suggested_budget'
            )->onQueue('ai');
        } catch (\Throwable $e) {
            Log::error('AI generate error: ' . $e->getMessage(), ['campaign_id' => $this->campaignId]);

            $this->aiStatus         = 'failed';
            $this->aiRecommendation = '⚠️ Error';
            $this->polling          = false;
            $this->clickable        = true;
        }
    }

    public function checkStatus(): void
    {
        if ($this->column !== 'rec') {
            return;
        }

        $this->loadCampaign();

        if ($this->aiStatus === 'done') {
            $this->polling   = false;
            $this->clickable = false;
            $this->dispatch('ai-campaign-updated', $this->campaignId);
        } elseif ($this->aiStatus === 'failed') {
            $this->polling   = false;
            $this->clickable = true;
            $this->dispatch('ai-campaign-updated', $this->campaignId);
        }
        if ($this->column === 'rec') {
            $this->clickable = !in_array($this->aiStatus, ['pending', 'done'], true);
        }
    }

    public function onAiCampaignUpdated(int $campaignId): void
    {
        if ($campaignId === $this->campaignId) {
            $this->loadCampaign();
        }
    }

    public function render()
    {
        return view('livewire.ads.campaign-ai-recommendation');
    }
}
