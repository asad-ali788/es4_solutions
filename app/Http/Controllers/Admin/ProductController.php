<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\ProductEnum;
use App\Http\Controllers\Controller;
use App\Jobs\ProductUpdateJob;
use App\Models\AmazonSoldPrice;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductListing;
use App\Models\ProductListingLog;

use App\Models\ProductPricing;
use App\Models\ProductContainerInfo;
use App\Models\ProductAdditionalDetail;
use App\Models\ProductCategorisation;
use App\Models\ProductNote;
use App\Models\UserAssignedAsin;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(ProductEnum::Product);
        try {
            $query             = Product::query();
            $user              = auth()->user();
            $unrestrictedRoles = ['md', 'manager', 'developer', 'administrator'];

            $targetUserId   = $request->input('select');
            $reportingUsers = User::where('reporting_to', $user->id)
                ->pluck('name', 'id')
                ->toArray();

            // === Specific user selected (not "all") ===
            if ($targetUserId && $targetUserId !== 'all') {

                // Allowed if unrestricted OR self OR direct report
                $allowed = $user->hasAnyRole($unrestrictedRoles)
                    || (string)$targetUserId === (string)$user->id
                    || array_key_exists($targetUserId, $reportingUsers);

                if (!$allowed) {
                    return redirect()->back()->with('error', 'Unauthorized to view this user\'s products.');
                }

                // Filter to selected user's ASINs
                $assignedAsins = UserAssignedAsin::where('user_id', $targetUserId)
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
                    $query->whereRaw('1=0'); // No ASINs → no products
                }
            } else {
                // === "All User" or no selection ===
                if ($user->hasAnyRole($unrestrictedRoles)) {
                    // Unrestricted → no filter
                } else {
                    // Restricted → own + direct reports' ASINs
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

            // Search filter
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
            // Status filter
            $status = $request->input('status', 'active');

            if ($status !== 'all') {
                $statusValue = $status === 'active' ? 1 : 0; // assuming 1=active, 0=inactive
                $query->where('status', $statusValue);
            }

            $products = $query
                ->with([
                    'listings' => function ($q) {
                        $q->orderBy('id')->limit(1);
                    },
                    'listings.additionalDetail',
                    'listings.pricing',
                    'listings.containerInfo',
                    'asins',
                    'asins.categorisation',
                ])
                ->paginate($request->get('per_page', 15));
            return view('pages.admin.product.index', compact('products', 'reportingUsers', 'targetUserId'));
        } catch (\Throwable $e) {
            Log::error('Product index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Something went wrong while fetching the products.');
        }
    }


    public function createSku(Request $request)
    {
        $this->authorize(ProductEnum::ProductCreate);

        $request->validate([
            'sku'             => 'required|string|max:100',
            'short_title'     => 'required|string|max:255',
            'listing_to_copy' => 'nullable|string|max:255',
        ]);
        try {

            $countries = config('countries');

            $sku           = $request->input('sku');
            $shortTitle    = $request->input('short_title');
            $listingToCopy = $request->input('listing_to_copy');

            DB::beginTransaction();
            // Step 1: Create one Product record
            $product = Product::create([
                'uuid'        => Str::uuid(),
                'sku'         => $sku,
                'short_title' => $shortTitle,
            ]);

            $now = now();
            $listingsData = [];
            // Step 2: Prepare 6 listings linked to this product
            foreach ($countries as $country) {
                $listingsData[] = [
                    'uuid'         => Str::uuid(),
                    'products_id'  => $product->id,
                    'country'      => $country,
                    'progress_status' => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
            // Step 3: Insert all product listings
            $insertedListings = ProductListing::insert($listingsData);
            // Step 4: Get inserted listings to attach additionalDetail
            $insertedListings = ProductListing::where('products_id', $product->id)->get();

            foreach ($insertedListings as $listing) {
                $listing->additionalDetail()->create([
                    'listing_to_copy' => $listingToCopy,
                ]);
            }
            DB::commit();
            $lastInsertedListing = $insertedListings->first();
            return redirect()
                ->route('admin.products.edit', $lastInsertedListing->uuid)
                ->with('success', 'SKU created successfully for all countries.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred while creating the product: ' . $e->getMessage());
        }
    }

    public function edit($uuid)
    {
        $this->authorize(ProductEnum::ProductUpdate);

        try {
            $productListing = ProductListing::with(['product', 'additionalDetail', 'pricing', 'containerInfo', 'productNotes', 'discontinueInfo'])
                ->where('uuid', $uuid)
                ->firstOrFail();
            $marketplaceId = config('marketplaces.marketplace_ids')[$productListing->country] ?? null;

            $amazonSoldPrice = AmazonSoldPrice::where('seller_sku', $productListing->product->sku)
                ->select('listing_price')
                ->where('marketplace_id', $marketplaceId)
                ->get();

            $otherCountrys = ProductListing::where('products_id', $productListing->products_id)
                ->select('country', 'uuid')
                ->get();

            return view('pages.admin.product.form', [
                'product'         => $productListing,
                'otherCountrys'   => $otherCountrys,
                'amazonSoldPrice' => $amazonSoldPrice
            ]);
        } catch (Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorize(ProductEnum::ProductUpdate);
        $mainFields = [
            'sku',
            'short_title',
            'translator',
            'title_amazon',
            'bullet_point_1',
            'bullet_point_2',
            'bullet_point_3',
            'bullet_point_4',
            'bullet_point_5',
            'description',
            'search_terms',
            'advertising_keywords',
            'country',
            'product_category'
        ];

        $pricingFields = [
            'item_price',
            'postage',
            'base_price',
            'fba_fee',
            'duty',
            'air_ship'
        ];

        $containerFields = [
            'commercial_invoice_title',
            'hs_code',
            'hs_code_percentage',
            'item_weight_kg',
            'carton_weight_kg',
            'quantity_per_carton',
            'carton_cbm',
            'moq',
            'product_material',
            'order_lead_time_weeks',
            'item_size_length_cm',
            'item_size_width_cm',
            'item_size_height_cm',
            'ctn_size_length_cm',
            'ctn_size_width_cm',
            'ctn_size_height_cm'
        ];

        $additionalFields = ['listing_to_copy', 'warnings'];

        $fileFields = [
            'instructions_file',
            'fba_barcode_file',
            'product_label_file',
            'instructions_file_2',
            'listing_research_file'
        ];

        $imageFields = ['image1', 'image2', 'image3', 'image4', 'image5', 'image6'];
        try {
            $productListing = ProductListing::with(['product', 'additionalDetail', 'pricing', 'containerInfo', 'productNotes', 'discontinueInfo'])
                ->findOrFail($id);
            $oldData = collect([
                'main' => $productListing,
                'pricing' => $productListing->pricing,
                'container' => $productListing->containerInfo,
                'additional' => $productListing->additionalDetail,
            ])->map(fn($m) => $m?->getOriginal() ?? [])->toArray();

            // Main Fields
            $mainData = $request->only($mainFields);
            if ($request->hasFile('instructions_file')) {
                $mainData['instructions_file'] = $request->file('instructions_file')->store('products', 'public');
            }
            $productListing->update($mainData);

            $this->touchContentIfChanged(
                $productListing,
                $oldData['main'] ?? [],
                true // false to mark only this listing
            );

            // Related Table Updates
            $productListing->pricing()->updateOrCreate(
                ['product_listings_id' => $productListing->id],
                $request->only($pricingFields)
            );

            $productListing->containerInfo()->updateOrCreate(
                ['product_listings_id' => $productListing->id],
                $request->only($containerFields)
            );

            $additionalDetailData = $request->only($additionalFields);
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $additionalDetailData[$field] = $request->file($field)->store('products', 'public');
                }
            }
            $productListing->additionalDetail()->updateOrCreate(
                ['product_listings_id' => $productListing->id],
                $additionalDetailData
            );

            // Image fields: upload and bulk update
            $imageData = [];
            foreach ($imageFields as $field) {
                if ($request->hasFile($field)) {
                    $imageData[$field] = $request->file($field)->store('products', 'public');
                }
            }

            if (!empty($imageData)) {
                $listingIds = ProductListing::where('products_id', $productListing->products_id)->pluck('id');
                ProductAdditionalDetail::whereIn('product_listings_id', $listingIds)->update($imageData);
            }
            if ($request->filled('seasonal_type')) {
                $asin1 = $productListing->product?->asins()->value('asin1');
                if ($asin1) {
                    ProductCategorisation::where('child_asin', $asin1)
                        ->update(['seasonal_type' => $request->seasonal_type]);
                }
            }

            // Progress Status
            $productListing->load('additionalDetail');
            $productListing->progress_status = calculate_progress_status($productListing);
            $productListing->save();

            // Pricing Calculations
            $productListing->load('containerInfo', 'pricing');
            $calcResult = $this->priceCalculations($productListing);

            $sharedProductData = [
                'title_amazon' => $productListing->title_amazon,
            ];

            $sharedPricingData = [
                'item_price' => $productListing->pricing->item_price,
                'postage' => $calcResult['postage'] ?? $productListing->pricing->postage,
                'duty' => $calcResult['duty'] ?? $productListing->pricing->duty,
                'base_price' => $calcResult['base_price'] ?? $productListing->pricing->base_price,
            ];

            $sharedContainerData = [
                'item_weight_kg' => $productListing->containerInfo->item_weight_kg,
                'carton_weight_kg' => $productListing->containerInfo->carton_weight_kg,
                'quantity_per_carton' => $productListing->containerInfo->quantity_per_carton,
                'item_size_length_cm' => $productListing->containerInfo->item_size_length_cm,
                'item_size_width_cm' => $productListing->containerInfo->item_size_width_cm,
                'item_size_height_cm' => $productListing->containerInfo->item_size_height_cm,
                'ctn_size_length_cm' => $productListing->containerInfo->ctn_size_length_cm,
                'ctn_size_width_cm' => $productListing->containerInfo->ctn_size_width_cm,
                'ctn_size_height_cm' => $productListing->containerInfo->ctn_size_height_cm,
                'hs_code_percentage' => $productListing->containerInfo->hs_code_percentage,
            ];

            $listingIds = ProductListing::where('products_id', $productListing->products_id)->pluck('id');

            ProductListing::whereIn('id', $listingIds)->update($sharedProductData);
            ProductPricing::whereIn('product_listings_id', $listingIds)->update($sharedPricingData);
            ProductContainerInfo::whereIn('product_listings_id', $listingIds)->update($sharedContainerData);

            if ($productListing->progress_status == 3) {
                $this->trackProductModifications($productListing, $oldData);
            }

            return response()->json(array_merge([
                'success' => true,
                'message' => 'Product updated successfully.',
                'updated_listing' => $productListing,
            ], $calcResult));
        } catch (\Exception $e) {
            Log::error('Update Product Error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }
    protected function touchContentIfChanged(ProductListing $listing, array $before, bool $siblings = true): array
    {
        $groups = [
            'title'       => ['title_amazon'],
            'bullets'     => ['bullet_point_1', 'bullet_point_2', 'bullet_point_3', 'bullet_point_4', 'bullet_point_5'],
            'description' => ['description'],
        ];

        $flagCols = [
            'title'       => 'title_change_status',
            'bullets'     => 'bullets_change_status',
            'description' => 'description_change_status',
        ];

        $keys   = array_merge(...array_values($groups));
        $before = Arr::only($before, $keys);
        $after  = Arr::only($listing->getAttributes(), $keys);

        $diff    = [];
        $changed = [];

        foreach ($groups as $group => $fields) {
            foreach ($fields as $k) {
                $from = $before[$k] ?? null;
                $to   = $after[$k] ?? null;

                if ($from !== $to) {
                    $diff[$k] = ['from' => $from, 'to' => $to];
                    $changed[$group] = true;
                }
            }
        }

        if (!$diff) {
            return ['changed' => false, 'diff' => [], 'groups' => []];
        }

        $query = $siblings
            ? ProductListing::where('products_id', $listing->products_id)
            : ProductListing::whereKey($listing->id);

        $update = [
            'sync_status' => 'dirty',
            'updated_at'  => now(),
        ];

        // Set boolean flags only for sections that changed
        foreach ($changed as $group => $_) {
            $update[$flagCols[$group]] = true;
        }

        $query->update($update);

        return [
            'changed' => true,
            'diff'    => $diff,
            'groups'  => array_keys($changed),
        ];
    }

    public function syncProductDetails($productId)
    {
        $this->authorize(ProductEnum::ProductSync);
        $listing = ProductListing::findOrFail($productId);
        ProductUpdateJob::dispatch($listing->id);
        // ProductUpdateJob::dispatchSync($listing->id);
        return redirect()->back()->with('success', 'Sync job dispatched successfully!');
    }

    private function priceCalculations($productListing)
    {
        try {
            if (!$productListing) {
                return ['success' => false, 'message' => 'Invalid container or product listing.'];
            }

            $pricingUpdates = [];
            $postage        = calculatePostageIfApplicable($productListing->containerInfo);
            $duty           = calculateDutyIfApplicable($productListing->pricing, $productListing->containerInfo);

            if (!is_null($postage)) {
                $pricingUpdates['postage'] = $postage;
            }
            if (!is_null($duty)) {
                $pricingUpdates['duty'] = $duty;
            }
            // get Base Price 
            $container = $productListing->pricing;
            if ($container->fba_fee) {
                if (hasPricingDetailsProduct($container)) {
                    $landedCost                   = landed_cost($container->item_price, $container->postage, $container->duty);
                    $basePrice                    = calculateBasePrice($landedCost, $container->fba_fee, $productListing->country);
                    $pricingUpdates['base_price'] = $basePrice;
                }
            } else {
                $basePrice                    = null;
                $pricingUpdates['base_price'] = $basePrice;
            }
            if ($pricingUpdates) {
                $productListing->pricing()->update($pricingUpdates);
            }

            // if ($productListing->id && $productListing->progress_status === 3) {
            //     $this->trackProductModifications($productListing, $productListing->getOriginal());
            // }

            return array_merge(
                ['success' => true, 'message' => 'Product updated successfully.'],
                array_filter([
                    'postage' => $postage,
                    'duty'    => $duty,
                ], fn($v) => !is_null($v)),
                ['base_price' => $basePrice ?? null]
            );
        } catch (\Exception $e) {
            Log::error('Update Product Pricing Calculations: ' . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    protected function trackProductModifications($productListing, $oldData)
    {
        try {
            $userId  = Auth::user()->id;
            $country = $productListing->country;

            $fieldGroups = [
                'main' => [
                    'fields' => [
                        'sku',
                        'short_title',
                        'translator',
                        'title_amazon',
                        'bullet_point_1',
                        'bullet_point_2',
                        'bullet_point_3',
                        'bullet_point_4',
                        'bullet_point_5',
                        'description',
                        'search_terms',
                        'advertising_keywords',
                        'country',
                        'product_category'
                    ],
                    'newData' => $productListing,
                ],
                'pricing' => [
                    'fields' => [
                        'item_price',
                        'postage',
                        'base_price',
                        'fba_fee',
                        'duty',
                        'air_ship'
                    ],
                    'newData' => $productListing->pricing,
                ],
                'container' => [
                    'fields' => [
                        'commercial_invoice_title',
                        'hs_code',
                        'hs_code_percentage',
                        'item_weight_kg',
                        'carton_weight_kg',
                        'quantity_per_carton',
                        'carton_cbm',
                        'moq',
                        'product_material',
                        'order_lead_time_weeks',
                        'item_size_length_cm',
                        'item_size_width_cm',
                        'item_size_height_cm',
                        'ctn_size_length_cm',
                        'ctn_size_width_cm',
                        'ctn_size_height_cm'
                    ],
                    'newData' => $productListing->containerInfo,
                ],
                'additional' => [
                    'fields' => array_merge(
                        ['listing_to_copy', 'warnings'],
                        [
                            'instructions_file',
                            'fba_barcode_file',
                            'product_label_file',
                            'instructions_file_2',
                            'listing_research_file',
                            'image1',
                            'image2',
                            'image3',
                            'image4',
                            'image5',
                            'image6'
                        ]
                    ),
                    'newData' => $productListing->additionalDetail,
                ],
            ];

            foreach ($fieldGroups as $group => $data) {
                $fields = $data['fields'];
                $newData = $data['newData'];
                $oldGroupData = $oldData[$group] ?? [];

                if (!$newData) continue;

                foreach ($fields as $field) {
                    $oldValue = $oldGroupData[$field] ?? null;
                    $newValue = $newData->$field ?? null;

                    if ($oldValue !== $newValue) {
                        ProductListingLog::create([
                            'product_id' => $productListing->id,
                            'field_name' => $field,
                            'old_value' => $oldValue,
                            'new_value' => $newValue,
                            'user_id' => $userId,
                            'country' => $country,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to log field changes for product ID {$productListing->id}: " . $e->getMessage(), ['exception' => $e,]);
        }
    }

    public function inActive($id)
    {
        $this->authorize(ProductEnum::ProductInactive);
        try {
            $product = Product::where('uuid', $id)->firstOrFail();
            // Toggle status: active (1) -> inactive (0), else active (1)
            $product->status = !$product->status;
            $product->save();
            return redirect()->back()->with('success', 'Product status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
