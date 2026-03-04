<?php

namespace App\Services\Ads;

use App\Models\ProductCategorisation;
use Illuminate\Support\Facades\DB;

class AdsOverviewService
{
    public function buildTotalsFromOverview(array $overview): array
    {
        $sumSales  = 0.0;
        $sumSpend  = 0.0;
        $units     = 0;

        // bucket totals (counts + spend + sales + units where relevant)
        $lt30Count  = $lt30Spend  = $lt30Sales  = 0.0;
        $lt30Units  = 0;

        $gte30Count = $gte30Spend = $gte30Sales = 0.0;
        $gte30Units = 0;

        $zeroCount  = $zeroSpend  = $zeroSales  = 0.0;
        $zeroUnits  = 0;

        // spend > 0 & sales = 0 ⇒ only count + spend
        $spendGtZeroSalesCount = 0;
        $spendGtZeroSalesSpend = 0.0;

        // spend = 0 & sales = 0 ⇒ only count
        $spendZeroSalesZeroCount = 0;

        if (! isset($overview['by_type']) || ! is_array($overview['by_type'])) {
            return [
                'sales'   => null,
                'spend'   => null,
                'acos'    => null,
                'units'   => null,
                'buckets' => [
                    'lt_30' => [
                        'count' => null,
                        'spend' => null,
                        'sales' => null,
                        'units' => null,
                    ],
                    'gte_30' => [
                        'count' => null,
                        'spend' => null,
                        'sales' => null,
                        'units' => null,
                    ],
                    'zero' => [
                        'count' => null,
                        'spend' => null,
                        'sales' => null,
                        'units' => null,
                    ],
                    'spend_gt_zero_sales' => [
                        'count' => null,
                        'spend' => null,
                        'sales' => null,
                        'units' => null,
                    ],
                    'spend_zero_sales_zero_cnt' => [
                        'count' => null,
                        'spend' => null,
                        'sales' => null,
                        'units' => null,
                    ],
                ],
            ];
        }

        foreach ($overview['by_type'] as $typeKey => $typeRow) {
            // SP has AUTO / MANUAL nested
            if ($typeKey === 'SP' && is_array($typeRow)) {
                foreach ($typeRow as $subRow) {
                    if (! is_array($subRow) || ! isset($subRow['sales'])) {
                        continue;
                    }

                    $sumSales += (float) ($subRow['sales'] ?? 0);
                    $sumSpend += (float) ($subRow['spend'] ?? 0);
                    $units    += (int)   ($subRow['units'] ?? 0);

                    $buckets = $subRow['buckets'] ?? [];

                    // lt_30
                    $lt30Count  += (int)   ($buckets['lt_30']['count']  ?? 0);
                    $lt30Spend  += (float) ($buckets['lt_30']['spend']  ?? 0);
                    $lt30Sales  += (float) ($buckets['lt_30']['sales']  ?? 0);
                    $lt30Units  += (int)   ($buckets['lt_30']['units']  ?? 0);

                    // gte_30
                    $gte30Count += (int)   ($buckets['gte_30']['count'] ?? 0);
                    $gte30Spend += (float) ($buckets['gte_30']['spend'] ?? 0);
                    $gte30Sales += (float) ($buckets['gte_30']['sales'] ?? 0);
                    $gte30Units += (int)   ($buckets['gte_30']['units'] ?? 0);

                    // zero (spend = 0 OR sales = 0)
                    $zeroCount  += (int)   ($buckets['zero']['count']  ?? 0);
                    $zeroSpend  += (float) ($buckets['zero']['spend']  ?? 0);
                    $zeroSales  += (float) ($buckets['zero']['sales']  ?? 0);
                    $zeroUnits  += (int)   ($buckets['zero']['units']  ?? 0);

                    // spend > 0 & sales = 0 ⇒ only count + spend
                    $spendGtZeroSalesCount += (int)   ($buckets['spend_gt_zero_sales']['count'] ?? 0);
                    $spendGtZeroSalesSpend += (float) ($buckets['spend_gt_zero_sales']['spend'] ?? 0);

                    // spend = 0 & sales = 0 ⇒ only count
                    $spendZeroSalesZeroCount += (int) ($buckets['spend_zero_sales_zero_cnt']['count'] ?? 0);
                }
            } else {
                // SB / SD etc. are flat rows
                if (! is_array($typeRow) || ! isset($typeRow['sales'])) {
                    continue;
                }

                $sumSales += (float) ($typeRow['sales'] ?? 0);
                $sumSpend += (float) ($typeRow['spend'] ?? 0);
                $units    += (int)   ($typeRow['units'] ?? 0);

                $buckets = $typeRow['buckets'] ?? [];

                // lt_30
                $lt30Count  += (int)   ($buckets['lt_30']['count']  ?? 0);
                $lt30Spend  += (float) ($buckets['lt_30']['spend']  ?? 0);
                $lt30Sales  += (float) ($buckets['lt_30']['sales']  ?? 0);
                $lt30Units  += (int)   ($buckets['lt_30']['units']  ?? 0);

                // gte_30
                $gte30Count += (int)   ($buckets['gte_30']['count'] ?? 0);
                $gte30Spend += (float) ($buckets['gte_30']['spend'] ?? 0);
                $gte30Sales += (float) ($buckets['gte_30']['sales'] ?? 0);
                $gte30Units += (int)   ($buckets['gte_30']['units'] ?? 0);

                // zero
                $zeroCount  += (int)   ($buckets['zero']['count']  ?? 0);
                $zeroSpend  += (float) ($buckets['zero']['spend']  ?? 0);
                $zeroSales  += (float) ($buckets['zero']['sales']  ?? 0);
                $zeroUnits  += (int)   ($buckets['zero']['units']  ?? 0);

                // spend > 0 & sales = 0 ⇒ only count + spend
                $spendGtZeroSalesCount += (int)   ($buckets['spend_gt_zero_sales']['count'] ?? 0);
                $spendGtZeroSalesSpend += (float) ($buckets['spend_gt_zero_sales']['spend'] ?? 0);

                // spend = 0 & sales = 0 ⇒ only count
                $spendZeroSalesZeroCount += (int) ($buckets['spend_zero_sales_zero_cnt']['count'] ?? 0);
            }
        }

        $totalAcos = $sumSales > 0 ? round($sumSpend / $sumSales * 100, 2) : null;

        return [
            'sales' => $sumSales,
            'spend' => $sumSpend,
            'acos'  => $totalAcos,
            'units' => $units,
            'buckets' => [
                'lt_30' => [
                    'count' => $lt30Count,
                    'spend' => $lt30Spend,
                    'sales' => $lt30Sales,
                    'units' => $lt30Units,
                ],
                'gte_30' => [
                    'count' => $gte30Count,
                    'spend' => $gte30Spend,
                    'sales' => $gte30Sales,
                    'units' => $gte30Units,
                ],
                'zero' => [
                    'count' => $zeroCount,
                    'spend' => $zeroSpend,
                    'sales' => $zeroSales,
                    'units' => $zeroUnits,
                ],
                'spend_gt_zero_sales' => [
                    'count' => $spendGtZeroSalesCount,
                    'spend' => $spendGtZeroSalesSpend,
                    'sales' => 0.0, // explicitly 0
                    'units' => 0,
                ],
                'spend_zero_sales_zero_cnt' => [
                    'count' => $spendZeroSalesZeroCount,
                    'spend' => 0.0, // always 0 logically
                    'sales' => 0.0,
                    'units' => 0,
                ],
            ],
        ];
    }

