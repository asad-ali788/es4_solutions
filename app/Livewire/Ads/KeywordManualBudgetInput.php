<?php

namespace App\Livewire\Ads;

use App\Enum\Permissions\AmzAdsEnum;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\AmzKeywordRecommendation;

class KeywordManualBudgetInput extends Component
{
    use AuthorizesRequests;

    public int $keywordId;
    public $manualBid;

    public function mount(int $keywordId, $manualBid = null)
    {
        $this->keywordId = $keywordId;
        $this->manualBid = $manualBid;
    }

    public function updatedManualBid($value)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordPerformanceManualBudget);

        // validate only this field
        $this->validateOnly('manualBid', [
            'manualBid' => 'nullable|numeric|min:0|max:50',
        ], [
            'manualBid.numeric' => 'Invalid number.',
            'manualBid.min'     => 'Value cannot be negative.',
            'manualBid.max'     => 'Max allowed is 50.',
        ]);

        $finalValue = ($value === '' || $value === null) ? null : (float) $value;

        AmzKeywordRecommendation::where('id', $this->keywordId)
            ->update([
                'manual_bid' => $finalValue,
                'run_status' => 'pending',
            ]);

        $message = $finalValue === null
            ? "Manual bid cleared"
            : "Manual bid updated to $finalValue";

        $this->dispatch(
            'show-toast',
            type: 'success',
            message: $message
        );
    }

    public function render()
    {
        return view('livewire.ads.keyword-manual-budget-input');
    }
}
