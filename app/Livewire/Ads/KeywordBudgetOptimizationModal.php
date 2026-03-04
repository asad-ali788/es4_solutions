<?php

namespace App\Livewire\Ads;

use Livewire\Component;
use App\Models\KeywordBidRecommendationRule;

class KeywordBudgetOptimizationModal extends Component
{
    public bool $show = false;
    public bool $loaded = false;
    public array $rules = [];

    // LISTEN FOR OUTSIDE TRIGGER
    protected $listeners = [
        'open-budget-optimization' => 'open',
    ];

    public function open(): void
    {
        $this->show = true;
        $rules = KeywordBidRecommendationRule::orderBy('priority')->get();

        $this->rules = $rules->map(function ($rule) {
            return [
                'condition'      => $rule->condition_text,
                'recommendation' => $rule->action_label ?? '—',
                'bid_adjustment' => $rule->bid_adjustment ?? '—',
                'priority'       => $rule->priority ?? '—',
                'status'         => $rule->is_active ? 'Active' : 'Inactive',
            ];
        })->toArray();

        $this->loaded = true;
    }

    public function close(): void
    {
        $this->reset(['show', 'loaded', 'rules']);
    }

    public function render()
    {
        return view('livewire.ads.keyword-budget-optimization-modal');
    }
}
