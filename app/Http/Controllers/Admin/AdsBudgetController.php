<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Http\Controllers\Controller;
use App\Models\AmzAdsCampaignsBudgetUsage;
use App\Models\CampaignBudgetRecommendations;
use App\Models\ProductCategorisation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdsBudgetController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsBudgetsUsage);
        $table = (new AmzAdsCampaignsBudgetUsage())->getTable();

        $query = AmzAdsCampaignsBudgetUsage::query()
            ->select([
                'id',
                'campaign_id',
                'campaign_type',
                'budget',
                'budget_usage_percent',
                'usage_updated_at',
                'updated_at',
            ])
            ->when($request->filled('search'), function ($builder) use ($request, $table) {
                $search = trim((string) $request->search);
                $this->applySearchFilter($builder, $table, $search);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $budgetUsages = $query->paginate((int) $request->input('per_page', 50));
        $this->attachProductNames($budgetUsages);

        return view('pages.admin.amzAds.budget.usage', compact('budgetUsages'));
    }

    public function recommendations(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsBudgetRecommendation);
        $table = (new CampaignBudgetRecommendations())->getTable();

        $query = CampaignBudgetRecommendations::query()
            ->select([
                'id',
                'campaign_id',
                'campaign_type',
                'rule_name',
                'suggested_budget',
                'suggested_budget_increase_percent',
                'seven_days_start_date',
                'seven_days_end_date',
                'estimated_missed_sales_lower',
                'percent_time_in_budget',
                'updated_at',
            ])
            ->when($request->filled('search'), function ($builder) use ($request, $table) {
                $search = trim((string) $request->search);
                $this->applySearchFilter(
                    $builder,
                    $table,
                    $search,
                    true
                );
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $recommendations = $query->paginate((int) $request->input('per_page', 50));
        $this->attachProductNames($recommendations);

        return view('pages.admin.amzAds.budget.recommendations', compact('recommendations'));
    }

    private function attachProductNames(LengthAwarePaginator $paginator): void
    {
        $rows = collect($paginator->items());
        if ($rows->isEmpty()) {
            return;
        }

        $campaignIdsByType = [
            'SP' => [],
            'SB' => [],
            'SD' => [],
        ];

        foreach ($rows as $row) {
            $type = strtoupper((string) ($row->campaign_type ?? ''));
            $campaignId = (string) ($row->campaign_id ?? '');

            if ($campaignId === '' || !isset($campaignIdsByType[$type])) {
                continue;
            }

            $campaignIdsByType[$type][] = $campaignId;
        }

        $campaignIdsByType = [
            'SP' => array_values(array_unique($campaignIdsByType['SP'])),
            'SB' => array_values(array_unique($campaignIdsByType['SB'])),
            'SD' => array_values(array_unique($campaignIdsByType['SD'])),
        ];

        $asinByTypeAndCampaign = [];
        $asinByTypeAndCampaign = array_merge(
            $asinByTypeAndCampaign,
            $this->loadAsinMap('SP', 'amz_ads_products', $campaignIdsByType['SP'])
        );
        $asinByTypeAndCampaign = array_merge(
            $asinByTypeAndCampaign,
            $this->loadAsinMap('SB', 'amz_ads_products_sb', $campaignIdsByType['SB'])
        );
        $asinByTypeAndCampaign = array_merge(
            $asinByTypeAndCampaign,
            $this->loadAsinMap('SD', 'amz_ads_products_sd', $campaignIdsByType['SD'])
        );

        $asins = array_values(array_unique(array_filter(array_values($asinByTypeAndCampaign))));

        $productNameByAsin = ProductCategorisation::query()
            ->whereIn('child_asin', $asins)
            ->pluck('child_short_name', 'child_asin')
            ->mapWithKeys(fn($name, $asin) => [trim((string) $asin) => trim((string) $name)])
            ->all();

        foreach ($rows as $row) {
            $type = strtoupper((string) ($row->campaign_type ?? ''));
            $campaignId = (string) ($row->campaign_id ?? '');
            $key = $type . '|' . $campaignId;

            $asin = $asinByTypeAndCampaign[$key] ?? null;
            $productName = $asin ? ($productNameByAsin[$asin] ?? null) : null;

            $row->asin = $asin;
            $row->product_name = ($productName && $productName !== '') ? $productName : 'N/A';
        }
    }

    private function applySearchFilter($builder, string $table, string $search, bool $includeRuleName = false): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $like = "%{$search}%";
        $productTablesByType = [
            'SP' => 'amz_ads_products',
            'SB' => 'amz_ads_products_sb',
            'SD' => 'amz_ads_products_sd',
        ];

        $builder->where(function ($query) use ($table, $like, $includeRuleName, $productTablesByType) {
            $query->where("{$table}.campaign_id", 'like', $like);

            if ($includeRuleName) {
                $query->orWhere("{$table}.rule_name", 'like', $like);
            }

            foreach ($productTablesByType as $type => $productTable) {
                $query->orWhereExists(function ($subQuery) use ($table, $type, $productTable, $like) {
                    $subQuery->select(DB::raw(1))
                        ->from("{$productTable} as p")
                        ->leftJoin('product_categorisations as pc', 'pc.child_asin', '=', 'p.asin')
                        ->whereColumn('p.campaign_id', "{$table}.campaign_id")
                        ->whereRaw("{$table}.campaign_type = ?", [$type])
                        ->where(function ($inner) use ($like) {
                            $inner->where('p.asin', 'like', $like)
                                ->orWhere('pc.child_short_name', 'like', $like);
                        });
                });
            }
        });
    }

    /**
     * @return array<string, string>
     */
    private function loadAsinMap(string $type, string $table, array $campaignIds): array
    {
        if (empty($campaignIds)) {
            return [];
        }

        $rows = DB::table($table)
            ->whereIn('campaign_id', $campaignIds)
            ->whereNotNull('asin')
            ->where('asin', '<>', '')
            ->select('campaign_id', DB::raw('MIN(asin) as asin'))
            ->groupBy('campaign_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$type . '|' . (string) $row->campaign_id] = trim((string) $row->asin);
        }

        return $map;
    }
}
