<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KeywordBidRecommendationRule;

class KeywordRecommendationRulesController extends Controller
{
    /**
     * Display a listing of the keyword rules.
     */
    public function index()
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordRuleUpdate);
        $rules = KeywordBidRecommendationRule::orderBy('priority')->paginate(15);
        return view('pages.admin.amzAds.rules.keyword.index', compact('rules'));
    }

    /**
     * Show the form for creating a new keyword rule.
     */
    public function create()
    {
        return view('pages.admin.amzAds.rules.keyword.form');
    }

    /**
     * Store a newly created keyword rule in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ctr_condition'        => 'nullable|string',
            'conversion_condition' => 'nullable|string',
            'acos_condition'       => 'nullable|string',
            'click_condition'      => 'nullable|string',
            'sales_condition'      => 'nullable|string',
            'impressions_condition' => 'nullable|string',
            'bid_adjustment' => 'nullable|string',
            'action_label'         => 'required|string|max:255',
            'is_active'            => 'required|boolean',
        ]);


        KeywordBidRecommendationRule::create($request->all());

        return redirect()->route('admin.ads.performance.rules.keyword.index')
            ->with('success', 'Keyword rule created successfully.');
    }

    /**
     * Show the form for editing the specified keyword rule.
     */
    public function edit($id)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordRuleUpdate);
        $rule = KeywordBidRecommendationRule::findOrFail($id);
        return view('pages.admin.amzAds.rules.keyword.form', compact('rule'));
    }

    /**
     * Update the specified keyword rule in storage.
     */
    public function update(Request $request, $id)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsKeywordRuleUpdate);
        $rule = KeywordBidRecommendationRule::findOrFail($id);

        $request->validate([
            'ctr_condition'        => 'nullable|string',
            'conversion_condition' => 'nullable|string',
            'acos_condition'       => 'nullable|string',
            'click_condition'      => 'nullable|string',
            'sales_condition'      => 'nullable|string',
            'impressions_condition' => 'nullable|string',
            'action_label'         => 'required|string|max:255',
            'bid_adjustment' => 'nullable|string',
            'is_active'            => 'required|boolean',
        ]);


        $rule->update($request->all());

        return redirect()->route('admin.ads.performance.rules.keyword.index')
            ->with('success', 'Keyword rule updated successfully.');
    }

    /**
     * Remove the specified keyword rule from storage.
     */
    public function destroy($id)
    {
        $rule = KeywordBidRecommendationRule::findOrFail($id);
        $rule->delete();

        return redirect()->route('admin.ads.performance.rules.keyword.index')
            ->with('success', 'Keyword rule deleted successfully.');
    }
}
