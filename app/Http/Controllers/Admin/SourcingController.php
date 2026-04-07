<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\SourcingEnum;
use App\Http\Controllers\Controller;
use App\Models\ProductListing;
use App\Models\SourcingBuyerQuestionChat;
use App\Models\SourcingContainer;
use App\Models\SourcingContainerItem;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Exports\SourcingExport;
use Maatwebsite\Excel\Facades\Excel;

class SourcingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(SourcingEnum::Sourcing);
        $containers          = SourcingContainer::select('container_id', 'uuid')->get();
        $activeContainerUuid = $request->input('uuid')
            ?? SourcingContainer::latest('created_at')->value('uuid');

        if (!$activeContainerUuid) {
            return view('pages.admin.sourcing.index', [
                'sourcing'            => collect(),
                'containers'          => $containers,
                'activeContainerUuid' => null,
            ]);
        }
        $activeContainerId = SourcingContainer::where('uuid', $activeContainerUuid)->value('id');
        // Default to pending (0) if not explicitly "1"
        $completedFlag = $request->input('completed') === '1' ? 1 : 0;

        $sourcing = SourcingContainerItem::where('sourcing_container_id', $activeContainerId)
            ->where('add_to_pl', $completedFlag)
            ->paginate($request->get('per_page', 15));

        return view('pages.admin.sourcing.index', compact('sourcing', 'containers', 'activeContainerUuid'));
    }


    public function createContainer(Request $request)
    {
        $request->validate([
            'container_id' => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
        ]);
        try {
            $container = SourcingContainer::create([
                'uuid'         => (string) Str::uuid(),
                'container_id' => $request->container_id,
                'description'  => $request->description,
            ]);
            return redirect()
                ->route('admin.sourcing.index', ['uuid' => $container->uuid])
                ->with('success', 'Sourcing Container created successfully.');
        } catch (\Exception $e) {
            Log::warning($e);
            return redirect()->back()->with('error', 'An error occurred while fetching the sourcing data.');
        }
    }

    public function createListingItem(Request $request)
    {
        $this->authorize(SourcingEnum::SourcingCreate);
        $request->validate([
            'uuid'       => 'required',
            'amazon_url' => 'nullable|string',
            'amz_price'  => 'nullable|numeric',
        ]);
        try {
            $container = SourcingContainer::where('uuid', $request->uuid)->first();
            $asin      = $this->extractAsinFromUrl($request->amazon_url);
            // Get suppliers with role 'Supplier'
            $suppliers  = User::role('Supplier')->get();
            $supplierId = null;
            if ($suppliers->count() === 1) {
                $supplierId = $suppliers->first()->id;
            }
            $item      = SourcingContainerItem::create([
                'uuid'                  => (string) Str::uuid(),
                'sourcing_container_id' => $container->id,
                'amazon_url'            => $request->amazon_url,
                'amz_price'             => $request->amz_price,
                'asin_no'               => $asin,
                'supplier_id'           => $supplierId,
            ]);
            return redirect()->route('admin.sourcing.edit', $item->uuid)
                ->with('success', 'Sourcing Countainer created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'An error occurred while creating sourcing. Please try again.');
        }
    }

    public function edit($uuid)
    {
        $this->authorize(SourcingEnum::SourcingUpdate);
        try {
            $sourcing           = SourcingContainerItem::where('uuid', $uuid)->firstOrFail();
            $sourcing->fba_cost = json_decode($sourcing->fba_cost, true) ?? [
                'US' => '',
                'CA' => '',
                'UK' => '',
                'DE' => '',
                'ES' => '',
                'FR' => '',
                'EU' => ''
            ];
            $suppliers = User::role('Supplier')->get();
            $chats     = $this->getChats($uuid);

            return view('pages.admin.sourcing.form', compact('sourcing', 'suppliers', 'chats'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while fetching the sourcing data.');
        }
    }

    public function update(Request $request, $uuid)
    {
        $this->authorize(SourcingEnum::SourcingUpdate);
        try {
            $item = SourcingContainerItem::where('uuid', $uuid)->first();
            if ($request->filled('sku') && Product::where('sku', $request->sku)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'The given SKU already exists in the Product.',
                ]);
            }
            $data = $request->only([
                'description',
                'sku',
                'amazon_url',
                'amz_price',
                'asin_no',
                'pro_variations',
                'notes',
                'supplier_id',
                'short_title',
                'ean',
                'base_price_us',
                'base_price_uk',
                'base_price_eu',
                'qty_to_order',
                'postage',
                'duty',
                'air_ship',
                'item_length',
                'item_widht',
                'item_height',
                'carton_length',
                'carton_width',
                'carton_height',
                'item_weight_kg',
                'carton_qty',
                'pro_weight',

                'unit_price',
                'shipping_cost',
                'landed_costs_eu',
                'landed_costs_us',
                'landed_costs_uk',
                'moq',
                'total_order_value'

            ]);

            $existingFbaCost = json_decode($item->fba_cost, true) ?? [
                'US' => '',
                'CA' => '',
                'UK' => '',
                'DE' => '',
                'FR' => '',
                'ES' => '',
                'EU' => ''
            ];

            $fbaKeys = [
                'fba_cost_us' => 'US',
                'fba_cost_ca' => 'CA',
                'fba_cost_uk' => 'UK',
                'fba_cost_de' => 'DE',
                'fba_cost_fr' => 'FR',
                'fba_cost_es' => 'ES',
                'fba_cost_eu' => 'EU',
            ];

            foreach ($fbaKeys as $inputField => $jsonKey) {
                if ($request->has($inputField)) {
                    $existingFbaCost[$jsonKey] = $request->input($inputField, '');
                }
            }
            $data['fba_cost'] = json_encode($existingFbaCost);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('sourcing', 'public');
            }

            SourcingContainerItem::updateOrCreate(
                ['uuid' => $uuid],
                $data
            );
            $container = $item->refresh();
            $response  = $this->priceCalculations($container);
            return response()->json($response);
        } catch (\Exception $e) {
            Log::warning($e);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving sourcing data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    // all calculation related function is seperated
    private function priceCalculations($container)
    {
        if (!$container) {
            return ['success' => false, 'message' => 'Invalid container data.'];
        }
        $sContainerItem = [];

        // Check and calculate shipping cost
        if (hasCartonDetails($container)) {
            $sContainerItem['shipping_cost'] = calculate_postage(
                (float) $container->carton_length,
                (float) $container->carton_width,
                (float) $container->carton_height,
                (float) $container->carton_qty
            );
            $container->shipping_cost = $sContainerItem['shipping_cost']; // for landed cost calculation
        }
        // Check and calculate landed costs
        if (hasPricingDetails($container)) {
            $landedCost = landed_cost($container->unit_price, $container->shipping_cost, 0);
            foreach (['eu', 'us', 'uk'] as $region) {
                $sContainerItem["landed_costs_{$region}"] = $landedCost;
            }
        } else {
            foreach (['eu', 'us', 'uk'] as $region) {
                $sContainerItem["landed_costs_{$region}"] = null;
            }
        }
        if($container->unit_price && $container->qty_to_order){
            $sContainerItem["total_order_value"] = $container->unit_price * $container->qty_to_order;
        }
        $container->fba_cost = json_decode($container->fba_cost, true) ?? [
            'US' => '',
            'CA' => '',
            'UK' => '',
            'DE' => '',
            'ES' => '',
            'EU' => '',
            'FR' => '',
        ];
        // Base price calculations
        $sContainerItem['base_price_us'] = calculateBasePrice($container->landed_costs_us, $container->fba_cost['US'] ?? null, 'US');
        $sContainerItem['base_price_uk'] = calculateBasePrice($container->landed_costs_uk, $container->fba_cost['UK'] ?? null, 'UK');
        $sContainerItem['base_price_eu'] = calculateBasePrice($container->landed_costs_eu, $container->fba_cost['EU'] ?? null, 'EU');

        if ($sContainerItem) {
            $container->update($sContainerItem);
        }
        return array_merge(
            ['success' => true, 'message' => 'Sourcing data saved successfully.'],
            $sContainerItem
        );
    }

    public function getChats($uuid)
    {
        $container = SourcingContainerItem::where('uuid', $uuid)->firstOrFail();
        $chats     = $container->sourcingBuyerQuestionChats()
            ->with(['sender', 'receiver'])
            ->get()
            ->transform(function ($chat) {
                $chat->diff_for_humans = Carbon::parse($chat->created_at)->diffForHumans();
                return $chat;
            });

        return $chats;
    }

    public function saveChats(Request $request)
    {
        $request->validate([
            'uuid'         => 'required|exists:sourcing_container_items,uuid',
            'q_a'          => 'required|string',
            'attachment'   => 'nullable|file|mimes:jpeg,png,pdf,doc,docx|max:5120',
            'receiver'     => 'required|string',
            'record_type'  => 'nullable|string',
            'read_status'  => 'nullable|boolean',
        ]);

        try {
            $containerItem = SourcingContainerItem::where('uuid', $request->uuid)->firstOrFail();
            if (!$containerItem) {
                return response()->json(['error' => 'Related container item not found'], 404);
            }
            if (!$containerItem->supplier_id) {
                return response()->json(['error' => 'Please select the supplier to chat'], 404);
            }

            $chatData = [
                'sourcing_container_items_id' => $containerItem->id,
                'q_a'                         => $request->q_a,
                'sender_id'                   => Auth::user()->id,
                'receiver_id'                 => $containerItem->supplier_id,
                'record_type'                 => $request->record_type ?? null,
                'read_status'                 => $request->read_status ?? false,
            ];

            if ($request->hasFile('attachment')) {
                $chatData['attachment'] = $request->file('attachment')->store('chat_attachments', 'public');
            }

            $chat = SourcingBuyerQuestionChat::create($chatData);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!',
                'chat'    => $chat,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in saveChats: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function archive($uuid)
    {
        try {
            DB::beginTransaction();

            $item    = SourcingContainerItem::where('uuid', $uuid)->firstOrFail();
            $product = Product::firstOrCreate(
                ['sku'  => $item->sku, 'short_title' => $item->short_title],
                ['uuid' => (string) Str::uuid()]
            );

            $now       = now();
            $countries = config('countries');

            // Prepare 6 listings linked to this product
            $listingsData = [];
            foreach ($countries as $country) {
                $listingsData[] = [
                    'uuid'             => (string) Str::uuid(),
                    'products_id'      => $product->id,
                    'country'          => $country,
                    'product_category' => '',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            // Insert all listings
            ProductListing::insert($listingsData);

            $insertedListings = ProductListing::where('products_id', $product->id)
                ->whereIn('country', $countries)
                ->get();

            foreach ($insertedListings as $listing) {
                $listing->additionalDetail()->create([
                    'warnings' => $item->buyer_questions,
                ]);
                $listing->containerInfo()->create([
                    'item_size_length_cm' => $item->item_length,
                    'item_size_width_cm'  => $item->item_widht,
                    'item_size_height_cm' => $item->item_height,
                    'ctn_size_length_cm'  => $item->carton_length,
                    'ctn_size_width_cm'   => $item->carton_width,
                    'ctn_size_height_cm'  => $item->carton_height,
                    'quantity_per_carton' => $item->carton_qty,
                    'item_weight_kg'      => $item->pro_weight,
                    'moq'                 => $item->moq,
                ]);
                $listing->pricing()->create([
                    'item_price' => $item->unit_price,
                    'postage'    => $item->shipping_cost,
                    'base_price' => $item->suplier_price,
                ]);
            }

            // Now mark sourcing item as archived
            $item->update([
                'archived'         => 1,
                'archived_note'    => request()->input('archived_note', 'Archived by admin.'),
                'archiver_user_id' => Auth::id(),
                'archived_date'    => $now,
                'add_to_pl'        => 1,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Item archived and product data synced successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Exception in archive: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    public function moveToContainer(Request $request)
    {
        try {
            $itemUuid      = $request->input('item_uuid');
            $containerUuid = $request->input('container_uuid');
            $item          = SourcingContainerItem::where('uuid', $itemUuid)->firstOrFail();
            $container     = SourcingContainer::where('uuid', $containerUuid)->firstOrFail();

            if ($item->sourcing_container_id == $container->id) {
                return response()->json(['status' => 'success', 'message' => 'The item is already in the selected container.']);
            }

            $item->update(['sourcing_container_id' => $container->id]);

            return response()->json(['status' => 'success', 'message' => 'Item moved to container successfully.']);
        } catch (\Exception $e) {
            Log::error('Error moving item: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function exportExcel()
    {
        $this->authorize(SourcingEnum::SourcingExport);
        $timestamp = now()->format('Y_m_d_His');
        $filename  = "sourcing_{$timestamp}.xlsx";

        return Excel::download(new SourcingExport, $filename);
    }

    public function extractAsinFromUrl(string $url): ?string
    {
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/product\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
