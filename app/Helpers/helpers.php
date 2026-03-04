<?php

use App\Models\AmzAdsProductPerformanceReport;
use App\Models\AmzAdsCampaignPerformanceReport;
use App\Models\AmzAdsCampaignSBPerformanceReport;
use App\Models\AmzAdsProductPerformanceReportSd;
use App\Models\AmzAdsSbPurchasedProductReport;
use App\Models\DailySales;
use App\Models\Product;
use App\Models\ProductAsins;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SourcingContainer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

if (!function_exists('calculate_progress_status')) {
    /**
     * Calculate the progress status of a product listing.
     *
     * @param  object  $productListing  Product listing model instance (with related additionalDetail loaded)
     * @return int
     */
    function calculate_progress_status($productListing)
    {
        try {
            $titleFilled = !empty($productListing->title_amazon);

            $bulletPoints = [
                $productListing->bullet_point_1,
                $productListing->bullet_point_2,
                $productListing->bullet_point_3,
                $productListing->bullet_point_4,
                $productListing->bullet_point_5,
            ];
            $bulletFilledCount = collect($bulletPoints)->filter()->count();

            $images = [
                $productListing->additionalDetail->image1 ?? null,
                $productListing->additionalDetail->image2 ?? null,
                $productListing->additionalDetail->image3 ?? null,
                $productListing->additionalDetail->image4 ?? null,
                $productListing->additionalDetail->image5 ?? null,
                $productListing->additionalDetail->image6 ?? null,
            ];
            $imageFilledCount = collect($images)->filter()->count();

            if ($titleFilled && $bulletFilledCount >= 3 && $imageFilledCount >= 3) {
                return 3; // Completed
            } elseif ($titleFilled || $bulletFilledCount > 0 || $imageFilledCount > 0) {
                return 2; // In Progress
            } else {
                return 1; // New Product
            }
        } catch (\Throwable $e) {
            Log::error('Error calculating progress status: ' . $e->getMessage(), [
                'productListingId' => $productListing->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}


if (!function_exists('calculate_postage')) {
    function calculate_postage(float $length, float $width, float $height, int $qtyPerCarton): float
    {
        if ($length <= 0 || $width <= 0 || $height <= 0 || $qtyPerCarton <= 0) {
            return 0.0;
        }

        $rate = SourcingContainer::PRICE_CALCULATE_CARTON_RATE;
        $cbm  = ($length * $width * $height) / 1_000_000;
        return round(($cbm * $rate) / $qtyPerCarton, 2);
    }
}

if (!function_exists('calculate_duty')) {
    /**
     * Calculate the duty based on item cost and HS Code percentage.
     *
     * @param  float  $itemCost       The cost of the item.
     * @param  float  $hsCodePercent  The duty percentage (from HS Code).
     */

    function calculate_duty(float $itemCost, float $hsCodePercentage): float
    {
        return round($itemCost * ($hsCodePercentage / 100), 2);
    }
}

if (!function_exists('landed_cost')) {
    /**
     * Calculate the Landed Cost Formula 
     * Landed Cost USA =  Item Price + Shipping + Duty , (duty as 0 now)
     * @param  float  $item Price Shipping.
     */

    function landed_cost(float $itemPrice, float $shipping, ?float $duty = null): float
    {
        // Only add duty if it's not null
        return round($itemPrice + $shipping + ($duty ?? 0), 2);
    }
}

function hasCartonDetails($container): bool
{
    return $container->carton_length !== null &&
        $container->carton_width  !== null &&
        $container->carton_height !== null &&
        $container->carton_qty    !== null;
}

function hasPricingDetails($container): bool
{
    return $container->unit_price !== null && $container->shipping_cost !== null;
}
function hasPricingDetailsProduct($container): bool
{
    return $container->item_price !== null && $container->postage !== null;
}
function calculatePostageIfApplicable($container)
{
    if (
        !$container ||
        $container->ctn_size_length_cm === null ||
        $container->ctn_size_width_cm === null ||
        $container->ctn_size_height_cm === null ||
        $container->quantity_per_carton === null
    ) {
        return null;
    }

    return calculate_postage(
        (float) $container->ctn_size_length_cm,
        (float) $container->ctn_size_width_cm,
        (float) $container->ctn_size_height_cm,
        (float) $container->quantity_per_carton
    );
}


function calculateDutyIfApplicable($pricing, $container)
{
    if (
        $pricing &&
        $container &&
        $pricing->item_price !== null &&
        $container->hs_code_percentage !== null
    ) {
        return calculate_duty(
            (float) $pricing->item_price,
            (float) $container->hs_code_percentage
        );
    }
    return null;
}

function calculateBasePrice($landedCost, $fbaFee, $country)
{
    if (is_null($landedCost) || is_null($fbaFee) || is_null($country)) {
        return null; // avoid calculation if any key input is missing
    }

    // Country-based multipliers and fees
    $pricingRules = [
        'US' => ['multiplier' => 1.30, 'addon' => 0.50],
        'CA' => ['multiplier' => 1.30, 'addon' => 0.50],
        'UK' => ['multiplier' => 1.60, 'addon' => 1.50],
        'EU' => ['multiplier' => 1.60, 'addon' => 1.50],
        'FR' => ['multiplier' => 1.60, 'addon' => 1.50],
        'DE' => ['multiplier' => 1.60, 'addon' => 1.50],
        'ES' => ['multiplier' => 1.60, 'addon' => 1.50],
    ];

    if (!array_key_exists($country, $pricingRules)) {
        return null; // or throw exception if unknown country
    }
    // Log::warning($country);
    $rule = $pricingRules[$country];

    $base = (($landedCost * 1.10) + $fbaFee + $landedCost) * $rule['multiplier'] + $rule['addon'];

    return round($base, 2);
}


if (!function_exists('delete_old_files')) {
    /**
     * Delete files older than X minutes from a given directory on a specified disk.
     *
     * @param string $directory Relative directory (e.g., 'temp')
     * @param int $minutes Files older than this (in minutes) will be deleted
     * @param string $disk Storage disk name (default: 'public')
     * @return void
     */
    function delete_old_files(string $directory, int $minutes = 60, string $disk = 'public'): void
    {
        if (!Storage::disk($disk)->exists($directory)) {
            return;
        }

        $files = Storage::disk($disk)->files($directory);
        $expiryTime = now()->subMinutes($minutes)->timestamp;

        foreach ($files as $file) {
            if (Storage::disk($disk)->lastModified($file) < $expiryTime) {
                Storage::disk($disk)->delete($file);
            }
        }
    }

    function Profit_made_USA(float $selling_price, float $landed_cost, ?float $fba_fee): float
    {
        // Only add duty if it's not null
        return round(($selling_price - ($selling_price * 18 / 100) - $landed_cost - $fba_fee), 2);
    }
}

function getUserColor(string $name): string
{
    $bgClasses = ['bg-primary', 'bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info'];

    $hash = hexdec(substr(md5($name), 0, 6)); // Convert first 6 characters of md5 hash to decimal
    $index = $hash % count($bgClasses);

    return $bgClasses[$index];
}

if (!function_exists('calculateACOS')) {
    /**
     * Calculate ACOS (Advertising Cost of Sales)
     *
     * @param float|int $adSpend
     * @param float|int $adSales
     * @return float
     */
    function calculateACOS($adSpend, $adSales)
    {
        if (empty($adSales) || $adSales == 0) {
            return 0; // Avoid division by zero
        }

        return ($adSpend / $adSales) * 100;
    }
}

function calculateACOSExcel($adSpend, $adSales)
{
    if (empty($adSales) || $adSales == 0) {
        return 0;
    }

    return $adSpend / $adSales; // NO *100
}

if (!function_exists('calculateTACOS')) {
    /**
     * Calculate TACoS (Total Advertising Cost of Sales)
     *
     * @param float|int $adSpend
     * @param float|int $totalSales
     * @return float
     */
    function calculateTACOS($adSpend, $totalSales)
    {
        if (empty($totalSales) || $totalSales == 0) {
            return 0; // Avoid division by zero
        }

        return ($adSpend / $totalSales) * 100;
    }
}



if (!function_exists('getCampaignReportDataDaily')) {
    function getCampaignReportDataDaily(string $identifier, string $type = 'asin'): array
    {
        $marketTz   = config('timezone.market');
        $today      = Carbon::today($marketTz);
        $startDate  = $today->copy()->subDays(6)->toDateString();
        $endDate    = $today->toDateString();

        // Generate rotated days
        $weekDays   = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $todayIndex = $today->dayOfWeekIso - 1;
        $rotated    = array_merge(array_slice($weekDays, $todayIndex + 1), array_slice($weekDays, 0, $todayIndex + 1));

        $dayLabels = $dateMap = [];
        foreach ($rotated as $i => $dayName) {
            $date = $today->copy()->subDays(6 - $i)->toDateString();
            $dayLabels['D' . ($i + 1)] = $dayName;
            $dateMap[$date] = 'D' . ($i + 1);
        }
        $days = array_keys($dayLabels);

        // Campaigns for identifier
        $campaignIds = AmzAdsProductPerformanceReport::where($type, $identifier)
            ->distinct()
            ->pluck('campaign_id')
            ->filter()
            ->toArray();

        // === SB Campaign IDs ===
        if ($type === 'sku') {
            // Get the product_id from uuid
            $productId = Product::where('sku', $identifier)->value('id');
            if ($productId) {
                // Get asin1 from product_asins table for that product_id
                $asin = ProductAsins::where('product_id', $productId)->value('asin1');

                if ($asin) {
                    $sbCampaignIds = AmzAdsSbPurchasedProductReport::where('asin', $asin)
                        ->distinct()
                        ->pluck('campaign_id')
                        ->filter()
                        ->toArray();
                } else {
                    $sbCampaignIds = [];
                }
            } else {
                $sbCampaignIds = [];
            }
        } else {
            $sbCampaignIds = AmzAdsSbPurchasedProductReport::where($type, $identifier)
                ->distinct()
                ->pluck('campaign_id')
                ->filter()
                ->toArray();
        }


        // 3. Run query normally

        $sdCampaignIds = AmzAdsProductPerformanceReportSd::where($type, $identifier)
            ->distinct()
            ->pluck('campaign_id')
            ->filter()
            ->toArray();

        if (empty($campaignIds) && empty($sbCampaignIds) && empty($sdCampaignIds)) {
            return [
                'sp' => [],
                'sb' => [],
                'sd' => [],
                'campaignMetrics' => [],
                'days' => $days,
                'dayNames' => $dayLabels
            ];
        }

        // Total sales for TACoS calc
        $totalSales = DailySales::where($type, $identifier)->sum('total_units');

        // === Aggregated SP data ===
        $spRaw = AmzAdsCampaignPerformanceReport::whereIn('campaign_id', $campaignIds)
            ->whereBetween('c_date', [$startDate, $endDate])
            ->selectRaw('country, DATE(c_date) as date, SUM(sales7d) as sales, SUM(cost) as cost')
            ->groupBy('country', 'c_date')
            ->cursor();

        // === Aggregated SB data ===
        $sbRaw = AmzAdsCampaignSBPerformanceReport::whereIn('campaign_id', $sbCampaignIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('country, DATE(date) as date, SUM(sales) as sales, SUM(cost) as cost')
            ->groupBy('country', 'date')
            ->cursor();

        $sdRaw = AmzAdsProductPerformanceReportSd::whereIn('campaign_id', $sdCampaignIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('country, DATE(date) as date, SUM(sales) as sales, SUM(cost) as cost')
            ->groupBy('country', 'date')
            ->cursor();

        // Initialize
        $marketplaceMap = ['US', 'CA'];
        $spSummary = $sbSummary = $campaignMetrics = [];

        foreach ($marketplaceMap as $region) {
            foreach ($days as $d) {
                $spSummary[$region][$d] = ['sales7d' => 0, 'cost' => 0, 'spTotal' => 0, 'acos' => 0, 'tacos' => 0];
                $sbSummary[$region][$d] = ['sales' => 0, 'cost' => 0, 'sbTotal' => 0, 'acos' => 0, 'tacos' => 0];
                $sdSummary[$region][$d] = ['sales'   => 0, 'cost' => 0, 'sdTotal' => 0, 'acos' => 0, 'tacos' => 0];
                $campaignMetrics[$region][$d] = [
                    'sp_acos' => 0,
                    'sp_tacos' => 0,
                    'sb_acos' => 0,
                    'sb_tacos' => 0,
                    'sd_acos' => 0,
                    'sd_tacos' => 0,
                    'total_acos' => 0,
                    'total_tacos' => 0
                ];
            }
        }

        // Fill SP data
        foreach ($spRaw as $rec) {
            if (!isset($dateMap[$rec->date])) continue;
            $dayKey = $dateMap[$rec->date];
            $spSummary[$rec->country][$dayKey]['sales7d'] = (float)$rec->sales;
            $spSummary[$rec->country][$dayKey]['cost']    = (float)$rec->cost;
            // $spSummary[$rec->country][$dayKey]['spTotal'] = $rec->sales + $rec->cost;
            // $spSummary[$rec->country][$dayKey]['spTotal'] = $rec->sales;
            $spSummary[$rec->country][$dayKey]['spTotal'] = $rec->cost;
        }

        // Fill SB data
        foreach ($sbRaw as $rec) {
            if (!isset($dateMap[$rec->date])) continue;
            $dayKey = $dateMap[$rec->date];
            $sbSummary[$rec->country][$dayKey]['sales'] = (float)$rec->sales;
            $sbSummary[$rec->country][$dayKey]['cost']    = (float)$rec->cost;
            // $sbSummary[$rec->country][$dayKey]['sbTotal'] = $rec->sales + $rec->cost;
            // $sbSummary[$rec->country][$dayKey]['sbTotal'] = $rec->sales;
            $sbSummary[$rec->country][$dayKey]['sbTotal'] = $rec->cost;
        }

        foreach ($sdRaw as $rec) {
            if (!isset($dateMap[$rec->date])) continue;
            $dayKey = $dateMap[$rec->date];
            $sdSummary[$rec->country][$dayKey]['sales'] = (float)$rec->sales;
            $sdSummary[$rec->country][$dayKey]['cost']  = (float)$rec->cost;
            // $sdSummary[$rec->country][$dayKey]['sdTotal'] = $rec->sales;
            $sdSummary[$rec->country][$dayKey]['sdTotal'] = $rec->cost;
        }

        // Compute metrics
        foreach ($marketplaceMap as $region) {
            foreach ($days as $dayKey) {
                $sp = $spSummary[$region][$dayKey];
                $sb = $sbSummary[$region][$dayKey];
                $sd = $sdSummary[$region][$dayKey];
                $combinedSales = $sp['sales7d'] + $sb['sales'] + $sd['sales'];
                $combinedCost  = $sp['cost'] + $sb['cost'] + $sd['cost'];

                $spSummary[$region][$dayKey]['acos']  = $sp['sales7d'] ? round($sp['cost'] / $sp['sales7d'] * 100, 2) : 0;
                $spSummary[$region][$dayKey]['tacos'] = $totalSales ? round($sp['cost'] / $totalSales * 100, 2) : 0;

                $sbSummary[$region][$dayKey]['acos']  = $sb['sales'] ? round($sb['cost'] / $sb['sales'] * 100, 2) : 0;
                $sbSummary[$region][$dayKey]['tacos'] = $totalSales ? round($sb['cost'] / $totalSales * 100, 2) : 0;

                $sdSummary[$region][$dayKey]['acos']  = $sd['sales'] ? round($sd['cost'] / $sd['sales'] * 100, 2) : 0;
                $sdSummary[$region][$dayKey]['tacos'] = $totalSales ? round($sd['cost'] / $totalSales * 100, 2) : 0;

                $campaignMetrics[$region][$dayKey] = [
                    'sp_acos' => $spSummary[$region][$dayKey]['acos'],
                    'sp_tacos' => $spSummary[$region][$dayKey]['tacos'],
                    'sb_acos' => $sbSummary[$region][$dayKey]['acos'],
                    'sb_tacos' => $sbSummary[$region][$dayKey]['tacos'],
                    'total_acos' => $combinedSales ? round($combinedCost / $combinedSales * 100, 2) : 0,
                    'total_tacos' => $totalSales ? round($combinedCost / $totalSales * 100, 2) : 0,
                ];
            }
        }

        return [
            'sp' => $spSummary,
            'sb' => $sbSummary,
            'sd' => $sdSummary,
            'campaignMetrics' => $campaignMetrics,
            'days' => $days,
            'dayNames' => $dayLabels,
        ];
    }
}


if (!function_exists('getCampaignDataWeekly')) {
    function getCampaignDataWeekly(string $identifier, string $type, array $weeks, int $year, array $marketplaceMap, string $marketTz)
    {
        $countryMap = ['US' => 'USA', 'CA' => 'CA', 'MX' => 'MX'];

        $campaignIds = AmzAdsProductPerformanceReport::where($type, $identifier)
            ->distinct()
            ->pluck('campaign_id')
            ->filter()
            ->toArray();


        // $sbCampaignIds = AmzAdsSbPurchasedProductReport::where($type, $identifier)
        //     ->distinct()
        //     ->pluck('campaign_id')
        //     ->filter()
        //     ->toArray();

        // === SB Campaign IDs ===
        if ($type === 'sku') {
            // Get the product_id from uuid
            $productId = Product::where('sku', $identifier)->value('id');

            if ($productId) {
                // Get asin1 from product_asins table for that product_id
                $asin = ProductAsins::where('product_id', $productId)->value('asin1');

                if ($asin) {
                    $sbCampaignIds = AmzAdsSbPurchasedProductReport::where('asin', $asin)
                        ->distinct()
                        ->pluck('campaign_id')
                        ->filter()
                        ->toArray();
                } else {
                    $sbCampaignIds = [];
                }
            } else {
                $sbCampaignIds = [];
            }
        } else {
            $sbCampaignIds = AmzAdsSbPurchasedProductReport::where($type, $identifier)
                ->distinct()
                ->pluck('campaign_id')
                ->filter()
                ->toArray();
        }


        $sdCampaignIds = AmzAdsProductPerformanceReportSd::where($type, $identifier)
            ->distinct()
            ->pluck('campaign_id')
            ->filter()
            ->toArray();

        if (empty($campaignIds) && empty($sbCampaignIds) && empty($sdCampaignIds)) {
            return [
                'spSummary' => [],
                'sbSummary' => [],
                'sdSummary' => [],
                'campaignMetrics' => []
            ];
        }

        // Week labels: W1, W2, W3...
        $weekMap = array_combine($weeks, array_map(fn($i) => 'W' . ($i + 1), range(0, count($weeks) - 1)));

        // Init summaries
        $spSummary = $sbSummary = [];
        foreach ($marketplaceMap as $region => $_) {
            foreach ($weekMap as $label) {
                $spSummary[$region][$label] = ['sales7d' => 0, 'cost' => 0, 'spTotal' => 0, 'acos' => 0, 'tacos' => 0];
                $sbSummary[$region][$label] = ['sales' => 0, 'cost' => 0, 'sbTotal' => 0, 'acos' => 0, 'tacos' => 0];
                $sdSummary[$region][$label] = ['sales' => 0, 'cost' => 0, 'sdTotal' => 0, 'acos' => 0, 'tacos' => 0];
            }
        }

        // === Aggregated SP weekly data ===
        $spRaw = AmzAdsCampaignPerformanceReport::whereIn('campaign_id', $campaignIds)
            ->whereYear('c_date', $year)
            ->whereIn(DB::raw('WEEK(c_date, 1)'), $weeks) // MySQL handles weeks
            ->selectRaw('country, WEEK(c_date, 1) as week, SUM(sales7d) as sales, SUM(cost) as cost')
            ->groupBy('country', 'week')
            ->cursor();

        foreach ($spRaw as $rec) {
            $region    = $countryMap[$rec->country] ?? null;
            $weekLabel = $weekMap[(int)$rec->week] ?? null;
            if (!$region || !$weekLabel) continue;

            $spSummary[$region][$weekLabel]['sales7d'] = (float)$rec->sales;
            $spSummary[$region][$weekLabel]['cost']    = (float)$rec->cost;
            // $spSummary[$region][$weekLabel]['spTotal'] = $rec->sales + $rec->cost;
            // $spSummary[$region][$weekLabel]['spTotal'] = $rec->sales; 
            $spSummary[$region][$weekLabel]['spTotal'] = $rec->cost; 
        }

        // === Aggregated SB weekly data ===
        $sbRaw = AmzAdsCampaignSBPerformanceReport::whereIn('campaign_id', $sbCampaignIds)
            ->whereYear('date', $year)
            ->whereIn(DB::raw('WEEK(date, 1)'), $weeks)
            ->selectRaw('country, WEEK(date, 1) as week, SUM(sales) as sales, SUM(cost) as cost')
            ->groupBy('country', 'week')
            ->cursor();

        foreach ($sbRaw as $rec) {
            $region    = $countryMap[$rec->country] ?? null;
            $weekLabel = $weekMap[(int)$rec->week] ?? null;
            if (!$region || !$weekLabel) continue;

            $sbSummary[$region][$weekLabel]['sales'] = (float)$rec->sales;
            $sbSummary[$region][$weekLabel]['cost']    = (float)$rec->cost;
            // $sbSummary[$region][$weekLabel]['sbTotal'] = $rec->sales + $rec->cost;
            // $sbSummary[$region][$weekLabel]['sbTotal'] = $rec->sales;
            $sbSummary[$region][$weekLabel]['sbTotal'] = $rec->cost;
        }

        $sdRaw = AmzAdsProductPerformanceReportSd::whereIn('campaign_id', $sdCampaignIds)
            ->whereYear('date', $year)
            ->whereIn(DB::raw('WEEK(date, 1)'), $weeks)
            ->selectRaw('country, WEEK(date, 1) as week, SUM(sales) as sales, SUM(cost) as cost')
            ->groupBy('country', 'week')
            ->cursor();

        foreach ($sdRaw as $rec) {
            $region    = $countryMap[$rec->country] ?? null;
            $weekLabel = $weekMap[(int)$rec->week] ?? null;
            if (!$region || !$weekLabel) continue;

            $sdSummary[$region][$weekLabel]['sales'] = (float)$rec->sales;
            $sdSummary[$region][$weekLabel]['cost']    = (float)$rec->cost;
            // $sdSummary[$region][$weekLabel]['sbTotal'] = $rec->sales + $rec->cost;
            // $sdSummary[$region][$weekLabel]['sdTotal'] = $rec->sales;
            $sdSummary[$region][$weekLabel]['sdTotal'] = $rec->cost;
        }

        // Total sales for TACoS
        $totalSales = DailySales::where($type, $identifier)->sum('total_units');

        // Compute metrics
        $campaignMetrics = [];
        foreach ($marketplaceMap as $region => $_) {
            foreach ($weekMap as $label) {
                $sp = $spSummary[$region][$label];
                $sb = $sbSummary[$region][$label];
                $sd = $sdSummary[$region][$label];

                $combinedSales = $sp['sales7d'] + $sb['sales'] + $sd['sales'];
                $combinedCost  = $sp['cost'] + $sb['cost'] + $sd['cost'];

                // SP metrics
                $spSummary[$region][$label]['acos']  = $sp['sales7d'] ? round($sp['cost'] / $sp['sales7d'] * 100, 2) : 0;
                $spSummary[$region][$label]['tacos'] = $totalSales ? round($sp['cost'] / $totalSales * 100, 2) : 0;

                // SB metrics
                $sbSummary[$region][$label]['acos']  = $sb['sales'] ? round($sb['cost'] / $sb['sales'] * 100, 2) : 0;
                $sbSummary[$region][$label]['tacos'] = $totalSales ? round($sb['cost'] / $totalSales * 100, 2) : 0;

                // SD metrics
                $sdSummary[$region][$label]['acos']  = $sd['sales'] ? round($sd['cost'] / $sd['sales'] * 100, 2) : 0;
                $sdSummary[$region][$label]['tacos'] = $totalSales ? round($sd['cost'] / $totalSales * 100, 2) : 0;

                // Combined campaign metrics
                $campaignMetrics[$region][$label] = [
                    'sp_acos'     => $spSummary[$region][$label]['acos'],
                    'sp_tacos'    => $spSummary[$region][$label]['tacos'],
                    'sb_acos'     => $sbSummary[$region][$label]['acos'],
                    'sb_tacos'    => $sbSummary[$region][$label]['tacos'],
                    'sd_acos'     => $sdSummary[$region][$label]['acos'],
                    'sd_tacos'    => $sdSummary[$region][$label]['tacos'],
                    'total_acos'  => $combinedSales ? round($combinedCost / $combinedSales * 100, 2) : 0,
                    'total_tacos' => $totalSales ? round($combinedCost / $totalSales * 100, 2) : 0,
                ];
            }
        }


        return [
            'spSummary'       => $spSummary,
            'sbSummary'       => $sbSummary,
            'sdSummary'       => $sdSummary,
            'campaignMetrics' => $campaignMetrics
        ];
    }
}