    public function getRangeSummaryFromReports(string $startDate, int $windowDays, ?string $asin, ?string $productName = null): array
    {
        // Metrics + counts in single queries
        $spAutoAgg   = $this->aggregateSp($startDate, $windowDays, 'AUTO', $asin, $productName);
        $spManualAgg = $this->aggregateSp($startDate, $windowDays, 'MANUAL', $asin, $productName);
        $sbAgg       = $this->aggregateSb($startDate, $windowDays, $asin, $productName);
        $sdAgg       = $this->aggregateSd($startDate, $windowDays, $asin, $productName);
        $spAuto   = $this->normalizeRangeRow($spAutoAgg);
        $spManual = $this->normalizeRangeRow($spManualAgg);
        $sb       = $this->normalizeRangeRow($sbAgg);
        $sd       = $this->normalizeRangeRow($sdAgg);

        $spAutoCount   = (int) ($spAutoAgg->campaign_count ?? 0);
        $spManualCount = (int) ($spManualAgg->campaign_count ?? 0);
        $sbCount       = (int) ($sbAgg->campaign_count ?? 0);
        $sdCount       = (int) ($sdAgg->campaign_count ?? 0);

        $totalCampaigns = $spAutoCount + $spManualCount + $sbCount + $sdCount;

        return [
            'by_type' => [
                'SP' => [
                    'AUTO'   => $spAuto,
                    'MANUAL' => $spManual,
                ],
                'SB' => $sb,
                'SD' => $sd,
            ],
            'counts' => [
                'SP' => [
                    'AUTO'   => $spAutoCount,
                    'MANUAL' => $spManualCount,
                ],
                'SB'    => $sbCount,
                'SD'    => $sdCount,
                'total' => $totalCampaigns,
            ],
        ];
    }

