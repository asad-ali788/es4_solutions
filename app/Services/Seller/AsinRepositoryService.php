<?php

namespace App\Services\Seller;

use App\Models\ProductAsins;
use App\Models\User;
use App\Models\ProductForecastAsins;
use App\Models\AsinRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsinRepositoryService
{
    public function getReportingUsers(int $userId): array
    {
        return User::where('reporting_to', $userId)->pluck('name', 'id')->toArray();
    }

    public function getAsinsForIndex(Request $request, $user, array $reportingUsers)
    {
        $query = ProductAsins::query()->select('asin1')->groupBy('asin1');

        $unrestrictedRoles = ['md', 'manager', 'developer', 'administrator'];
        $targetUserId = $request->input('select');

        if ($targetUserId && $targetUserId !== 'all') {
            $allowed = $user->hasAnyRole($unrestrictedRoles) || (string)$targetUserId === (string)$user->id || array_key_exists($targetUserId, $reportingUsers);

            if (!$allowed) {
                return $query->whereRaw('1=0')->paginate(15);
            }

            $query->whereIn('asin1', function ($subQuery) use ($targetUserId) {
                $subQuery->from('user_assigned_asins')
                    ->select('asin')
                    ->where('user_id', (int) $targetUserId)
                    ->whereNotNull('asin')
                    ->groupBy('asin');
            });
        } else {
            if (!$user->hasAnyRole($unrestrictedRoles)) {
                $userIds = array_merge([(int)$user->id], array_map('intval', array_keys($reportingUsers)));

                $query->whereIn('asin1', function ($subQuery) use ($userIds) {
                    $subQuery->from('user_assigned_asins')
                        ->select('asin')
                        ->whereIn('user_id', $userIds)
                        ->whereNotNull('asin')
                        ->groupBy('asin');
                });
            }
        }

        if ($request->filled('search')) {
            $like = '%' . $request->search . '%';

            $query->where(function ($q) use ($like) {
                $q->where('asin1', 'like', $like)
                    ->orWhereHas('categorisation', function ($catQ) use ($like) {
                        $catQ->where('child_short_name', 'like', $like);
                    });
            });
        }


        return $query->with(['products', 'categorisation'])->paginate(15);
    }

    public function getAsinWithProductOrFail(string $asin)
    {
        return ProductAsins::with('categorisation')->where('asin1', $asin)->firstOrFail();
    }

    public function getForecastForMonths($asin, $months)
    {
        return ProductForecastAsins::where('product_asin', $asin)
            ->whereIn('forecast_month', $months)
            ->select('forecast_month', DB::raw('SUM(forecast_units) as total_forecast_units'))
            ->groupBy('forecast_month')
            ->orderBy('forecast_month')
            ->get()
            ->keyBy('forecast_month');
    }

    public function getProductIdsForAsin($asin)
    {
        return DB::table('product_asins')
            ->select('product_id')
            ->where('asin1', $asin)
            ->orWhere('asin2', $asin)
            ->orWhere('asin3', $asin)
            ->pluck('product_id');
    }

    public function getSkusForProductIds($productIds)
    {
        return DB::table('products')->whereIn('id', $productIds)->pluck('sku');
    }

    public function getRecommendations($asin)
    {
        return AsinRecommendation::select(
            'asin',
            'country',
            'campaign_types',
            DB::raw('SUM(active_campaigns) as total_active_campaigns'),
            DB::raw('SUM(total_daily_budget) as total_daily_budget'),
            DB::raw('SUM(total_spend) as total_spend'),
            DB::raw('SUM(total_sales) as total_sales'),
            DB::raw('AVG(acos) as avg_acos')
        )
            ->where('asin', $asin)
            ->where('report_week', Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeek()->toDateString())
            ->groupBy('asin', 'country', 'campaign_types')
            ->orderByRaw("CASE WHEN country = 'US' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN campaign_types = 'SP' THEN 0 ELSE 1 END")
            ->orderBy('country')
            ->paginate(50);
    }
}
