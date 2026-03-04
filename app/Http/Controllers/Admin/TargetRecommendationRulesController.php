<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TargetBudgetRecommendationRule;

class TargetRecommendationRulesController extends Controller
{
    public function index()
    {
        $rules = TargetBudgetRecommendationRule::orderBy('priority')->get();
        return view('pages.admin.amzAds.rules.targets.index', compact('rules'));
    }

    public function edit($id)
    {
        $rule = TargetBudgetRecommendationRule::findOrFail($id);

        // Detect only active (non-null) fields
        $activeFields = collect([
            'min_ctr' => $rule->min_ctr,
            'max_ctr' => $rule->max_ctr,
            'min_conversion_rate' => $rule->min_conversion_rate,
            'max_conversion_rate' => $rule->max_conversion_rate,
            'min_acos' => $rule->min_acos,
            'max_acos' => $rule->max_acos,
            'min_clicks' => $rule->min_clicks,
            'min_sales' => $rule->min_sales,
            'min_impressions' => $rule->min_impressions,
        ])->filter(fn($v) => !is_null($v))->keys()->toArray();

        return view('pages.admin.amzAds.rules.targets.form', compact('rule', 'activeFields'));
    }

    public function update(Request $request, $id)
    {
        $rule = TargetBudgetRecommendationRule::findOrFail($id);

        $request->validate([
            'min_ctr'             => 'nullable|numeric|min:0|max:100',
            'max_ctr'             => 'nullable|numeric|min:0|max:100',
            'min_conversion_rate' => 'nullable|numeric|min:0|max:100',
            'max_conversion_rate' => 'nullable|numeric|min:0|max:100',
            'min_acos'            => 'nullable|numeric|min:0',
            'max_acos'            => 'nullable|numeric|min:0',
            'min_clicks'          => 'nullable|integer|min:0',
            'min_sales'           => 'nullable|integer|min:0',
            'min_impressions'     => 'nullable|integer|min:0',
            'action_label'        => 'required|string|max:255',
            'adjustment_type'     => 'required|in:keep,increase,decrease',
            'adjustment_value'    => 'nullable|numeric|min:0',
            'priority'            => 'required|integer|min:1',
            'is_active'           => 'required|boolean',
        ]);

        $rule->update($request->only([
            'min_ctr',
            'max_ctr',
            'min_conversion_rate',
            'max_conversion_rate',
            'min_acos',
            'max_acos',
            'min_clicks',
            'min_sales',
            'min_impressions',
            'action_label',
            'adjustment_type',
            'adjustment_value',
            'priority',
            'is_active'
        ]));

        return redirect()->route('admin.ads.performance.rules.target.index')->with('success', 'Rule updated successfully.');
    }

    public function partials()
    {
        $rules = TargetBudgetRecommendationRule::orderBy('priority')->get();

        $data = $rules->map(function ($rule) {
            return [
                'condition' => $rule->condition_text,
                'recommendation' => $rule->action_label ?? '—',
            ];
        });

        return response()->json($data);
    }
}
