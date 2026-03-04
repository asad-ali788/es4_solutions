<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\SellingEnum;
use App\Http\Controllers\Controller;
use App\Models\AfnInventoryData;
use App\Models\AmazonSoldPrice;
use App\Models\DailySales;
use App\Models\FbaInventoryUsa;
use App\Models\InboundShipmentDetailsSp;
use App\Models\PriceUpdateQueue;
use App\Models\PriceUpdateReason;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductNote;
use App\Models\ProductDiscontinue;
use App\Models\ProductForecast;
use App\Models\WeeklySales;
use App\Models\ProductRanking;
use App\Models\ProductWhInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserAssignedAsin;


class SellingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(SellingEnum::SellingSku);
        try {
            $query             = Product::query()->whereHas('listings');
            $user              = auth()->user();
            $unrestrictedRoles = ['md', 'manager', 'developer', 'administrator'];
            $targetUserId      = $request->input('select');
            $reportingUsers    = User::where('reporting_to', $user->id)
                ->pluck('name', 'id')->toArray();

            // Specific user selected (not "all")
            if ($targetUserId && $targetUserId !== 'all') {
                $allowed = $user->hasAnyRole($unrestrictedRoles)
                    || (string)$targetUserId === (string)$user->id
                    || array_key_exists($targetUserId, $reportingUsers);

                if (!$allowed) {
                    return redirect()->back()->with('error', 'Unauthorized to view this user\'s products.');
                }

                $assignedAsins = UserAssignedAsin::where('user_id', $targetUserId)
                    ->pluck('asin')->filter()->unique()->values()->all();

                if (!empty($assignedAsins)) {
                    $query->whereHas('asins', function ($asinQuery) use ($assignedAsins) {
                        $asinQuery->whereIn('asin1', $assignedAsins);
                    });
                } else {
                    $query->whereRaw('1=0');
                }
            } else {
                //"All User" or no selection
                if ($user->hasAnyRole($unrestrictedRoles)) {
                    // Unrestricted no ASIN filter
                } else {
                    $userIds = array_merge([(int)$user->id], array_map('intval', array_keys($reportingUsers)));

                    $assignedAsins = UserAssignedAsin::whereIn('user_id', $userIds)
                        ->pluck('asin')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    if (!empty($assignedAsins)) {
                        $query->whereHas('asins', function ($asinQuery) use ($assignedAsins) {
                            $asinQuery->whereIn('asin1', $assignedAsins);
                        });
                    } else {
                        $query->whereRaw('1=0');
                    }
                }
            }

            //Search filter
            if ($request->filled('search')) {
                $like = '%' . $request->search . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('sku', 'like', $like)
                        ->orWhere('short_title', 'like', $like)
                        ->orWhere('fnsku', 'like', $like)
                        ->orWhereHas('asins', function ($asinQuery) use ($like) {
                            $asinQuery->where('asin1', 'like', $like);
                        })
                        ->orWhereHas('asins.categorisation', function ($catQuery) use ($like) {
                            $catQuery->where('child_short_name', 'like', $like);
                        });
                });
            }
            $products = $query
                ->with([
                    'listings' => function ($q) {
                        $q->orderBy('id')->limit(1);
                    },
                    'listings.additionalDetail',
                    'listings.pricing',
                    'listings.containerInfo',
                    'listings.productNotes',
                    'listings.productRanking',
                    'listings.discontinueInfo',
                    'asins.categorisation'
                ])
                ->paginate($request->get('per_page', 15));


            return view('pages.admin.selling.index', compact('products', 'reportingUsers', 'targetUserId'));
        } catch (\Throwable $e) {
            Log::error('Error fetching selling items: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Something went wrong while fetching selling items.');
        }
    }

    public function sellingItems($uuid)
    {
        $this->authorize(SellingEnum::SellingDashboard);
        try {
            $listing = ProductListing::with([
                'product',
                'additionalDetail',
                'pricing',
                'containerInfo',
                'productNotes',
                'productRanking',
                'discontinueInfo'
            ])
                ->where('uuid', $uuid)
                ->firstOrFail();

            $sku = $listing->product->sku;
            $marketplaceId = config('marketplaces.marketplace_ids')[$listing->country] ?? null;
            $productId = $listing->products_id;

            $amazonSoldPrice = AmazonSoldPrice::where('seller_sku', $sku)
                ->where('marketplace_id', $marketplaceId ?? '')
                ->get();

            $perPage = 10;

            $afnInventoryData = AfnInventoryData::where('seller_sku', $sku)
                ->paginate($perPage, ['*'], 'afn_page')->fragment('afn');

            $fbaInventoryData = FbaInventoryUsa::withTrashed()
                ->whereRaw('LOWER(sku) = ?', [strtolower($sku)])
                ->paginate($perPage, ['*'], 'fba_page');

            $inboundDetailsData = InboundShipmentDetailsSp::with('shipment')
                ->where('sku', $sku)
                ->paginate($perPage, ['*'], 'inbound_page');

            $inboundDetailsData = InboundShipmentDetailsSp::with('shipment')
                ->where('sku', $sku)
                ->paginate($perPage, ['*'], 'inbound_page');

            $whInventoryData = ProductWhInventory::with('warehouse')
                ->where('product_id', $listing->products_id)
                ->paginate($perPage, ['*'], 'wh_page');

            $afdWhAvailable = $whInventoryData->filter(fn($inv) => $inv->warehouse_id == 3)
                ->sum('available_quantity');

            $tacticalWhAvailable = $whInventoryData->filter(fn($inv) => $inv->warehouse_id == 2)
                ->sum('available_quantity');

            // Landed Cost Calculation
            $landedCost = $amazonSoldPrice->first(fn($price) => !empty($price->landed_price))?->landed_price
                ?? (
                    isset($listing->pricing?->item_price, $listing->pricing?->postage, $listing->pricing?->duty)
                    ? landed_cost(
                        $listing->pricing->item_price,
                        $listing->pricing->postage,
                        $listing->pricing->duty
                    )
                    : null
                );

            // Base Price
            $basePrice = null;
            if (isset($landedCost, $listing->pricing?->fba_fee, $listing->country)) {
                $basePrice = calculateBasePrice(
                    $landedCost,
                    $listing->pricing->fba_fee,
                    $listing->country
                );
            } else {
                $basePrice = (float)($amazonSoldPrice->first()?->regular_price ?? 0);
            }

            // Profit
            $profit = isset($listing->pricing?->fba_fee, $landedCost, $basePrice)
                ? Profit_made_USA($basePrice, $landedCost, $listing->pricing->fba_fee)
                : null;

            // Countries list for tabs
            $countries = ProductListing::where('products_id', $listing->products_id)
                ->select('country', 'uuid')
                ->get();

            // Reports
            $weeklyReportData = $this->weeklyReport($sku, $uuid);
            $dailyReportData  = $this->dailyReport($sku);
            $campaignReport = $this->getCampaignReport($sku);

            $weeklyReport = [
                'summary'         => $weeklyReportData['summary'],
                'weeks'           => $weeklyReportData['weeks'],
                'sp'              => $weeklyReportData['spSummary'],
                'sb'              => $weeklyReportData['sbSummary'],
                'campaignMetrics' => $weeklyReportData['campaignMetrics'],
            ];

            $dailyReport = [
                'summary'  => $dailyReportData['daily'],
                'days'     => $dailyReportData['days'],
                'dayNames' => $dailyReportData['dayNames'],
            ];

            // Price update reasons
            $reasons = PriceUpdateReason::all();
            $from = request()->query('from', 'products');

            $startMonth = now(config('timezone.market'))->startOfMonth();

            // Build 12 months: current → +11
            $forecastMonths = collect(range(0, 11))
                ->map(fn($i) => $startMonth->copy()->addMonths($i)->format('Y-m'));

            // Fetch only required forecasts
            $rawForecasts = ProductForecast::where('product_id', $listing->products_id)
                ->whereIn(
                    'forecast_month',
                    $forecastMonths->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->startOfMonth())
                )
                ->get();

            // Map forecasts by "Y-m"
            $forecastMap = $rawForecasts->mapWithKeys(function ($item) {
                return [
                    Carbon::parse($item->forecast_month)->format('Y-m') => (int) $item->forecast_units
                ];
            });

            // Split months
            $leftMonths  = $forecastMonths->take(6)->values();
            $rightMonths = $forecastMonths->slice(6, 6)->values();

            // Build rows for Blade (NO Carbon there)
            $forecastRows = collect();

            for ($i = 0; $i < 6; $i++) {
                $forecastRows->push([
                    'left' => [
                        'month' => Carbon::createFromFormat('Y-m', $leftMonths[$i])->format('M-y'),
                        'units' => $forecastMap[$leftMonths[$i]] ?? 0,
                    ],
                    'right' => [
                        'month' => Carbon::createFromFormat('Y-m', $rightMonths[$i])->format('M-y'),
                        'units' => $forecastMap[$rightMonths[$i]] ?? 0,
                    ],
                ]);
            }

            // Range label
            $forecastRangeLabel = sprintf(
                '%s – %s',
                Carbon::createFromFormat('Y-m', $forecastMonths->first())->format('M Y'),
                Carbon::createFromFormat('Y-m', $forecastMonths->last())->format('M Y')
            );

            return view('pages.admin.selling.sellingItems', compact(
                'weeklyReport',
                'dailyReport',
                'listing',
                'countries',
                'landedCost',
                'profit',
                'amazonSoldPrice',
                'afnInventoryData',
                'fbaInventoryData',
                'inboundDetailsData',
                'reasons',
                'from',
                'sku',
                'productId',
                'forecastRows',
                'forecastRangeLabel',
                'whInventoryData',
                'campaignReport',
                'afdWhAvailable',
                'tacticalWhAvailable'

            ));
        } catch (\Throwable $e) {
            Log::error('Error in SellingController@index', [
                'uuid' => $uuid,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'An error occurred while loading the product details.');
        }
    }

    public function updateProfit(Request $request)
    {
        try {
            $sellingPrice = $request->input('selling_price');
            $uuid = $request->input('uuid');

            $listing = ProductListing::with('pricing')->where('uuid', $uuid)->firstOrFail();

            if (!isset($listing->pricing)) {
                return response()->json(['success' => false, 'message' => 'Pricing info missing.']);
            }

            $landedCost = landed_cost(
                $listing->pricing->item_price,
                $listing->pricing->postage,
                $listing->pricing->duty
            );

            $profit = Profit_made_USA(
                $sellingPrice,
                $landedCost,
                $listing->pricing->fba_fee
            );

            return response()->json(['success' => true, 'profit' => $profit]);
        } catch (\Throwable $e) {
            Log::error('Error updating profit: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'An error occurred.']);
        }
    }

    public function discontinueProduct(Request $request, $uuid)
    {
        $this->authorize(SellingEnum::SellingDiscontinue);
        try {
            $validated = $request->validate([
                'reason_of_dis' => 'required|string|max:1000',
                'country' => 'required|string|max:100',
            ]);

            $listing = ProductListing::where('uuid', $uuid)->firstOrFail();

            ProductDiscontinue::create([
                'products_id'     => $listing->products_id,
                'country'         => $validated['country'],
                'reason_of_dis'   => $validated['reason_of_dis'],
                'discontinued_at' => now(),
            ]);

            $listing->update(['disc_status' => 1]);

            return redirect()->back()->with('success', 'Product successfully discontinued.');
        } catch (\Throwable $e) {
            Log::error('Error discontinuing product: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'An error occurred while discontinuing the product.');
        }
    }

    public function weeklyReport($sku): array
    {
        $marketTz     = config('timezone.market');
        $today        = Carbon::today($marketTz);
        $lastFullWeek = $today->copy()->subWeek()->isoWeek();
        $year         = $today->year;

        $weeks = range($lastFullWeek - 5, $lastFullWeek);

        // Week labels: W1 = oldest, W6 = most recent (you can reverse if you want)
        $weekLabels = [];
        $reversed = array_reverse($weeks);
        foreach ($reversed as $i => $week) {
            $weekLabels['W' . ($i + 1)] = $week;
        }

        $columns = array_reverse(array_keys($weekLabels));
        $columns[] = 'Sales';

        $marketplaceMap = [
            'USA' => ['Amazon.com'],
            'CA'  => ['Amazon.ca'],
            'MX'  => ['Amazon.com.mx'],
        ];

        // Initialize summary
        $summary = [];
        foreach ($marketplaceMap as $region => $_) {
            $summary[$region] = array_fill_keys($columns, 0);
        }

        // Weekly sales (by sku)
        $weeklySales = WeeklySales::where('sku', $sku)
            ->select('marketplace_id', 'week_number', 'total_units')
            ->whereIn('week_number', $weeks)
            ->get();

        foreach ($weeklySales as $record) {
            foreach ($marketplaceMap as $region => $ids) {
                if (in_array($record->marketplace_id, $ids)) {
                    foreach ($weekLabels as $label => $weekNum) {
                        if ((int)$record->week_number === $weekNum) {
                            $summary[$region][$label] += $record->total_units;
                            break;
                        }
                    }
                }
            }
        }

        // Daily sales for current week (by sku)
        $dailySales = DailySales::where('sku', $sku)
            ->select('marketplace_id', 'total_units')
            ->whereBetween('sale_date', [
                $today->copy()->startOfWeek(),
                $today->copy()->subDay(),
            ])
            ->get();

        foreach ($dailySales as $record) {
            foreach ($marketplaceMap as $region => $ids) {
                if (in_array($record->marketplace_id, $ids)) {
                    $summary[$region]['Sales'] += $record->total_units;
                }
            }
        }

        $campaignData = getCampaignDataWeekly($sku, 'sku', $weeks, $year, $marketplaceMap, $marketTz);

        return [
            'summary'         => $summary,
            'weeks'           => $columns,
            'spSummary'       => $campaignData['spSummary'],
            'sbSummary'       => $campaignData['sbSummary'],
            'campaignMetrics' => $campaignData['campaignMetrics'],
        ];
    }

    public function dailyReport($sku): array
    {
        $today = Carbon::today();

        $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $todayIndex = $today->dayOfWeekIso - 1;

        $rotatedDays = array_merge(
            array_slice($weekDays, $todayIndex + 1),
            array_slice($weekDays, 0, $todayIndex + 1)
        );

        $dayLabels = [];
        $dateMap = [];

        foreach ($rotatedDays as $i => $dayName) {
            $date = $today->copy()->subDays(6 - $i);
            $dayLabels['D' . ($i + 1)] = $dayName;
            $dateMap[$date->toDateString()] = 'D' . ($i + 1);
        }

        $days = array_keys($dayLabels);

        $marketplaceMap = [
            'USA' => ['Amazon.com'],
            'CA'  => ['Amazon.ca'],
            'MX'  => ['Amazon.com.mx'],
        ];

        $dailySummary = [];

        foreach (array_keys($marketplaceMap) as $region) {
            $dailySummary[$region] = array_fill_keys($days, 0);
        }

        $salesRecords = DailySales::where('sku', $sku)
            ->select('marketplace_id', 'sale_date', 'total_units')
            ->whereBetween('sale_date', [
                $today->copy()->subDays(6),
                $today
            ])
            ->get();

        foreach ($salesRecords as $record) {
            foreach ($marketplaceMap as $region => $ids) {
                if (in_array($record->marketplace_id, $ids)) {
                    $dayKey = $dateMap[Carbon::parse($record->sale_date)->toDateString()] ?? null;

                    if ($dayKey && isset($dailySummary[$region][$dayKey])) {
                        $dailySummary[$region][$dayKey] += $record->total_units;
                    }
                }
            }
        }

        return [
            'daily'    => $dailySummary,
            'days'     => $days,
            'dayNames' => $dayLabels,
        ];
    }

    public function getCampaignReport($sku): array
    {
        try {

            return getCampaignReportDataDaily($sku, 'sku');
        } catch (\Throwable $e) {
            Log::error('Error in SellingController@getCampaignReport', [
                'sku'     => $sku,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'sp' => [],
                'sb' => [],
                'campaignMetrics' => [],
                'days' => [],
                'dayNames' => [],
            ];
        }
    }

    public function setAmazonPrice(Request $request)
    {
        $this->authorize(SellingEnum::SellingSetPrice);
        try {
            $validated = $request->validate([
                'sku'                    => 'nullable|string|max:255',
                'product_id'             => 'required|integer|exists:products,id',
                'country'                => 'nullable|string|max:10',
                'currency'               => 'nullable|string|max:10',
                'new_price'              => 'required|numeric|min:0',
                'old_price'              => 'nullable|numeric|min:0',
                'reference'              => 'nullable|string|max:255',
                'price_update_reason_id' => 'nullable|integer|exists:price_update_reasons,id',
            ]);

            $now = now();

            PriceUpdateQueue::create([
                'sku'                    => $validated['sku'] ?? null,
                'product_id'             => $validated['product_id'],
                'country'                => $validated['country'] ?? null,
                'currency'               => $validated['currency'] ?? 'USD',
                'new_price'              => $validated['new_price'],
                'old_price'              => $validated['old_price'] ?? null,
                'status'                 => 'submitted',
                'pi_user_id'             => Auth::user()->id,
                'reference'              => $validated['reference'] ?? null,
                'price_update_reason_id' => $validated['price_update_reason_id'] ?? null,
                'added_date'             => $now,
            ]);

            return redirect()->back()->with('success', 'Amazon price successfully submitted to queue.');
        } catch (\Throwable $e) {
            Log::error('Error in setAmazonPrice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'An error occurred while submitting the Amazon price. Please try again.');
        }
    }

    private function extractFieldNames($listing)
    {
        $relatedModels = array_merge(
            ['product', 'additionalDetail', 'pricing', 'containerInfo'],
            ['listingLog']
        );

        $fieldNames = collect((new ProductListing)->getFillable());

        foreach ($relatedModels as $relation) {
            $relatedInstance = $listing->$relation()->getRelated();
            $fieldNames = $fieldNames->merge($relatedInstance->getFillable());
        }

        $excludeFields = ['uuid', 'products_id', 'user_id', 'product_id'];

        $filtered = $fieldNames
            ->unique()
            ->reject(fn($field) => in_array($field, $excludeFields))
            ->values();


        return $filtered;
    }
}
