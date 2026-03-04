<?php

namespace Database\Seeders;

use App\Models\CampaignBudgetRecommendationRule;
use App\Models\KeywordBidRecommendationRule;
use App\Models\TargetBudgetRecommendationRule;
use Illuminate\Database\Seeder;

class BudgetRecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Campaign Rules
         */
        $campaignRules = [
            // ACOS < 30 & spend >= daily budget
            [
                'min_acos' => 0,
                'max_acos' => 30,
                'spend_condition' => 'gte_budget',
                'action_label' => '🔼 Increase budget 30%',
                'adjustment_type' => 'increase',
                'adjustment_value' => 30,
                'priority' => 1,
            ],
            // ACOS < 30 & spend < daily budget
            [
                'min_acos' => 0,
                'max_acos' => 30,
                'spend_condition' => 'lt_budget',
                'action_label' => '✅ Keep same budget',
                'adjustment_type' => 'keep',
                'adjustment_value' => null,
                'priority' => 2,
            ],
            // ACOS between 30 and 40, spend any
            [
                'min_acos' => 30,
                'max_acos' => 40,
                'spend_condition' => 'any',
                'action_label' => '✅ Keep same budget (optimize keywords/placements)',
                'adjustment_type' => 'keep',
                'adjustment_value' => null,
                'priority' => 3,
            ],
            // ACOS > 40, spend any
            [
                'min_acos' => 40,
                'max_acos' => null,
                'spend_condition' => 'any',
                'action_label' => '🔽 Reduce budget by 20% (campaign inefficient)',
                'adjustment_type' => 'decrease',
                'adjustment_value' => 20,
                'priority' => 4,
            ],
            // ACOS > 0.00
            [
                'min_acos' => 0.00,
                'max_acos' => 0.00,
                'spend_condition' => 'any',
                'action_label' => '🔽 Reduce budget by 30%',
                'adjustment_type' => 'decrease',
                'adjustment_value' => 30,
                'priority' => 5,
            ],
            // SPEND > 0.00
            [
                'min_acos' => 0.00,
                'max_acos' => 0.00,
                'spend_condition' => 'spend_zero',
                'action_label' => '🔽 Reduce budget by 30%',
                'adjustment_type' => 'decrease',
                'adjustment_value' => 30,
                'priority' => 6,
            ],
        ];

        foreach ($campaignRules as $rule) {
            CampaignBudgetRecommendationRule::create($rule);
        }

        /**
         * Keyword Rules (structured for model schema)
         */
        $keywordRules = [
            [
                'ctr_condition'         => 1,   // >1%
                'conversion_condition'  => 15,  // >15%
                'acos_condition'        => 30,  // <30%
                'click_condition'       => null,
                'sales_condition'       => null,
                'impressions_condition' => null,
                'bid_adjustment'        => 1.15,
                'action_label'          => '🚀 Increase bid by 10–20% to capture more impressions and scale sales.',
                'priority'              => 1,
                'is_active'             => true,
            ],
            [
                'ctr_condition'         => 1,
                'conversion_condition'  => 5,
                'acos_condition'        => 30,
                'click_condition'       => null,
                'sales_condition'       => null,
                'impressions_condition' => null,
                'bid_adjustment'        => 0.90,
                'action_label'          => '⚠️ Keyword is attracting clicks but not converting → Lower bid OR move to Negative Keywords.',
                'priority'              => 2,
                'is_active'             => true,
            ],
            [
                'ctr_condition'         => 0.3,
                'conversion_condition'  => null,
                'acos_condition'        => null,
                'click_condition'       => null,
                'sales_condition'       => 0,
                'impressions_condition' => null,
                'bid_adjustment'        => '❌ Pause',
                'action_label'          => '❌ Pause keyword or refine match type (likely irrelevant to shoppers).',
                'priority'              => 3,
                'is_active'             => true,
            ],
            [
                'ctr_condition'         => 0.3,
                'conversion_condition'  => 5,
                'acos_condition'        => 30,
                'click_condition'       => null,
                'sales_condition'       => null,
                'impressions_condition' => null,
                'bid_adjustment'        => 0.88,
                'action_label'          => '🔽 Lower bid 10–15% and monitor → test if profitability improves.',
                'priority'              => 4,
                'is_active'             => true,
            ],
            [
                'ctr_condition'         => 0.2,
                'conversion_condition'  => null,
                'acos_condition'        => null,
                'click_condition'       => null,
                'sales_condition'       => null,
                'impressions_condition' => 1000,
                'bid_adjustment'        => '❌ Pause',
                'action_label'          => '❌ Keyword is wasting impressions, hurting ad rank → pause or test different creatives.',
                'priority'              => 5,
                'is_active'             => true,
            ],
            [
                'ctr_condition'         => 0.7,
                'conversion_condition'  => null,
                'acos_condition'        => 30,
                'click_condition'       => null,
                'sales_condition'       => 0,
                'impressions_condition' => null,
                'bid_adjustment'        => 0.93,
                'action_label'          => '🔽 Lower bid slightly to improve efficiency, but keep keyword active.',
                'priority'              => 6,
                'is_active'             => true,
            ],
            [
                'ctr_condition'         => null,
                'conversion_condition'  => null,
                'acos_condition'        => null,
                'click_condition'       => 25,
                'sales_condition'       => 0,
                'impressions_condition' => null,
                'bid_adjustment'        => '❌ Pause',
                'action_label'          => '❌ Pause or Negative Keyword (clear waste of spend).',
                'priority'              => 7,
                'is_active'             => true,
            ],
        ];

        foreach ($keywordRules as $rule) {
            KeywordBidRecommendationRule::updateOrCreate(
                ['priority' => $rule['priority']],
                $rule
            );
        }


        /**
         * Target Rules
         */
        $targetRules = [
            [
                'min_ctr'             => 1,
                'max_ctr'             => null,
                'min_conversion_rate' => 15,
                'max_conversion_rate' => null,
                'min_acos'            => null,
                'max_acos'            => 30,
                'action_label'        => '🚀 Increase bid by 10–20% to capture more impressions and scale sales.',
                'adjustment_type'     => 'increase',
                'adjustment_value'    => 15,
                'priority'            => 1,
                'is_active'           => true,
            ],
            [
                'min_ctr'             => 1,
                'max_ctr'             => null,
                'min_conversion_rate' => null,
                'max_conversion_rate' => 5,
                'min_acos'            => 30,
                'max_acos'            => null,
                'action_label'        => '⚠️ Target is attracting clicks but not converting → Lower bid OR move to Negative Target.',
                'adjustment_type'     => 'decrease',
                'adjustment_value'    => 10,
                'priority'            => 2,
                'is_active'           => true,
            ],
            [
                'max_ctr'             => 0.3,
                'min_sales'           => 0,
                'action_label'        => '❌ Pause target or refine targeting (likely irrelevant to shoppers).',
                'adjustment_type'     => 'pause',
                'priority'            => 3,
                'is_active'           => true,
            ],
            [
                'min_ctr'             => 0.3,
                'max_ctr'             => 1,
                'min_conversion_rate' => 5,
                'max_conversion_rate' => 15,
                'min_acos'            => 30,
                'max_acos'            => null,
                'action_label'        => '🔽 Lower bid 10–15% and monitor → test if profitability improves.',
                'adjustment_type'     => 'decrease',
                'adjustment_value'    => 12,
                'priority'            => 4,
                'is_active'           => true,
            ],
            [
                'min_impressions'     => 1000,
                'max_ctr'             => 0.2,
                'action_label'        => '⚠️ Target is wasting impressions, hurting ad rank → pause or test different ad creatives.',
                'adjustment_type'     => 'pause',
                'priority'            => 5,
                'is_active'           => true,
            ],
            [
                'min_ctr'             => 0.7,
                'min_sales'           => 1,
                'min_acos'            => 30,
                'action_label'        => '🔽 Lower bid slightly to improve efficiency, but keep target active.',
                'adjustment_type'     => 'decrease',
                'adjustment_value'    => 7,
                'priority'            => 6,
                'is_active'           => true,
            ],
            [
                'min_clicks'          => 25,
                'min_sales'           => 0,
                'action_label'        => '❌ Pause or Negative Target (clear waste).',
                'adjustment_type'     => 'pause',
                'priority'            => 7,
                'is_active'           => true,
            ],
        ];

        foreach ($targetRules as $rule) {
            TargetBudgetRecommendationRule::create($rule);
        }
    }
}
