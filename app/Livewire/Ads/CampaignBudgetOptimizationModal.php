<?php

namespace App\Livewire\Ads;

use Livewire\Component;
use App\Models\CampaignBudgetRecommendationRule;

class CampaignBudgetOptimizationModal extends Component
{
    public bool $show = false;
    public bool $loaded = false;
    public $rules = [];

    // LISTEN FOR OUTSIDE TRIGGER
    protected $listeners = [
        'open-budget-optimization' => 'open',
    ];

    public function open()
    {
        $this->show = true;
        $this->loaded = false;
        $this->rules = [];
    }

    public function loadRules()
    {
        if ($this->loaded) return;

        $rules = CampaignBudgetRecommendationRule::orderBy('priority')->get();

        $this->rules = $rules->map(function ($rule) {
            $condition = '';

            if ($rule->min_acos !== null  && $rule->max_acos !== null) {
                $condition .= "ACoS {$rule->min_acos}% >= {$rule->max_acos}%";
            } elseif ($rule->min_acos !== null) {
                $condition .= "ACoS > {$rule->min_acos}%";
            } elseif ($rule->max_acos !== null) {
                $condition .= "ACoS < {$rule->max_acos}%";
            }

            if ($rule->spend_condition === 'gte_budget') {
                $condition .= ' AND Spend ≥ Daily Budget';
            } elseif ($rule->spend_condition === 'lt_budget') {
                $condition .= ' AND Spend < Daily Budget';
            }
            if ($rule->spend_condition === 'spend_zero') {
                $condition .= " AND Spend > 0";
            }

            return [
                'condition' => $condition,
                'recommendation' => $rule->action_label,
            ];
        })->toArray();

        $this->loaded = true;
    }

    public function close()
    {
        $this->reset(['show', 'loaded', 'rules']);
    }

    public function render()
    {
        return view('livewire.ads.campaign-budget-optimization-modal');
    }
}
