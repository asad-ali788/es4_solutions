<?php

namespace App\Livewire\Ads;

use App\Enum\Permissions\AmzAdsEnum;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\CampaignRecommendations;

class CampaignManualBudgetInput extends Component
{
    use AuthorizesRequests;

    public int $campaignId;
    public $manual_budget;

    public function mount(int $campaignId, $manualBudget = null)
    {
        $this->campaignId    = $campaignId;
        $this->manual_budget = $manualBudget;
    }
    public function updatedManualBudget($value)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignPerformanceManualBudget);

        $this->validateOnly('manual_budget', [
            'manual_budget' => 'nullable|numeric|min:0|max:50',
        ], [
            'manual_budget.numeric' => 'Invalid number.',
            'manual_budget.min'     => 'Value cannot be negative.',
            'manual_budget.max'     => 'Max allowed is 50.',
        ]);

        $finalValue = ($value === '' || $value === null) ? null : (float) $value;

        CampaignRecommendations::where('id', $this->campaignId)
            ->update([
                'manual_budget' => $finalValue,
                'run_status'    => 'pending',
            ]);

        $message = $finalValue === null
            ? "Manual Budget cleared"
            : "Manual Budget updated to $finalValue";

        $this->dispatch(
            'show-toast',
            type: 'success',
            message: $message
        );
    }

    public function render()
    {
        return view('livewire.ads.campaign-manual-budget-input');
    }
}
