<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CampaignBudgetRecommendationRule;
use App\Enum\Permissions\AmzAdsEnum;

class CampaignRecommendationRulesController extends Controller
{
    /**
     * Show all rules
     */
    public function index()
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignRuleUpdate);
        $rules = CampaignBudgetRecommendationRule::orderBy('priority')->paginate(15);
        return view('pages.admin.amzAds.rules.campaign.index', compact('rules'));
    }

    /**
     * Show the form to edit a rule
     */
    public function edit($id)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignRuleUpdate);
        $rule = CampaignBudgetRecommendationRule::findOrFail($id);
        return view('pages.admin.amzAds.rules.campaign.form', compact('rule'));
    }

    /**
     * Update a rule
     */
    public function update(Request $request, $id)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignRuleUpdate);
        $rule = CampaignBudgetRecommendationRule::findOrFail($id);

        $request->validate([
            'min_acos' => 'required|numeric|min:0',
            'max_acos' => 'nullable|numeric|gte:min_acos',
            'spend_condition' => 'required|in:any,gte_budget,lt_budget',
            'action_label' => 'required|string|max:255',
            'adjustment_type' => 'required|in:keep,increase,decrease',
            'adjustment_value' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $rule->update([
            'min_acos' => $request->min_acos,
            'max_acos' => $request->max_acos,
            'spend_condition' => $request->spend_condition,
            'action_label' => $request->action_label,
            'adjustment_type' => $request->adjustment_type,
            'adjustment_value' => $request->adjustment_value,
            'priority' => $request->priority,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.ads.performance.rules.index')->with('success', 'Rule updated successfully.');
    }
}
