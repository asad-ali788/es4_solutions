<?php

namespace App\Services\Ai;

use App\Models\CampaignRecommendations;
use App\Models\DailySales;
use App\Models\ProductCategorisation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiChatBotServices
{
    /**
     * Static allowed marketplaces (not AI-controlled).
     */
    private const ALLOWED_MARKETPLACES = [
        'Amazon.com',
        'Amazon.ca',
        'Amazon.com.mx',
    ];

    private ?string $dailySalesAsinColumn = null;

    /**
     * Get top selling products for a historical date.
     *
     * Rules:
     * - Only yesterday or previous days.
     * - Supports optional marketplace filter.
     * - Returns child_short_name using ProductCategorisation model.
     * - If no product name exists for a child_asin, returns "Product name not available".
     */
    public function topSellingProducts(
        string $date,
        ?string $marketplaceId = null,
        int $limit = 10,
        string $marketTz = 'America/Los_Angeles'
    ): array {
        $limit = max(1, min(50, $limit));

        $reportDate = Carbon::createFromFormat('Y-m-d', $date, $marketTz)->startOfDay();
        $today = Carbon::now($marketTz)->startOfDay();

        // Enforce historical-only rule
        if ($reportDate->greaterThanOrEqualTo($today)) {
            return [];
        }

        $marketplaces = $this->resolveMarketplaces($marketplaceId);

        $cacheKey = implode(':', [
            'top_selling_products',
            'v8',
            $date,
            $marketTz,
            $marketplaceId ?: 'ALL',
            'limit' . $limit,
        ]);

        return Cache::remember($cacheKey, 3600, function () use ($date, $marketplaces, $limit) {
            $asinColumn = $this->resolveDailySalesAsinColumn();

            $sales = DailySales::query()
                ->whereDate('sale_date', $date)
                ->whereIn('marketplace_id', $marketplaces)
                ->whereNotNull($asinColumn)
                ->where($asinColumn, '<>', '')
                ->selectRaw("{$asinColumn} as child_asin")
                ->selectRaw('MIN(sale_date) as sale_date')
                ->selectRaw('SUM(total_units) as total_units')
                ->selectRaw('SUM(total_cost) as total_cost')
                ->selectRaw('SUM(total_revenue) as total_revenue')
                ->groupBy($asinColumn)
                ->orderByDesc('total_units')
                ->limit($limit)
                ->get();

            if ($sales->isEmpty()) {
                return [];
            }

            $asins = $sales->pluck('child_asin')
                ->filter()
                ->map(fn($asin) => trim((string) $asin))
                ->unique()
                ->values();

            $productNames = ProductCategorisation::query()
                ->whereIn('child_asin', $asins)
                ->pluck('child_short_name', 'child_asin')
                ->mapWithKeys(function ($name, $asin) {
                    return [trim((string) $asin) => $name];
                });

            return $sales->map(function ($row) use ($productNames) {
                $asin = trim((string) $row->child_asin);

                $name = $productNames[$asin] ?? null;
                $name = is_string($name) && trim($name) !== '' ? trim($name) : 'Product name not available';

                return [
                    'asin'          => $asin,
                    'product_name'  => $name,
                    'sale_date'     => $row->sale_date,
                    'total_units'   => (int) $row->total_units,
                    'total_cost'    => (float) $row->total_cost,
                    'total_revenue' => (float) $row->total_revenue,
                ];
            })
                ->values()
                ->toArray();
        });
    }

    private function resolveMarketplaces(?string $marketplaceId): array
    {
        if ($marketplaceId === null || trim($marketplaceId) === '') {
            return self::ALLOWED_MARKETPLACES;
        }

        $marketplaceId = trim($marketplaceId);

        if (! in_array($marketplaceId, self::ALLOWED_MARKETPLACES, true)) {
            return self::ALLOWED_MARKETPLACES;
        }

        return [$marketplaceId];
    }

    private function resolveDailySalesAsinColumn(): string
    {
        if ($this->dailySalesAsinColumn !== null) {
            return $this->dailySalesAsinColumn;
        }

        if (Schema::hasColumn('daily_sales', 'child_asin')) {
            $this->dailySalesAsinColumn = 'child_asin';
            return $this->dailySalesAsinColumn;
        }

        $this->dailySalesAsinColumn = 'asin';
        return $this->dailySalesAsinColumn;
    }

    public function campaignPerformance(
        string $date,
        ?string $country = null,
        ?string $campaignType = null,
        string $period = '7d',
        int $limit = 15,
        ?float $minAcos = null,
        ?float $maxAcos = null,
        ?float $minSales = null,
        ?float $maxSales = null,
        ?float $minSpend = null,
        ?float $maxSpend = null
    ): array {
        $limit = max(1, min(50, $limit));

        $period = strtolower(trim($period));
        if (!in_array($period, ['1d', '7d', '14d', '30d'], true)) {
            $period = '7d';
        }

        [$spendCol, $salesCol, $acosCol, $purchasesCol] = match ($period) {
            '1d' => ['total_spend', 'total_sales', 'acos', 'purchases7d'],
            '14d' => ['total_spend_14d', 'total_sales_14d', 'acos_14d', 'purchases7d_14d'],
            '30d' => ['total_spend_30d', 'total_sales_30d', 'acos_30d', 'purchases7d_30d'],
            default => ['total_spend_7d', 'total_sales_7d', 'acos_7d', 'purchases7d_7d'],
        };

        $campaignType = $campaignType ? strtoupper(trim($campaignType)) : null;
        if ($campaignType !== null && !in_array($campaignType, ['SP', 'SB', 'SD'], true)) {
            $campaignType = null;
        }

        $country = $country !== null && trim($country) !== '' ? trim($country) : null;

        $base = CampaignRecommendations::query()
                ->whereDate('report_week', $date)
                ->when($country, fn($q) => $q->where('country', $country))
                ->when($campaignType, fn($q) => $q->where('campaign_types', $campaignType))
                ->whereNotNull('campaign_id')
                ->where('campaign_id', '<>', '')
                // Apply ACOS filters
                ->when($minAcos !== null && $minAcos > 0, fn($q) => $q->whereRaw("COALESCE({$acosCol}, 0) >= ?", [$minAcos]))
                ->when($maxAcos !== null, fn($q) => $q->whereRaw("COALESCE({$acosCol}, 0) <= ? AND COALESCE({$acosCol}, 0) > 0", [$maxAcos]))
                // Apply sales filters
                ->when($minSales !== null && $minSales > 0, fn($q) => $q->whereRaw("COALESCE({$salesCol}, 0) >= ?", [$minSales]))
                ->when($maxSales !== null, fn($q) => $q->whereRaw("COALESCE({$salesCol}, 0) <= ?", [$maxSales]))
                // Apply spend filters
                ->when($minSpend !== null && $minSpend > 0, fn($q) => $q->whereRaw("COALESCE({$spendCol}, 0) >= ?", [$minSpend]))
                ->when($maxSpend !== null, fn($q) => $q->whereRaw("COALESCE({$spendCol}, 0) <= ?", [$maxSpend]));

            $stats = (clone $base)
                ->selectRaw('COUNT(*) as total_campaigns')
                ->selectRaw("COALESCE(SUM({$spendCol}), 0) as total_spend")
                ->selectRaw("COALESCE(SUM({$salesCol}), 0) as total_sales")
                ->selectRaw("COALESCE(AVG({$acosCol}), 0) as avg_acos")
                ->selectRaw("COALESCE(MIN({$acosCol}), 0) as min_acos")
                ->selectRaw("COALESCE(MAX({$acosCol}), 0) as max_acos")
                ->selectRaw("COALESCE(MIN({$salesCol}), 0) as min_sales")
                ->selectRaw("COALESCE(MAX({$salesCol}), 0) as max_sales")
                ->selectRaw("COALESCE(AVG({$salesCol}), 0) as avg_sales")
                ->selectRaw("COALESCE(MIN({$spendCol}), 0) as min_spend")
                ->selectRaw("COALESCE(MAX({$spendCol}), 0) as max_spend")
                ->selectRaw("COALESCE(AVG({$spendCol}), 0) as avg_spend")
                ->first();

            $totalCampaigns = (int) ($stats->total_campaigns ?? 0);

            if ($totalCampaigns === 0) {
                return [
                    'ranges' => [
                        'acos' => ['min' => 0, 'max' => 0, 'avg' => 0, 'buckets' => ['lte_30' => 0, 'between_30_45' => 0, 'gte_45' => 0, 'zero_with_spend' => 0]],
                        'sales' => ['min' => 0, 'max' => 0, 'avg' => 0],
                        'spend' => ['min' => 0, 'max' => 0, 'avg' => 0],
                    ],
                    'bad_campaigns' => [],
                    'campaigns' => [],
                    'summary' => [
                        'total_campaigns' => 0,
                        'total_spend' => 0,
                        'total_sales' => 0,
                        'avg_acos' => 0,
                        'period' => $period,
                        'date' => $date,
                        'country' => $country,
                        'campaign_type' => $campaignType,
                    ],
                ];
            }

            $bucketStats = (clone $base)
                ->selectRaw("SUM(CASE WHEN COALESCE({$acosCol},0) > 0 AND COALESCE({$acosCol},0) <= 30 THEN 1 ELSE 0 END) as lte_30")
                ->selectRaw("SUM(CASE WHEN COALESCE({$acosCol},0) > 30 AND COALESCE({$acosCol},0) < 45 THEN 1 ELSE 0 END) as between_30_45")
                ->selectRaw("SUM(CASE WHEN COALESCE({$acosCol},0) >= 45 THEN 1 ELSE 0 END) as gte_45")
                ->selectRaw("SUM(CASE WHEN COALESCE({$acosCol},0) = 0 AND COALESCE({$spendCol},0) > 0 THEN 1 ELSE 0 END) as zero_with_spend")
                ->first();

            // Only fetch bad campaigns if user hasn't already filtered by ACOS
            $badCampaigns = [];
            if ($minAcos === null && $maxAcos === null) {
                $badCampaigns = (clone $base)
                    ->select([
                        'campaign_id',
                        'campaign_name',
                        'country',
                        'campaign_types',
                    ])
                    ->selectRaw("COALESCE({$spendCol}, 0) as spend")
                    ->selectRaw("COALESCE({$salesCol}, 0) as sales")
                    ->selectRaw("COALESCE({$acosCol}, 0) as acos")
                    ->selectRaw("COALESCE({$purchasesCol}, 0) as purchases")
                    ->whereRaw("COALESCE({$acosCol}, 0) > 30")
                    ->whereRaw("COALESCE({$spendCol}, 0) > 0")
                    ->orderByDesc('acos')
                    ->limit(10)
                    ->get()
                    ->map(fn($r) => [
                        'campaign_id' => (string) $r->campaign_id,
                        'campaign_name' => (string) ($r->campaign_name ?? 'Unknown Campaign'),
                        'country' => (string) ($r->country ?? ''),
                        'campaign_type' => (string) ($r->campaign_types ?? ''),
                        'acos' => (float) $r->acos,
                        'spend' => (float) $r->spend,
                        'sales' => (float) $r->sales,
                        'purchases' => (int) $r->purchases,
                    ])
                    ->values()
                    ->all();
            }

            $campaigns = (clone $base)
                ->select([
                    'campaign_id',
                    'campaign_name',
                    'country',
                    'campaign_types',
                ])
                ->selectRaw("COALESCE({$spendCol}, 0) as spend")
                ->selectRaw("COALESCE({$salesCol}, 0) as sales")
                ->selectRaw("COALESCE({$acosCol}, 0) as acos")
                ->selectRaw("COALESCE({$purchasesCol}, 0) as purchases")
                ->orderByDesc('spend')
                ->limit($limit)
                ->get()
                ->map(fn($r) => [
                    'campaign_id' => (string) $r->campaign_id,
                    'campaign_name' => (string) ($r->campaign_name ?? 'Unknown Campaign'),
                    'country' => (string) ($r->country ?? ''),
                    'campaign_type' => (string) ($r->campaign_types ?? ''),
                    'acos' => (float) $r->acos,
                    'spend' => (float) $r->spend,
                    'sales' => (float) $r->sales,
                    'purchases' => (int) $r->purchases,
                ])
                ->values()
                ->all();

            return [
                'ranges' => [
                    'acos' => [
                        'min' => round((float) ($stats->min_acos ?? 0), 2),
                        'max' => round((float) ($stats->max_acos ?? 0), 2),
                        'avg' => round((float) ($stats->avg_acos ?? 0), 2),
                        'buckets' => [
                            'lte_30' => (int) ($bucketStats->lte_30 ?? 0),
                            'between_30_45' => (int) ($bucketStats->between_30_45 ?? 0),
                            'gte_45' => (int) ($bucketStats->gte_45 ?? 0),
                            'zero_with_spend' => (int) ($bucketStats->zero_with_spend ?? 0),
                        ],
                    ],
                    'sales' => [
                        'min' => round((float) ($stats->min_sales ?? 0), 2),
                        'max' => round((float) ($stats->max_sales ?? 0), 2),
                        'avg' => round((float) ($stats->avg_sales ?? 0), 2),
                    ],
                    'spend' => [
                        'min' => round((float) ($stats->min_spend ?? 0), 2),
                        'max' => round((float) ($stats->max_spend ?? 0), 2),
                        'avg' => round((float) ($stats->avg_spend ?? 0), 2),
                    ],
                ],
                'bad_campaigns' => $badCampaigns,
                'campaigns' => $campaigns,
                'summary' => [
                    'total_campaigns' => $totalCampaigns,
                    'total_spend' => round((float) ($stats->total_spend ?? 0), 2),
                    'total_sales' => round((float) ($stats->total_sales ?? 0), 2),
                    'avg_acos' => round((float) ($stats->avg_acos ?? 0), 2),
                    'period' => $period,
                    'date' => $date,
                    'country' => $country,
                    'campaign_type' => $campaignType,
                ],
            ];
    }

    public function warehouseStockDetails(
        ?string $asin = null,
        ?string $sku = null,
        ?string $warehouseName = null,
        ?string $stockBucket = null,
        int $limit = 50
    ): array {
        $limit = max(1, min(100, $limit));

        // Parse comma-separated SKUs
        $skus = null;
        if ($sku !== null && trim($sku) !== '') {
            $skus = array_map('trim', explode(',', $sku));
            $skus = array_filter($skus, fn($s) => $s !== '');
            $skus = array_unique($skus);
            $skus = !empty($skus) ? array_values($skus) : null;
        }

        // Parse comma-separated ASINs
        $asins = null;
        if ($asin !== null && trim($asin) !== '') {
            $asins = array_map('trim', explode(',', $asin));
            $asins = array_filter($asins, fn($a) => $a !== '');
            $asins = array_unique($asins);
            $asins = !empty($asins) ? array_values($asins) : null;
        }

        $warehouseName = $warehouseName !== null && trim($warehouseName) !== '' ? trim($warehouseName) : null;
        $stockBucket = $stockBucket !== null && trim($stockBucket) !== '' ? strtoupper(trim($stockBucket)) : null;

        $allowedBuckets = ['AFN', 'FBA', 'INBOUND', 'SHIPOUT', 'TACTICAL', 'AWD'];
        if ($stockBucket !== null && !in_array($stockBucket, $allowedBuckets, true)) {
            $stockBucket = null;
        }

        // Skip cache for unique queries - performance trade-off
        // Most queries are unique (different SKU/ASIN combinations)
        return $this->fetchWarehouseStockData($asins, $skus, $warehouseName, $stockBucket, $limit);
    }

    private function fetchWarehouseStockData(
        ?array $asins,
        ?array $skus,
        ?string $warehouseName,
        ?string $stockBucket,
        int $limit
    ): array {
        $warehouseGroups = Cache::remember('warehouse_stock_groups:v1', 1800, function () {
            $warehouseRows = DB::table('warehouses')
                ->whereNull('deleted_at')
                ->get(['id', 'warehouse_name']);

            $shipOutIds = [];
            $tacticalIds = [];
            $awdIds = [];

            foreach ($warehouseRows as $warehouseRow) {
                $name = strtolower((string) ($warehouseRow->warehouse_name ?? ''));
                $warehouseId = (int) ($warehouseRow->id ?? 0);

                if ($warehouseId <= 0) {
                    continue;
                }

                if (str_contains($name, 'shipout')) {
                    $shipOutIds[] = $warehouseId;
                }
                if (str_contains($name, 'tactical')) {
                    $tacticalIds[] = $warehouseId;
                }
                if (str_contains($name, 'awd') || str_contains($name, 'afd')) {
                    $awdIds[] = $warehouseId;
                }
            }

            return [
                'shipout' => $shipOutIds,
                'tactical' => $tacticalIds,
                'awd' => $awdIds,
            ];
        });

        $shipOutIds = (array) ($warehouseGroups['shipout'] ?? []);
        $tacticalIds = (array) ($warehouseGroups['tactical'] ?? []);
        $awdIds = (array) ($warehouseGroups['awd'] ?? []);

        $allMatchedWarehouseIds = [];
        if ($warehouseName) {
            $allMatchedWarehouseIds = DB::table('warehouses')
                ->whereNull('deleted_at')
                ->where('warehouse_name', 'like', '%' . $warehouseName . '%')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->values()
                ->all();
        }

        $shipOutSql = empty($shipOutIds) ? null : implode(',', array_map('intval', $shipOutIds));
        $tacticalSql = empty($tacticalIds) ? null : implode(',', array_map('intval', $tacticalIds));
        $awdSql = empty($awdIds) ? null : implode(',', array_map('intval', $awdIds));

        $whSub = DB::table('product_wh_inventory as pwi')
            ->whereNull('pwi.deleted_at')
            ->select('pwi.product_id')
            ->selectRaw('SUM(COALESCE(pwi.available_quantity, 0)) as wh_total_available')
            ->selectRaw(($shipOutSql ? "SUM(CASE WHEN pwi.warehouse_id IN ({$shipOutSql}) THEN COALESCE(pwi.available_quantity,0) ELSE 0 END)" : '0') . ' as shipout_qty')
            ->selectRaw(($tacticalSql ? "SUM(CASE WHEN pwi.warehouse_id IN ({$tacticalSql}) THEN COALESCE(pwi.available_quantity,0) ELSE 0 END)" : '0') . ' as tactical_qty')
            ->selectRaw(($awdSql ? "SUM(CASE WHEN pwi.warehouse_id IN ({$awdSql}) THEN COALESCE(pwi.available_quantity,0) ELSE 0 END)" : '0') . ' as awd_qty')
            ->groupBy('pwi.product_id');

        if (!empty($allMatchedWarehouseIds)) {
            $whSub->whereIn('pwi.warehouse_id', $allMatchedWarehouseIds);
        }

        $afnSub = DB::table('afn_inventory_data')
            ->whereNull('deleted_at')
            ->select('seller_sku')
            ->selectRaw('SUM(COALESCE(quantity_available,0)) as afn_quantity')
            ->groupBy('seller_sku');

        $fbaSub = DB::table('fba_inventory_usa')
            ->whereNull('deleted_at')
            ->select('sku')
            ->selectRaw('SUM(COALESCE(totalstock,0)) as fba_total_stock')
            ->groupBy('sku');

        $inboundSub = DB::table('inbound_shipment_details_sps as inbound')
            ->leftJoin('inbound_shipment_sps as sps', 'inbound.ship_id', '=', 'sps.shipment_id')
            ->where(function ($q) {
                $q->whereNull('sps.ship_status')
                    ->orWhere('sps.ship_status', '!=', 'CLOSED');
            })
            ->select('inbound.sku')
            ->selectRaw('GREATEST(SUM(COALESCE(inbound.qty_ship,0)) - SUM(COALESCE(inbound.qty_received,0)), 0) as inbound_qty')
            ->groupBy('inbound.sku');

        $query = DB::table('products as p')
            ->leftJoin('product_asins as pa', function ($join) {
                $join->on('pa.product_id', '=', 'p.id')
                    ->whereNull('pa.deleted_at');
            })
            ->leftJoinSub($afnSub, 'afn', fn($join) => $join->on('afn.seller_sku', '=', 'p.sku'))
            ->leftJoinSub($fbaSub, 'fba', fn($join) => $join->on('fba.sku', '=', 'p.sku'))
            ->leftJoinSub($inboundSub, 'inbound', fn($join) => $join->on('inbound.sku', '=', 'p.sku'))
            ->leftJoinSub($whSub, 'wh', fn($join) => $join->on('wh.product_id', '=', 'p.id'))
            ->whereNull('p.deleted_at')
            ->select([
                'p.sku',
                'pa.asin1',
                'pa.asin2',
                'pa.asin3',
            ])
            ->selectRaw('COALESCE(afn.afn_quantity, 0) as afn_quantity')
            ->selectRaw('COALESCE(fba.fba_total_stock, 0) as fba_total_stock')
            ->selectRaw('COALESCE(inbound.inbound_qty, 0) as inbound_qty')
            ->selectRaw('COALESCE(wh.shipout_qty, 0) as shipout_qty')
            ->selectRaw('COALESCE(wh.tactical_qty, 0) as tactical_qty')
            ->selectRaw('COALESCE(wh.awd_qty, 0) as awd_qty')
            ->selectRaw('COALESCE(wh.wh_total_available, 0) as wh_total_available');

        // Handle multiple SKUs
        if ($skus !== null && !empty($skus)) {
            if (count($skus) === 1) {
                // Single SKU - use LIKE for partial matching
                $query->where('p.sku', 'like', '%' . $skus[0] . '%');
            } else {
                // Multiple SKUs - use exact matching with whereIn
                $query->whereIn('p.sku', $skus);
            }
        }

        // Handle multiple ASINs
        if ($asins !== null && !empty($asins)) {
            if (count($asins) === 1) {
                // Single ASIN - use LIKE for partial matching
                $query->where(function ($q) use ($asins) {
                    $q->where('pa.asin1', 'like', '%' . $asins[0] . '%')
                        ->orWhere('pa.asin2', 'like', '%' . $asins[0] . '%')
                        ->orWhere('pa.asin3', 'like', '%' . $asins[0] . '%');
                });
            } else {
                // Multiple ASINs - use exact matching with whereIn
                $query->where(function ($q) use ($asins) {
                    $q->whereIn('pa.asin1', $asins)
                        ->orWhereIn('pa.asin2', $asins)
                        ->orWhereIn('pa.asin3', $asins);
                });
            }
        }

        if ($stockBucket) {
            match ($stockBucket) {
                'AFN' => $query->whereRaw('COALESCE(afn.afn_quantity,0) > 0'),
                'FBA' => $query->whereRaw('COALESCE(fba.fba_total_stock,0) > 0'),
                'INBOUND' => $query->whereRaw('COALESCE(inbound.inbound_qty,0) > 0'),
                'SHIPOUT' => $query->whereRaw('COALESCE(wh.shipout_qty,0) > 0'),
                'TACTICAL' => $query->whereRaw('COALESCE(wh.tactical_qty,0) > 0'),
                'AWD' => $query->whereRaw('COALESCE(wh.awd_qty,0) > 0'),
                default => null,
            };
        }

        $rows = $query
            ->orderByRaw('COALESCE(wh.wh_total_available,0) DESC')
            ->orderBy('p.sku')
            ->limit($limit)
            ->get();

        $items = $rows->map(function ($row) {
            $asins = collect([$row->asin1, $row->asin2, $row->asin3])
                ->filter(fn($value) => is_string($value) && trim($value) !== '')
                ->map(fn($value) => trim((string) $value))
                ->unique()
                ->values()
                ->all();

            $afn = (int) ($row->afn_quantity ?? 0);
            $fba = (int) ($row->fba_total_stock ?? 0);
            $inbound = (int) ($row->inbound_qty ?? 0);
            $shipOut = (int) ($row->shipout_qty ?? 0);
            $tactical = (int) ($row->tactical_qty ?? 0);
            $awd = (int) ($row->awd_qty ?? 0);

            return [
                'sku' => (string) ($row->sku ?? ''),
                'asins' => $asins,
                'primary_asin' => $asins[0] ?? null,
                'afn' => $afn,
                'fba' => $fba,
                'inbound' => $inbound,
                'shipout' => $shipOut,
                'tactical' => $tactical,
                'awd' => $awd,
                'total_stock' => $afn + $fba + $inbound + $shipOut + $tactical + $awd,
                'is_in_stock' => ($afn + $fba + $inbound + $shipOut + $tactical + $awd) > 0,
            ];
        })->values();

        $stockSummary = [
            'matched_rows' => $items->count(),
            'total_afn' => (int) $items->sum('afn'),
            'total_fba' => (int) $items->sum('fba'),
            'total_inbound' => (int) $items->sum('inbound'),
            'total_shipout' => (int) $items->sum('shipout'),
            'total_tactical' => (int) $items->sum('tactical'),
            'total_awd' => (int) $items->sum('awd'),
            'total_stock' => (int) $items->sum('total_stock'),
            'in_stock_rows' => (int) $items->filter(fn($row) => (bool) $row['is_in_stock'])->count(),
            'out_of_stock_rows' => (int) $items->filter(fn($row) => !(bool) $row['is_in_stock'])->count(),
            'has_stock' => (int) $items->sum('total_stock') > 0,
        ];

        return [
            'items' => $items->all(),
            'stock_summary' => $stockSummary,
            'filters' => [
                'asins' => $asins,
                'skus' => $skus,
                'warehouse_name' => $warehouseName,
                'stock_bucket' => $stockBucket,
                'limit' => $limit,
            ],
            'stock_columns' => ['AFN', 'FBA', 'Inbound', 'ShipOut', 'Tactical', 'AWD'],
        ];
    }

    public function campaignDetails(
        ?string $country = null,
        ?string $campaignType = null,
        string $campaignState = 'ENABLED',
        int $limit = 15,
        ?array $campaignIds = null,
        ?string $campaignName = null
    ): array {
        $limit = max(1, min(50, $limit));

        // Normalize campaign state - handle both uppercase and lowercase
        $states = [];
        if (!empty($campaignState)) {
            // Accept ENABLED, PAUSED, enabled, paused
            $normalizedState = strtoupper(trim($campaignState));
            if (in_array($normalizedState, ['ENABLED', 'PAUSED'], true)) {
                $states[] = $normalizedState;
                // Also add lowercase version for SD table
                $states[] = strtolower($normalizedState);
            }
        }

        // Default to both states if none specified
        if (empty($states)) {
            $states = ['ENABLED', 'PAUSED', 'enabled', 'paused'];
        }

        try {
            // Query SP Campaigns (amz_campaigns)
            $spQuery = DB::table('amz_campaigns')
                ->select(
                    'campaign_id',
                    'campaign_name',
                    'campaign_state',
                    'daily_budget',
                    'campaign_type',
                    'targeting_type',
                    'country',
                    DB::raw("'SP' as ads_type"),
                    'created_at'
                )
                ->whereNull('deleted_at');

            // Query SB Campaigns (amz_campaigns_sb)
            $sbQuery = DB::table('amz_campaigns_sb')
                ->select(
                    'campaign_id',
                    'campaign_name',
                    'campaign_state',
                    'daily_budget',
                    'campaign_type',
                    'targeting_type',
                    'country',
                    DB::raw("'SB' as ads_type"),
                    'created_at'
                )
                ->whereNull('deleted_at');

            // Query SD Campaigns (amz_campaigns_sd)
            $sdQuery = DB::table('amz_campaigns_sd')
                ->select(
                    'campaign_id',
                    'campaign_name',
                    'campaign_state',
                    'daily_budget',
                    'campaign_type',
                    'targeting_type',
                    'country',
                    DB::raw("'SD' as ads_type"),
                    'created_at'
                )
                ->whereNull('deleted_at');

            // Apply country filter
            if (!empty($country)) {
                $countryCode = strtoupper(trim($country));
                $spQuery->where('country', $countryCode);
                $sbQuery->where('country', $countryCode);
                $sdQuery->where('country', $countryCode);
            }

            // Apply campaign type filter
            if (!empty($campaignType)) {
                $type = strtoupper(trim($campaignType));
                $spQuery->where('campaign_type', $type);
                $sbQuery->where('campaign_type', $type);
                $sdQuery->where('campaign_type', $type);
            }

            // Apply campaign ID filter - allows querying specific campaigns
            if (!empty($campaignIds) && is_array($campaignIds)) {
                $spQuery->whereIn('campaign_id', $campaignIds);
                $sbQuery->whereIn('campaign_id', $campaignIds);
                $sdQuery->whereIn('campaign_id', $campaignIds);
            }

            // Apply campaign name filter - allows searching by campaign name
            if (!empty($campaignName)) {
                $namePattern = '%' . trim($campaignName) . '%';
                $spQuery->where('campaign_name', 'LIKE', $namePattern);
                $sbQuery->where('campaign_name', 'LIKE', $namePattern);
                $sdQuery->where('campaign_name', 'LIKE', $namePattern);
            }

            // Apply state filter - case-insensitive for all tables
            if (!empty($states)) {
                // For SP and SB tables (campaign_state is uppercase)
                $spQuery->where(function ($q) {
                    $q->where('campaign_state', 'ENABLED')
                        ->orWhere('campaign_state', 'PAUSED');
                });
                $sbQuery->where(function ($q) {
                    $q->where('campaign_state', 'ENABLED')
                        ->orWhere('campaign_state', 'PAUSED');
                });
                
                // For SD table (campaign_state can be lowercase, use case-insensitive LOWER)
                $sdQuery->where(function ($q) {
                    $q->whereRaw("LOWER(campaign_state) = 'enabled'")
                        ->orWhereRaw("LOWER(campaign_state) = 'paused'");
                });
            }

            // Union all queries and order by creation date, then limit
            $campaigns = $spQuery
                ->unionAll($sbQuery)
                ->unionAll($sdQuery)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            // Build response
            $campaignList = $campaigns->map(function ($campaign) {
                return [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'state' => $campaign->campaign_state,
                    'daily_budget' => $campaign->daily_budget ? (float) $campaign->daily_budget : null,
                    'type' => $campaign->campaign_type,
                    'targeting_type' => $campaign->targeting_type,
                    'country' => $campaign->country,
                    'ads_type' => $campaign->ads_type,
                ];
            })->toArray();

            // Create summary
            $summary = [
                'total_campaigns' => count($campaignList),
                'by_type' => $campaigns->groupBy('ads_type')
                    ->mapWithKeys(function ($group, $key) {
                        return [$key => $group->count()];
                    })->toArray(),
                'total_budget' => (float) $campaigns->sum('daily_budget'),
            ];

            return [
                'campaigns' => $campaignList,
                'summary' => $summary,
            ];
        } catch (Throwable $e) {
            \Log::error('Campaign Details Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return [
                'campaigns' => [],
                'summary' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get products (ASIN/SKU) that belong to a campaign.
     * Queries all three performance report tables across SP, SB, and SD types.
     */
    public function campaignProducts(
        ?string $asin = null,
        ?string $sku = null,
        ?string $campaignId = null,
        ?string $country = null,
        int $limit = 25
    ): array {
        $limit = max(1, min(100, $limit));

        try {
            // SP Products (amz_ads_product_performance_report)
            $spQuery = DB::table('amz_ads_product_performance_report as p')
                ->leftJoin('amz_campaigns as c', 'p.campaign_id', '=', 'c.campaign_id')
                ->select(
                    'p.asin',
                    'p.sku',
                    'c.campaign_name',
                    'p.campaign_id',
                    'p.cost',
                    'p.clicks',
                    'p.impressions',
                    'p.purchases1d',
                    'p.purchases7d',
                    'p.purchases30d',
                    'p.sales1d',
                    'p.sales7d',
                    'p.sales30d',
                    DB::raw("NULL as sales14d"),
                    DB::raw("NULL as purchases14d"),
                    DB::raw("'SP' as ads_type"),
                    'p.country'
                )
                ->whereNull('p.deleted_at');

            // SB Products (amz_ads_sb_purchased_product_reports)
            $sbQuery = DB::table('amz_ads_sb_purchased_product_reports as p')
                ->select(
                    'p.asin',
                    DB::raw("NULL as sku"),
                    'p.campaign_name',
                    'p.campaign_id',
                    DB::raw("NULL as cost"),
                    DB::raw("NULL as clicks"),
                    DB::raw("NULL as impressions"),
                    DB::raw("NULL as purchases1d"),
                    DB::raw("NULL as purchases7d"),
                    DB::raw("NULL as purchases30d"),
                    DB::raw("NULL as sales1d"),
                    DB::raw("NULL as sales7d"),
                    DB::raw("NULL as sales30d"),
                    'p.sales14d',
                    'p.orders14d as purchases14d',
                    DB::raw("'SB' as ads_type"),
                    'p.country'
                )
                ->whereNull('p.deleted_at');

            // SD Products (amz_ads_product_performance_report_sd)
            $sdQuery = DB::table('amz_ads_product_performance_report_sd as p')
                ->leftJoin('amz_campaigns_sd as c', 'p.campaign_id', '=', 'c.campaign_id')
                ->select(
                    'p.asin',
                    'p.sku',
                    'c.campaign_name',
                    'p.campaign_id',
                    'p.cost',
                    'p.clicks',
                    'p.impressions',
                    'p.purchases as purchases1d',
                    DB::raw("NULL as purchases7d"),
                    DB::raw("NULL as purchases30d"),
                    'p.sales as sales1d',
                    DB::raw("NULL as sales7d"),
                    DB::raw("NULL as sales30d"),
                    DB::raw("NULL as sales14d"),
                    DB::raw("NULL as purchases14d"),
                    DB::raw("'SD' as ads_type"),
                    'p.country'
                )
                ->whereNull('p.deleted_at');

            // Apply ASIN filter
            if (!empty($asin)) {
                $asinTrimmed = trim($asin);
                $spQuery->where('p.asin', '=', $asinTrimmed);
                $sbQuery->where('p.asin', '=', $asinTrimmed);
                $sdQuery->where('p.asin', '=', $asinTrimmed);
            }

            // Apply SKU filter
            if (!empty($sku)) {
                $skuTrimmed = trim($sku);
                $spQuery->where('p.sku', '=', $skuTrimmed);
                // SB doesn't have SKU, skip for SB
                $sdQuery->where('p.sku', '=', $skuTrimmed);
            }

            // Apply campaign_id filter
            if (!empty($campaignId)) {
                $campaignIdInt = (int) $campaignId;
                $spQuery->where('p.campaign_id', $campaignIdInt);
                $sbQuery->where('p.campaign_id', $campaignIdInt);
                $sdQuery->where('p.campaign_id', $campaignIdInt);
            }

            // Apply country filter
            if (!empty($country)) {
                $countryCode = strtoupper(trim($country));
                $spQuery->where('p.country', $countryCode);
                $sbQuery->where('p.country', $countryCode);
                $sdQuery->where('p.country', $countryCode);
            }

            // Union all queries, distinct to avoid duplicates, and limit
            $products = $spQuery
                ->unionAll($sbQuery)
                ->unionAll($sdQuery)
                ->distinct()
                ->limit($limit)
                ->get();

            // Build response - include all performance metrics with different time periods
            $productList = $products->map(function ($product) {
                $data = [
                    'asin' => $product->asin,
                    'sku' => $product->sku,
                    'campaign_name' => $product->campaign_name,
                    'ads_type' => $product->ads_type,
                    'country' => $product->country,
                    'cost' => $product->cost !== null ? (float) $product->cost : null,
                    'clicks' => $product->clicks !== null ? (int) $product->clicks : null,
                    'impressions' => $product->impressions !== null ? (int) $product->impressions : null,
                ];

                // 1-day metrics
                if ($product->sales1d !== null || $product->purchases1d !== null) {
                    $data['sales_1d'] = $product->sales1d !== null ? (float) $product->sales1d : null;
                    $data['purchases_1d'] = $product->purchases1d !== null ? (int) $product->purchases1d : null;
                }

                // 7-day metrics (SP only)
                if ($product->sales7d !== null || $product->purchases7d !== null) {
                    $data['sales_7d'] = $product->sales7d !== null ? (float) $product->sales7d : null;
                    $data['purchases_7d'] = $product->purchases7d !== null ? (int) $product->purchases7d : null;
                }

                // 14-day metrics (SB only)
                if ($product->sales14d !== null || $product->purchases14d !== null) {
                    $data['sales_14d'] = $product->sales14d !== null ? (float) $product->sales14d : null;
                    $data['purchases_14d'] = $product->purchases14d !== null ? (int) $product->purchases14d : null;
                }

                // 30-day metrics (SP only)
                if ($product->sales30d !== null || $product->purchases30d !== null) {
                    $data['sales_30d'] = $product->sales30d !== null ? (float) $product->sales30d : null;
                    $data['purchases_30d'] = $product->purchases30d !== null ? (int) $product->purchases30d : null;
                }

                // Calculate ACOS if we have cost and sales data
                $totalSales = $product->sales30d ?? $product->sales14d ?? $product->sales7d ?? $product->sales1d;
                if ($product->cost !== null && $totalSales !== null && $totalSales > 0) {
                    $data['acos'] = round(($product->cost / $totalSales) * 100, 2);
                }

                return $data;
            })->toArray();

            // Create summary
            $summary = [
                'total_products' => count($productList),
                'by_type' => collect($products)->groupBy('ads_type')
                    ->mapWithKeys(function ($group, $key) {
                        return [$key => $group->count()];
                    })->toArray(),
            ];

            return [
                'products' => $productList,
                'summary' => $summary,
            ];
        } catch (Throwable $e) {
            \Log::error('Campaign Products Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return [
                'products' => [],
                'summary' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get keyword performance metrics for analysis.
     * Optimized for millions of records using indexed columns and early filtering.
     * 
     * Note: The 7d, 14d, and 30d metrics are PRE-CALCULATED aggregates in the database.
     * - 1d: Single day metrics for the specified date
     * - 7d: Pre-aggregated 7-day metrics (from that date backwards)
     * - 14d: Pre-aggregated 14-day metrics (from that date backwards)
     * - 30d: Pre-aggregated 30-day metrics (from that date backwards)
     * 
     * The date should be yesterday or earlier (data is historical-only).
     */
    public function keywordPerformance(
        string $date,
        ?string $country = null,
        ?string $campaignType = null,
        string $period = '7d',
        int $limit = 25,
        ?float $minAcos = null,
        ?float $maxAcos = null,
        ?float $minSales = null,
        ?float $maxSales = null,
        ?float $minSpend = null,
        ?float $maxSpend = null,
        ?string $sortBy = 'sales'
    ): array {
        $limit = max(1, min(100, $limit));

        try {
            // Map to pre-calculated column names based on period
            $metricsMap = [
                '1d' => [
                    'sales' => 'total_sales',
                    'spend' => 'total_spend',
                    'acos' => 'acos',
                    'purchases' => 'purchases1d',
                    'clicks' => 'clicks',
                    'impressions' => 'impressions',
                ],
                '7d' => [
                    'sales' => 'total_sales_7d',
                    'spend' => 'total_spend_7d',
                    'acos' => 'acos_7d',
                    'purchases' => 'purchases1d_7d',
                    'clicks' => 'clicks_7d',
                    'impressions' => 'impressions_7d',
                ],
                '14d' => [
                    'sales' => 'total_sales_14d',
                    'spend' => 'total_spend_14d',
                    'acos' => 'acos_14d',
                    'purchases' => 'purchases1d_14d',
                    'clicks' => 'clicks',
                    'impressions' => 'impressions',
                ],
                '30d' => [
                    'sales' => 'total_sales_30d',
                    'spend' => 'total_spend_30d',
                    'acos' => 'acos_30d',
                    'purchases' => 'purchases7d_30d',
                    'clicks' => 'clicks',
                    'impressions' => 'impressions',
                ],
            ];

            $periodValid = in_array($period, ['1d', '7d', '14d', '30d']);
            if (!$periodValid) {
                $period = '7d';
            }

            $metrics = $metricsMap[$period];

            // Build optimized query - filter by indexed columns first
            $query = DB::table('amz_keyword_recommendations')
                ->select(
                    'keyword_id',
                    'keyword',
                    'campaign_id',
                    'country',
                    'campaign_types',
                    'bid',
                    'suggested_bid',
                    'ai_suggested_bid',
                    "{$metrics['sales']} as sales",
                    "{$metrics['spend']} as spend",
                    "{$metrics['acos']} as acos",
                    "{$metrics['purchases']} as purchases",
                    "{$metrics['clicks']} as clicks",
                    "{$metrics['impressions']} as impressions",
                    'recommendation',
                    'ai_recommendation'
                )
                ->whereDate('date', $date)
                ->whereNull('deleted_at');

            // Filter by country (indexed column)
            if (!empty($country)) {
                $query->where('country', strtoupper(trim($country)));
            }

            // Filter by campaign type (indexed column via campaign_types)
            if (!empty($campaignType)) {
                $query->where('campaign_types', strtoupper(trim($campaignType)));
            }

            // Apply ACOS filters
            if ($minAcos !== null && $minAcos > 0) {
                $query->where($metrics['acos'], '>=', $minAcos);
            }
            if ($maxAcos !== null) {
                $query->where($metrics['acos'], '<=', $maxAcos)
                    ->where($metrics['acos'], '>', 0); // Exclude zero ACOS
            }

            // Apply sales filters
            if ($minSales !== null && $minSales > 0) {
                $query->where($metrics['sales'], '>=', $minSales);
            }
            if ($maxSales !== null) {
                $query->where($metrics['sales'], '<=', $maxSales);
            }

            // Apply spend filters
            if ($minSpend !== null && $minSpend > 0) {
                $query->where($metrics['spend'], '>=', $minSpend);
            }
            if ($maxSpend !== null) {
                $query->where($metrics['spend'], '<=', $maxSpend);
            }

            // Determine sort order based on sortBy parameter
            $sortColumn = match($sortBy) {
                'acos' => $metrics['acos'],
                'spend' => $metrics['spend'],
                'purchases' => $metrics['purchases'],
                'clicks' => $metrics['clicks'],
                default => $metrics['sales'],
            };

            $sortDirection = ($sortBy === 'acos') ? 'asc' : 'desc';

            // Get keywords with limit
            $keywords = $query
                ->orderBy($sortColumn, $sortDirection)
                ->limit($limit)
                ->get();

            // Build response
            $keywordList = $keywords->map(function ($kw) {
                return [
                    'keyword' => $kw->keyword,
                    'keyword_id' => $kw->keyword_id,
                    'campaign_id' => $kw->campaign_id,
                    'sales' => $kw->sales ? (float) $kw->sales : 0,
                    'spend' => $kw->spend ? (float) $kw->spend : 0,
                    'acos' => $kw->acos ? (float) $kw->acos : 0,
                    'purchases' => $kw->purchases ?? 0,
                   'clicks' => $kw->clicks ?? 0,
                    'impressions' => $kw->impressions ?? 0,
                    'bid' => $kw->bid ? (float) $kw->bid : 0,
                    'suggested_bid' => $kw->suggested_bid ? (float) $kw->suggested_bid : null,
                    'ai_suggested_bid' => $kw->ai_suggested_bid ? (float) $kw->ai_suggested_bid : null,
                    'country' => $kw->country,
                    'campaign_type' => $kw->campaign_types,
                    'recommendation' => $kw->recommendation,
                    'ai_recommendation' => $kw->ai_recommendation,
                ];
            })->toArray();

            // Calculate summary statistics
            $summary = [
                'total_keywords' => count($keywordList),
                'total_sales' => (float) $keywords->sum('sales'),
                'total_spend' => (float) $keywords->sum('spend'),
                'avg_acos' => $keywords->count() > 0 && $keywords->sum('sales') > 0
                    ? round(($keywords->sum('spend') / $keywords->sum('sales')) * 100, 2)
                    : 0,
                'total_purchases' => (int) $keywords->sum('purchases'),
                'total_clicks' => (int) $keywords->sum('clicks'),
            ];

            return [
                'keywords' => $keywordList,
                'summary' => $summary,
                'period' => $period,
            ];
        } catch (Throwable $e) {
            \Log::error('Keyword Performance Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return [
                'keywords' => [],
                'summary' => [],
                'period' => $period,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get keyword details and related ASINs via campaign_id.
     * Optimized for large datasets with early filtering and strict limits.
     */
    public function keywordDetails(
        string $date,
        ?string $keyword = null,
        ?string $keywordId = null,
        ?string $campaignId = null,
        ?string $asin = null,
        ?string $sku = null,
        ?string $country = null,
        ?string $campaignType = null,
        int $limit = 25,
        int $asinLimit = 50
    ): array {
        $limit = max(1, min(100, $limit));
        $asinLimit = max(1, min(200, $asinLimit));

        try {
            $query = DB::table('amz_keyword_recommendations as k')
                ->select(
                    'k.keyword_id',
                    'k.keyword',
                    'k.campaign_id',
                    'k.country',
                    'k.campaign_types'
                )
                ->whereDate('k.date', $date)
                ->whereNull('k.deleted_at')
                ->whereNotNull('k.campaign_id')
                ->where('k.campaign_id', '<>', '');

            // Exact keyword_id search (highest priority)
            if ($keywordId !== null && trim($keywordId) !== '') {
                $query->where('k.keyword_id', '=', trim($keywordId));
            }

            // Exact keyword text search (not partial - too many false positives)
            if ($keyword !== null && trim($keyword) !== '' && ($keywordId === null || trim($keywordId) === '')) {
                $query->where('k.keyword', '=', trim($keyword));
            }

            // Exact campaign_id search
            if ($campaignId !== null && trim($campaignId) !== '') {
                $query->where('k.campaign_id', '=', (int) trim($campaignId));
            }

            // Country filter
            if ($country !== null && trim($country) !== '') {
                $query->where('k.country', '=', strtoupper(trim($country)));
            }

            // Campaign type filter
            if ($campaignType !== null && trim($campaignType) !== '') {
                $query->where('k.campaign_types', '=', strtoupper(trim($campaignType)));
            }

            $keywords = $query
                ->orderBy('k.keyword')
                ->limit($limit)
                ->get();

            $keywordList = $keywords->map(function ($kw) {
                return [
                    'keyword' => $kw->keyword,
                    'keyword_id' => $kw->keyword_id,
                    'campaign_id' => (int) $kw->campaign_id,
                    'country' => $kw->country,
                    'campaign_type' => $kw->campaign_types,
                ];
            })->values()->all();

            $campaignIds = $keywords
                ->pluck('campaign_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (empty($campaignIds) && $campaignId !== null && trim($campaignId) !== '') {
                $campaignIds = [(int) trim($campaignId)];
            }

            $asins = [];
            if (!empty($campaignIds)) {
                $asinFilter = $asin !== null && trim($asin) !== '' ? strtoupper(trim($asin)) : null;
                $skuFilter = $sku !== null && trim($sku) !== '' ? trim($sku) : null;
                $countryFilter = $country !== null && trim($country) !== '' ? strtoupper(trim($country)) : null;

                $spQuery = DB::table('amz_ads_product_performance_report as p')
                    ->leftJoin('amz_campaigns as c', 'p.campaign_id', '=', 'c.campaign_id')
                    ->select(
                        'p.asin',
                        'p.sku',
                        'p.campaign_id',
                        'c.campaign_name',
                        DB::raw("'SP' as ads_type"),
                        'p.country'
                    )
                    ->whereNull('p.deleted_at')
                    ->whereIn('p.campaign_id', $campaignIds)
                    ->whereNotNull('p.asin')
                    ->where('p.asin', '<>', '');

                $sbQuery = DB::table('amz_ads_sb_purchased_product_reports as p')
                    ->select(
                        'p.asin',
                        DB::raw('NULL as sku'),
                        'p.campaign_id',
                        'p.campaign_name',
                        DB::raw("'SB' as ads_type"),
                        'p.country'
                    )
                    ->whereNull('p.deleted_at')
                    ->whereIn('p.campaign_id', $campaignIds)
                    ->whereNotNull('p.asin')
                    ->where('p.asin', '<>', '');

                $sdQuery = DB::table('amz_ads_product_performance_report_sd as p')
                    ->leftJoin('amz_campaigns_sd as c', 'p.campaign_id', '=', 'c.campaign_id')
                    ->select(
                        'p.asin',
                        'p.sku',
                        'p.campaign_id',
                        'c.campaign_name',
                        DB::raw("'SD' as ads_type"),
                        'p.country'
                    )
                    ->whereNull('p.deleted_at')
                    ->whereIn('p.campaign_id', $campaignIds)
                    ->whereNotNull('p.asin')
                    ->where('p.asin', '<>', '');

                // Apply exact ASIN filter (not partial)
                if ($asinFilter !== null) {
                    $spQuery->where('p.asin', '=', $asinFilter);
                    $sbQuery->where('p.asin', '=', $asinFilter);
                    $sdQuery->where('p.asin', '=', $asinFilter);
                }

                // Apply exact SKU filter
                if ($skuFilter !== null) {
                    $spQuery->where('p.sku', '=', $skuFilter);
                    $sdQuery->where('p.sku', '=', $skuFilter);
                }

                // Apply country filter
                if ($countryFilter !== null) {
                    $spQuery->where('p.country', '=', $countryFilter);
                    $sbQuery->where('p.country', '=', $countryFilter);
                    $sdQuery->where('p.country', '=', $countryFilter);
                }

                $union = $spQuery->unionAll($sbQuery)->unionAll($sdQuery);

                $asins = DB::query()
                    ->fromSub($union, 'u')
                    ->select('asin', 'sku', 'campaign_id', 'campaign_name', 'ads_type', 'country')
                    ->whereNotNull('asin')
                    ->where('asin', '<>', '')
                    ->distinct()
                    ->limit($asinLimit)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'asin' => $row->asin,
                            'sku' => $row->sku,
                            'campaign_id' => (int) $row->campaign_id,
                            'campaign_name' => $row->campaign_name,
                            'ads_type' => $row->ads_type,
                            'country' => $row->country,
                        ];
                    })
                    ->values()
                    ->all();
            }

            return [
                'keywords' => $keywordList,
                'asins' => $asins,
                'summary' => [
                    'total_keywords' => count($keywordList),
                    'total_campaigns' => count($campaignIds),
                    'total_asins' => count($asins),
                ],
            ];
        } catch (Throwable $e) {
            \Log::error('Keyword Details Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'keywords' => [],
                'asins' => [],
                'summary' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

}