    /**
     * SP: amz_ads_campaign_performance_report
     * Returns metrics + campaign_count + bucket counts/spend in one shot.
     */
    public function aggregateSp(string $snapshotDate, int $windowDays, string $targetingType, ?string $asin, ?string $productName)
    {
        $map = [
            1  => ['spend' => 'total_spend',       'sales' => 'total_sales',       'units' => 'purchases7d'],
            7  => ['spend' => 'total_spend_7d',    'sales' => 'total_sales_7d',    'units' => 'purchases7d_7d'],
            14 => ['spend' => 'total_spend_14d',   'sales' => 'total_sales_14d',   'units' => 'purchases7d_14d'],
            30 => ['spend' => 'total_spend_30d',   'sales' => 'total_sales_30d',   'units' => 'purchases7d_30d'],
        ];

        if (!isset($map[$windowDays])) {
            throw new \InvalidArgumentException("Invalid windowDays: {$windowDays}");
        }

        $spendCol = $map[$windowDays]['spend'];
        $salesCol = $map[$windowDays]['sales'];
        $unitsCol = $map[$windowDays]['units'];
        // dd($spendCol, $salesCol, $unitsCol);
        $perCampaign = DB::table('campaign_recommendations as cr')
            ->join('amz_campaigns as ac', 'ac.campaign_id', '=', 'cr.campaign_id')
            ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
            ->where('cr.campaign_types', 'SP')
            ->whereIn('cr.country', ['US', 'CA', 'MX'])
            ->whereDate('cr.report_week', $snapshotDate)
            ->where('ac.targeting_type', $targetingType)
            ->when($asin, function ($q) use ($asin, $snapshotDate) {
                $q->whereExists(function ($sub) use ($asin, $snapshotDate) {
                    $sub->from('amz_ads_product_performance_report as p')
                        ->whereColumn('p.campaign_id', 'cr.campaign_id')
                        ->where('p.asin', $asin)
                        ->whereDate('p.c_date', $snapshotDate);
                });
            })
            ->when($productName, function ($q) use ($productName, $snapshotDate) {

                $name = trim($productName);

                // Resolve EXACT child_asin for this product name (and optionally US marketplace)
                $childAsin = ProductCategorisation::query()
                    ->whereNull('deleted_at')
                    // ->where('marketplace', 'US')          
                    ->where('child_short_name', $name)    
                    ->value('child_asin');

                // If not found, return no rows (don't accidentally return all)
                if (!$childAsin) {
                    $q->whereRaw('1=0');
                    return;
                }

                // Apply same logic as ASIN filter: single ASIN match
                $q->whereExists(function ($sub) use ($childAsin, $snapshotDate) {
                    $sub->from('amz_ads_product_performance_report as p')
                        ->whereColumn('p.campaign_id', 'cr.campaign_id')
                        ->where('p.asin', $childAsin)
                        ->whereDate('p.c_date', $snapshotDate);
                });
            })

            ->groupBy('cr.campaign_id')
            ->selectRaw("
            cr.campaign_id,
            SUM(cr.{$spendCol} * cur.conversion_rate_to_usd) AS spend_usd,
            SUM(cr.{$salesCol} * cur.conversion_rate_to_usd) AS sales_usd,
            SUM(cr.{$unitsCol})                              AS units
        ");


        // 2) Bucket aggregation (same as your previous code)
        return DB::query()
            ->fromSub($perCampaign, 'c')
            ->selectRaw("
            COUNT(*) AS campaign_count,

            ROUND(SUM(spend_usd), 2) AS total_spend_usd,
            ROUND(SUM(sales_usd), 2) AS total_sales_usd,
            SUM(units)               AS units,

            -- spend > 0 & sales = 0
            SUM(spend_usd > 0 AND sales_usd <= 0) AS spend_gt_zero_sales_count,
            ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd <= 0 THEN spend_usd ELSE 0 END), 2)
                AS spend_gt_zero_sales_spend,

            -- spend = 0 & sales = 0
            SUM(spend_usd <= 0 AND sales_usd <= 0) AS spend_zero_sales_zero_count,

            -- ACOS undefined (spend = 0 OR sales = 0)
            SUM(spend_usd <= 0 OR sales_usd <= 0) AS acos_zero_count,
            ROUND(SUM(CASE WHEN spend_usd <= 0 OR sales_usd <= 0 THEN spend_usd ELSE 0 END), 2)
                AS acos_zero_spend,

            -- ACOS < 30
            SUM(spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) < 30)
                AS acos_lt_30_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) < 30
                THEN spend_usd ELSE 0 END), 2) AS acos_lt_30_spend,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) < 30
                THEN sales_usd ELSE 0 END), 2) AS acos_lt_30_sales_usd,
            SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) < 30
                THEN units ELSE 0 END) AS acos_lt_30_units,

            -- ACOS >= 30
            SUM(spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) >= 30)
                AS acos_gte_30_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) >= 30
                THEN spend_usd ELSE 0 END), 2) AS acos_gte_30_spend,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) >= 30
                THEN sales_usd ELSE 0 END), 2) AS acos_gte_30_sales_usd,
            SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) >= 30
                THEN units ELSE 0 END) AS acos_gte_30_units
        ")->first();
    }

    /**
     * SB aggregation using campaign_recommendations
     * Snapshot-based + dynamic windowDays
     */
    public function aggregateSb(string $snapshotDate, int $windowDays, ?string $asin, ?string $productName)
    {
        $map = [
            1  => ['spend' => 'total_spend',       'sales' => 'total_sales',       'units' => 'purchases7d'],
            7  => ['spend' => 'total_spend_7d',    'sales' => 'total_sales_7d',    'units' => 'purchases7d_7d'],
            14 => ['spend' => 'total_spend_14d',   'sales' => 'total_sales_14d',   'units' => 'purchases7d_14d'],
            30 => ['spend' => 'total_spend_30d',   'sales' => 'total_sales_30d',   'units' => 'purchases7d_30d'],
        ];

        if (!isset($map[$windowDays])) {
            throw new \InvalidArgumentException("Invalid windowDays: {$windowDays}");
        }

        $spendCol = $map[$windowDays]['spend'];
        $salesCol = $map[$windowDays]['sales'];
        $unitsCol = $map[$windowDays]['units'];
        $perCampaign = DB::table('campaign_recommendations as cr')
            ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
            ->where('cr.campaign_types', 'SB')
            ->whereIn('cr.country', ['US', 'CA', 'MX'])
            ->whereDate('cr.report_week', $snapshotDate)
            ->when($asin, function ($q) use ($asin, $snapshotDate) {
                // SB-specific ASIN mapping
                $q->whereExists(function ($sub) use ($asin, $snapshotDate) {
                    $sub->from('amz_ads_sb_purchased_product_reports as p')
                        ->whereColumn('p.campaign_id', 'cr.campaign_id')
                        ->where('p.asin', $asin)
                        ->whereDate('p.c_date', $snapshotDate);
                });
            })
            ->when($productName, function ($q) use ($productName, $snapshotDate) {
                $q->whereExists(function ($sub) use ($productName, $snapshotDate) {
                    $sub->from('amz_ads_sb_purchased_product_reports as p')
                        ->join('product_categorisations as pc', function ($join) {
                            $join->on('pc.child_asin', '=', 'p.asin')
                                ->whereNull('pc.deleted_at');
                        })
                        ->whereColumn('p.campaign_id', 'cr.campaign_id')
                        ->whereDate('p.c_date', $snapshotDate)
                        ->where('pc.child_short_name', 'like', '%' . trim($productName) . '%');
                });
            })

            ->groupBy('cr.campaign_id')
            ->selectRaw("
            cr.campaign_id,
            SUM(cr.{$spendCol} * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend_usd,
            SUM(cr.{$salesCol} * COALESCE(cur.conversion_rate_to_usd, 1)) AS sales_usd,
            SUM(cr.{$unitsCol})                                           AS units
        ");

        return DB::query()
            ->fromSub($perCampaign, 'c')
            ->selectRaw("
            COUNT(*) AS campaign_count,

            ROUND(SUM(spend_usd), 2) AS total_spend_usd,
            ROUND(SUM(sales_usd), 2) AS total_sales_usd,
            SUM(units)               AS units,

            -- spend > 0 & sales = 0
            SUM(spend_usd > 0 AND sales_usd <= 0) AS spend_gt_zero_sales_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd <= 0 THEN spend_usd ELSE 0
            END), 2) AS spend_gt_zero_sales_spend,

            -- spend = 0 & sales = 0
            SUM(spend_usd <= 0 AND sales_usd <= 0) AS spend_zero_sales_zero_count,

            -- ACOS undefined
            SUM(spend_usd <= 0 OR sales_usd <= 0) AS acos_zero_count,
            ROUND(SUM(CASE
                WHEN spend_usd <= 0 OR sales_usd <= 0 THEN spend_usd ELSE 0
            END), 2) AS acos_zero_spend,

            -- ACOS < 30
            SUM(spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) < 30)
                AS acos_lt_30_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) < 30
                THEN spend_usd ELSE 0
            END), 2) AS acos_lt_30_spend,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) < 30
                THEN sales_usd ELSE 0
            END), 2) AS acos_lt_30_sales_usd,
            SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) < 30
                THEN units ELSE 0
            END) AS acos_lt_30_units,

            -- ACOS >= 30
            SUM(spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) >= 30)
                AS acos_gte_30_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) >= 30
                THEN spend_usd ELSE 0
            END), 2) AS acos_gte_30_spend,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) >= 30
                THEN sales_usd ELSE 0
            END), 2) AS acos_gte_30_sales_usd,
            SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) >= 30
                THEN units ELSE 0
            END) AS acos_gte_30_units")->first();
    }

    /**
     * SD aggregation using campaign_recommendations
     * Snapshot-based + dynamic windowDays
     */
    public function aggregateSd(string $snapshotDate, int $windowDays, ?string $asin, ?string $productName)
    {
        $map = [
            1  => ['spend' => 'total_spend',       'sales' => 'total_sales',       'units' => 'purchases7d'],
            7  => ['spend' => 'total_spend_7d',    'sales' => 'total_sales_7d',    'units' => 'purchases7d_7d'],
            14 => ['spend' => 'total_spend_14d',   'sales' => 'total_sales_14d',   'units' => 'purchases7d_14d'],
            30 => ['spend' => 'total_spend_30d',   'sales' => 'total_sales_30d',   'units' => 'purchases7d_30d'],
        ];

        if (!isset($map[$windowDays])) {
            throw new \InvalidArgumentException("Invalid windowDays: {$windowDays}");
        }

        $spendCol = $map[$windowDays]['spend'];
        $salesCol = $map[$windowDays]['sales'];
        $unitsCol = $map[$windowDays]['units'];
        $perCampaign = DB::table('campaign_recommendations as cr')
            ->join('currencies as cur', 'cur.country_code', '=', 'cr.country')
            ->where('cr.campaign_types', 'SD')
            ->whereIn('cr.country', ['US', 'CA', 'MX'])
            ->whereDate('cr.report_week', $snapshotDate)
            ->when($asin, function ($q) use ($asin, $snapshotDate) {
                // SD-specific ASIN mapping
                $q->whereExists(function ($sub) use ($asin, $snapshotDate) {
                    $sub->from('amz_ads_product_performance_report_sd as p')
                        ->whereColumn('p.campaign_id', 'cr.campaign_id')
                        ->where('p.asin', $asin)
                        ->whereDate('p.date', $snapshotDate);
                });
            })
            ->when($productName, function ($q) use ($productName, $snapshotDate) {
                $q->whereExists(function ($sub) use ($productName, $snapshotDate) {
                    $sub->from('amz_ads_product_performance_report_sd as p')
                        ->join('product_categorisations as pc', function ($join) {
                            $join->on('pc.child_asin', '=', 'p.asin')
                                ->whereNull('pc.deleted_at');
                        })
                        ->whereColumn('p.campaign_id', 'cr.campaign_id')
                        ->whereDate('p.date', $snapshotDate) // SD uses p.date in your code
                        ->where('pc.child_short_name', 'like', '%' . trim($productName) . '%');
                });
            })

            ->groupBy('cr.campaign_id')
            ->selectRaw("
            cr.campaign_id,
            SUM(cr.{$spendCol} * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend_usd,
            SUM(cr.{$salesCol} * COALESCE(cur.conversion_rate_to_usd, 1)) AS sales_usd,
            SUM(cr.{$unitsCol}) AS units
        ");

        return DB::query()
            ->fromSub($perCampaign, 'c')
            ->selectRaw("
            COUNT(*) AS campaign_count,

            ROUND(SUM(spend_usd), 2) AS total_spend_usd,
            ROUND(SUM(sales_usd), 2) AS total_sales_usd,
            SUM(units)               AS units,

            -- spend > 0 & sales = 0
            SUM(spend_usd > 0 AND sales_usd <= 0) AS spend_gt_zero_sales_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd <= 0 THEN spend_usd ELSE 0
            END), 2) AS spend_gt_zero_sales_spend,

            -- spend = 0 & sales = 0
            SUM(spend_usd <= 0 AND sales_usd <= 0) AS spend_zero_sales_zero_count,

            -- ACOS undefined
            SUM(spend_usd <= 0 OR sales_usd <= 0) AS acos_zero_count,
            ROUND(SUM(CASE
                WHEN spend_usd <= 0 OR sales_usd <= 0 THEN spend_usd ELSE 0
            END), 2) AS acos_zero_spend,

            -- ACOS < 30
            SUM(spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) < 30)
                AS acos_lt_30_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) < 30
                THEN spend_usd ELSE 0
            END), 2) AS acos_lt_30_spend,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) < 30
                THEN sales_usd ELSE 0
            END), 2) AS acos_lt_30_sales_usd,
            SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) < 30
                THEN units ELSE 0
            END) AS acos_lt_30_units,

            -- ACOS >= 30
            SUM(spend_usd > 0 AND sales_usd > 0 AND (spend_usd / sales_usd * 100) >= 30)
                AS acos_gte_30_count,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) >= 30
                THEN spend_usd ELSE 0
            END), 2) AS acos_gte_30_spend,
            ROUND(SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) >= 30
                THEN sales_usd ELSE 0
            END), 2) AS acos_gte_30_sales_usd,
            SUM(CASE
                WHEN spend_usd > 0 AND sales_usd > 0
                     AND (spend_usd / sales_usd * 100) >= 30
                THEN units ELSE 0
            END) AS acos_gte_30_units
        ")->first();
    }

    /**
     * Normalize a row from any of the aggregate* methods
     * into a consistent structure (spend/sales/acos/buckets).
     */
    public function normalizeRangeRow($row): array
    {
        if (! $row) {
            return [
                'spend'   => 0.0,
                'sales'   => 0.0,
                'units'   => 0,
                'acos'    => 0.0,
                'buckets' => [
                    'spend_gt_zero_sales'       => ['count' => 0, 'spend' => 0.0, 'sales' => 0.0, 'units' => 0],
                    'spend_zero_sales_zero_cnt' => ['count' => 0, 'spend' => 0.0, 'sales' => 0.0, 'units' => 0],
                    'zero'                      => ['count' => 0, 'spend' => 0.0, 'sales' => 0.0, 'units' => 0],
                    'lt_30'                     => ['count' => 0, 'spend' => 0.0, 'sales' => 0.0, 'units' => 0],
                    'gte_30'                    => ['count' => 0, 'spend' => 0.0, 'sales' => 0.0, 'units' => 0],
                ],
            ];
        }

        $spend = (float) ($row->total_spend_usd ?? 0);
        $sales = (float) ($row->total_sales_usd ?? 0);
        $units = (int)   ($row->units ?? 0);

        return [
            'spend' => $spend,
            'sales' => $sales,
            'units' => $units,
            'acos'  => $sales > 0 ? round(($spend / $sales) * 100, 2) : 0.0,
            'buckets' => [
                'spend_gt_zero_sales' => [
                    'count' => (int)   ($row->spend_gt_zero_sales_count ?? 0),
                    'spend' => (float) ($row->spend_gt_zero_sales_spend ?? 0),
                    'sales' => 0.0,
                    'units' => 0,
                ],
                'spend_zero_sales_zero_cnt' => [
                    'count' => (int)   ($row->spend_zero_sales_zero_count ?? 0),
                    'spend' => 0.0,
                    'sales' => 0.0,
                    'units' => 0,
                ],
                'zero' => [
                    'count' => (int)   ($row->acos_zero_count  ?? 0),
                    'spend' => (float) ($row->acos_zero_spend  ?? 0),
                    'sales' => 0.0,
                    'units' => 0,
                ],
                'lt_30' => [
                    'count' => (int)   ($row->acos_lt_30_count ?? 0),
                    'spend' => (float) ($row->acos_lt_30_spend ?? 0),
                    'sales' => (float) ($row->acos_lt_30_sales_usd ?? 0),
                    'units' => (int)   ($row->acos_lt_30_units ?? 0),
                ],
                'gte_30' => [
                    'count' => (int)   ($row->acos_gte_30_count ?? 0),
                    'spend' => (float) ($row->acos_gte_30_spend ?? 0),
                    'sales' => (float) ($row->acos_gte_30_sales_usd ?? 0),
                    'units' => (int)   ($row->acos_gte_30_units ?? 0),
                ],
            ],
        ];
    }

    public function buildKeywordTotalsFromOverview(array $overview): array
    {
        $sumSales = 0.0;
        $sumSpend = 0.0;
        $units    = 0;

        $buckets = [
            'lt_30' => [
                'count' => 0,
                'spend' => 0.0,
                'sales' => 0.0,
                'units' => 0
            ],
            'gte_30' => [
                'count' => 0,
                'spend' => 0.0,
                'sales' => 0.0,
                'units' => 0
            ],
            'zero' => [
                'count' => 0,
                'spend' => 0.0,
                'sales' => 0.0,
                'units' => 0
            ],
            'spend_gt_zero_sales' => [
                'count' => 0,
                'spend' => 0.0
            ],
            'spend_zero_sales_zero_cnt' => [
                'count' => 0
            ],
        ];

        if (!isset($overview['by_type']) || !is_array($overview['by_type'])) {
            return [];
        }

        foreach ($overview['by_type'] as $typeRow) {

            if (!is_array($typeRow) || !isset($typeRow['spend'])) {
                continue;
            }

            $sumSpend += (float) $typeRow['spend'];
            $sumSales += (float) ($typeRow['sales'] ?? 0);
            $units    += (int)   ($typeRow['units'] ?? 0);

            foreach ($buckets as $bucketKey => $bucketTemplate) {

                if (!isset($typeRow['buckets'][$bucketKey])) {
                    continue;
                }

                foreach ($bucketTemplate as $metric => $_) {
                    $buckets[$bucketKey][$metric] +=
                        (float) ($typeRow['buckets'][$bucketKey][$metric] ?? 0);
                }
            }
        }

        return [
            'sales' => $sumSales,
            'spend' => $sumSpend,
            'acos'  => $sumSales > 0
                ? round(($sumSpend / $sumSales) * 100, 2)
                : null,
            'units' => $units,
            'buckets' => $buckets,
        ];
    }


    public function getKeywordRangeSummaryFromReports(
        string $snapshotDate,
        int $windowDays,
        ?string $asin,
        ?string $productName
    ): array {
        $spAgg = $this->aggregateSpKeywords($snapshotDate, $windowDays, $asin, $productName);
        $sbAgg = $this->aggregateSbKeywords($snapshotDate, $windowDays, $asin, $productName);

        $sp = $this->normalizeKeywordRangeRow($spAgg);
        $sb = $this->normalizeKeywordRangeRow($sbAgg);

        $spCount = (int) ($spAgg->keyword_count ?? 0);
        $sbCount = (int) ($sbAgg->keyword_count ?? 0);

        return [
            'by_type' => [
                'SP' => $sp,
                'SB' => $sb,
            ],
            'counts' => [
                'SP'    => $spCount,
                'SB'    => $sbCount,
                'total' => $spCount + $sbCount,
            ],
        ];
    }

    public function normalizeKeywordRangeRow($row): array
    {
        if (! $row) {
            return [
                'spend'   => 0.0,
                'sales'   => 0.0,
                'units'   => 0,
                'acos'    => 0.0,
                'buckets' => [
                    'spend_gt_zero_sales'       => ['count' => 0, 'spend' => 0, 'sales' => 0, 'units' => 0],
                    'spend_zero_sales_zero_cnt' => ['count' => 0, 'spend' => 0, 'sales' => 0, 'units' => 0],
                    'zero'                      => ['count' => 0, 'spend' => 0, 'sales' => 0, 'units' => 0],
                    'lt_30'                     => ['count' => 0, 'spend' => 0, 'sales' => 0, 'units' => 0],
                    'gte_30'                    => ['count' => 0, 'spend' => 0, 'sales' => 0, 'units' => 0],
                ],
            ];
        }

        $spend = (float) ($row->total_spend_usd ?? 0);
        $sales = (float) ($row->total_sales_usd ?? 0);
        $units = (int)   ($row->units ?? 0);

        return [
            'spend' => $spend,
            'sales' => $sales,
            'units' => $units,
            'acos'  => $sales > 0 ? round(($spend / $sales) * 100, 2) : 0.0,
            'buckets' => [
                'spend_gt_zero_sales' => [
                    'count' => (int)   ($row->spend_gt_zero_sales_count ?? 0),
                    'spend' => (float) ($row->spend_gt_zero_sales_spend ?? 0),
                    'sales' => 0.0,
                    'units' => 0,
                ],
                'spend_zero_sales_zero_cnt' => [
                    'count' => (int) ($row->spend_zero_sales_zero_count ?? 0),
                    'spend' => 0.0,
                    'sales' => 0.0,
                    'units' => 0,
                ],
                'zero' => [
                    'count' => (int)   ($row->acos_zero_count ?? 0),
                    'spend' => (float) ($row->acos_zero_spend ?? 0),
                    'sales' => 0.0,
                    'units' => 0,
                ],
                'lt_30' => [
                    'count' => (int)   ($row->acos_lt_30_count ?? 0),
                    'spend' => (float) ($row->acos_lt_30_spend ?? 0),
                    'sales' => (float) ($row->acos_lt_30_sales_usd ?? 0),
                    'units' => (int)   ($row->acos_lt_30_units ?? 0),
                ],
                'gte_30' => [
                    'count' => (int)   ($row->acos_gte_30_count ?? 0),
                    'spend' => (float) ($row->acos_gte_30_spend ?? 0),
                    'sales' => (float) ($row->acos_gte_30_sales_usd ?? 0),
                    'units' => (int)   ($row->acos_gte_30_units ?? 0),
                ],
            ],
        ];
    }


    public function aggregateSpKeywords(string $snapshotDate, int $windowDays, ?string $asin, ?string $productName)
    {
        $map = [
            1  => ['spend' => 'total_spend',     'sales' => 'total_sales',     'units' => 'purchases1d'],
            7  => ['spend' => 'total_spend_7d',  'sales' => 'total_sales_7d',  'units' => 'purchases1d_7d'],
            14 => ['spend' => 'total_spend_14d', 'sales' => 'total_sales_14d', 'units' => 'purchases1d_14d'],
            30 => ['spend' => 'total_spend_30d', 'sales' => 'total_sales_30d', 'units' => 'purchases7d_30d'],
        ];

        $cols = $map[$windowDays];

        // 1) Per-keyword aggregation
        $perKeyword = DB::table('amz_keyword_recommendations as k')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'k.country')
            ->where('k.campaign_types', 'SP')
            ->whereDate('k.date', $snapshotDate)
            ->when($asin, function ($q) use ($asin) {
                $q->whereExists(function ($sub) use ($asin) {
                    $sub->from('amz_ads_product_performance_report as p')
                        ->whereColumn('p.campaign_id', 'k.campaign_id')
                        ->where('p.asin', $asin);
                });
            })

            ->when($productName, function ($q) use ($productName) {
                $q->whereExists(function ($sub) use ($productName) {
                    $sub->from('amz_ads_product_performance_report as p')
                        ->join('product_categorisations as pc', function ($join) {
                            $join->on('pc.child_asin', '=', 'p.asin')
                                ->whereNull('pc.deleted_at');
                        })
                        ->whereColumn('p.campaign_id', 'k.campaign_id')
                        ->where('pc.child_short_name', 'like', '%' . trim($productName) . '%');
                });
            })
            ->groupBy('k.keyword_id')
            ->selectRaw("
            k.keyword_id,
            SUM(k.{$cols['spend']} * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend_usd,
            SUM(k.{$cols['sales']} * COALESCE(cur.conversion_rate_to_usd, 1)) AS sales_usd,
            SUM(k.{$cols['units']}) AS units
        ");

        // 2) Bucket classification (IDENTICAL to campaigns)
        return DB::query()
            ->fromSub($perKeyword, 'c')
            ->selectRaw("
                COUNT(*) AS keyword_count,
                ROUND(SUM(spend_usd), 2) AS total_spend_usd,
                ROUND(SUM(sales_usd), 2) AS total_sales_usd,
                SUM(units) AS units,

                -- spend > 0 & sales = 0
                SUM(CASE WHEN spend_usd > 0 AND sales_usd = 0 THEN 1 ELSE 0 END) AS spend_gt_zero_sales_count,
                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd = 0 THEN spend_usd ELSE 0 END), 2) AS spend_gt_zero_sales_spend,

                -- spend = 0 & sales = 0
                SUM(CASE WHEN spend_usd = 0 AND sales_usd = 0 THEN 1 ELSE 0 END) AS spend_zero_sales_zero_count,

                -- spend = 0 OR sales = 0
                SUM(CASE WHEN spend_usd = 0 OR sales_usd = 0 THEN 1 ELSE 0 END) AS acos_zero_count,
                ROUND(SUM(CASE WHEN spend_usd = 0 OR sales_usd = 0 THEN spend_usd ELSE 0 END), 2) AS acos_zero_spend,

                -- ACOS < 30
                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN 1 ELSE 0 END) AS acos_lt_30_count,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN spend_usd ELSE 0 END), 2) AS acos_lt_30_spend,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN sales_usd ELSE 0 END), 2) AS acos_lt_30_sales_usd,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN units ELSE 0 END) AS acos_lt_30_units,

                -- ACOS >= 30
                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN 1 ELSE 0 END) AS acos_gte_30_count,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN spend_usd ELSE 0 END), 2) AS acos_gte_30_spend,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN sales_usd ELSE 0 END), 2) AS acos_gte_30_sales_usd,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN units ELSE 0 END) AS acos_gte_30_units
            ")
            ->first();
    }

    public function aggregateSbKeywords(string $snapshotDate, int $windowDays, ?string $asin, ?string $productName)
    {
        $map = [
            1  => ['spend' => 'total_spend',     'sales' => 'total_sales',     'units' => 'purchases1d'],
            7  => ['spend' => 'total_spend_7d',  'sales' => 'total_sales_7d',  'units' => 'purchases1d_7d'],
            14 => ['spend' => 'total_spend_14d', 'sales' => 'total_sales_14d', 'units' => 'purchases1d_14d'],
            30 => ['spend' => 'total_spend_30d', 'sales' => 'total_sales_30d', 'units' => 'purchases7d_30d'],
        ];
        $cols = $map[$windowDays];
        $perKeyword = DB::table('amz_keyword_recommendations as k')
            ->leftJoin('currencies as cur', 'cur.country_code', '=', 'k.country')
            ->where('k.campaign_types', 'SB')
            ->whereDate('k.date', $snapshotDate)
            ->when($asin, function ($q) use ($asin) {
                $q->whereExists(function ($sub) use ($asin) {
                    $sub->from('amz_ads_sb_purchased_product_reports as p')
                        ->whereColumn('p.campaign_id', 'k.campaign_id')
                        ->where('p.asin', $asin);
                });
            })

            ->when($productName, function ($q) use ($productName) {
                $q->whereExists(function ($sub) use ($productName) {
                    $sub->from('amz_ads_sb_purchased_product_reports as p')
                        ->join('product_categorisations as pc', function ($join) {
                            $join->on('pc.child_asin', '=', 'p.asin')
                                ->whereNull('pc.deleted_at');
                        })
                        ->whereColumn('p.campaign_id', 'k.campaign_id')
                        ->where('pc.child_short_name', 'like', '%' . trim($productName) . '%');
                });
            })
            ->groupBy('k.keyword_id')
            ->selectRaw("
            k.keyword_id,
            SUM(k.{$cols['spend']} * COALESCE(cur.conversion_rate_to_usd, 1)) AS spend_usd,
            SUM(k.{$cols['sales']} * COALESCE(cur.conversion_rate_to_usd, 1)) AS sales_usd,
            SUM(k.{$cols['units']}) AS units
        ");

        return DB::query()
            ->fromSub($perKeyword, 'c')
            ->selectRaw("
                COUNT(*) AS keyword_count,
                ROUND(SUM(spend_usd), 2) AS total_spend_usd,
                ROUND(SUM(sales_usd), 2) AS total_sales_usd,
                SUM(units) AS units,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd = 0 THEN 1 ELSE 0 END) AS spend_gt_zero_sales_count,
                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd = 0 THEN spend_usd ELSE 0 END), 2) AS spend_gt_zero_sales_spend,

                SUM(CASE WHEN spend_usd = 0 AND sales_usd = 0 THEN 1 ELSE 0 END) AS spend_zero_sales_zero_count,

                SUM(CASE WHEN spend_usd = 0 OR sales_usd = 0 THEN 1 ELSE 0 END) AS acos_zero_count,
                ROUND(SUM(CASE WHEN spend_usd = 0 OR sales_usd = 0 THEN spend_usd ELSE 0 END), 2) AS acos_zero_spend,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN 1 ELSE 0 END) AS acos_lt_30_count,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN spend_usd ELSE 0 END), 2) AS acos_lt_30_spend,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN sales_usd ELSE 0 END), 2) AS acos_lt_30_sales_usd,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) < 30
                THEN units ELSE 0 END) AS acos_lt_30_units,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN 1 ELSE 0 END) AS acos_gte_30_count,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN spend_usd ELSE 0 END), 2) AS acos_gte_30_spend,

                ROUND(SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN sales_usd ELSE 0 END), 2) AS acos_gte_30_sales_usd,

                SUM(CASE WHEN spend_usd > 0 AND sales_usd > 0
                    AND (spend_usd / NULLIF(sales_usd, 0) * 100) >= 30
                THEN units ELSE 0 END) AS acos_gte_30_units
            ")
            ->first();
    }
}
