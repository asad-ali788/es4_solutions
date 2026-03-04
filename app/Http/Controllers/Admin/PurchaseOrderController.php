<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\PurchaseOrderEnum;
use App\Exports\ExcludedPurchaseOrderItemsExport;
use App\Exports\UpdatePurchaseOrderItemsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrderRequest;
use App\Imports\PurchaseOrderImport;
use App\Imports\UpdatePurchaseOrderItemsImport;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(PurchaseOrderEnum::PurchaseOrder);
        $query = PurchaseOrder::with(['supplier', 'warehouse']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('expected_arrival', 'like', "%{$search}%")
                    ->orWhere('order_date', 'like', "%{$search}%");
            });
        }

        $purchaseOrder = $query->paginate($request->get('per_page', 10));
        return view('pages.admin.purchaseOrder.index', \compact('purchaseOrder'));
    }

    public function create()
    {
        $this->authorize(PurchaseOrderEnum::PurchaseOrderAdd);
        $purchaseOrder = null;
        $warehouses    = Warehouse::get();
        $supplierUser  = User::role('Supplier')->get();
        return view('pages.admin.purchaseOrder.form', \compact('purchaseOrder', 'warehouses', 'supplierUser'));
    }

    public function store(PurchaseOrderRequest $request)
    {
        $this->authorize(PurchaseOrderEnum::PurchaseOrderAdd);
        try {
            $data = $request->validated();
            $data['uuid'] = Str::uuid();
            $order = PurchaseOrder::create($data);
            if ($request->hasFile('excel_file')) {
                $import = new PurchaseOrderImport($order->id);
                Excel::import($import, $request->file('excel_file'));

                $skipped = $import->getExcludedRows();

                if (!empty($skipped)) {
                    delete_old_files('temp', 1440, 'public');

                    $fileName = 'skipped_items_' . now()->timestamp . '.xlsx';
                    $filePath = 'temp/' . $fileName;

                    Excel::store(new ExcludedPurchaseOrderItemsExport($skipped), $filePath, 'public');

                    return redirect()->route('admin.purchaseOrder.index')
                        ->with('success', 'Purchase Order created. Some SKUs were skipped and available for download.')
                        ->with('skipped_file', $fileName);
                }
            }

            return redirect()->route('admin.purchaseOrder.index')->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            Log::error("Error @ PurchaseOrder.store: " . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while creating the Purchase Order.');
        }
    }

    public function edit($id)
    {
        $this->authorize(PurchaseOrderEnum::PurchaseOrderAdd);
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $warehouses    = Warehouse::all();
        $supplierUser  = User::role('Supplier')->get();

        return view('pages.admin.purchaseOrder.form', compact('purchaseOrder', 'warehouses', 'supplierUser'));
    }

    public function update(PurchaseOrderRequest $request, $id)
    {
        $this->authorize(PurchaseOrderEnum::PurchaseOrderAdd);
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            $purchaseOrder->update($request->validated());

            return redirect()->route('admin.purchaseOrder.index')->with('success', 'Purchase Order updated successfully.');
        } catch (\Exception $e) {
            Log::error("Error @ PurchaseOrder.update: " . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while updating the Purchase Order.');
        }
    }

    public function destroy($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            PurchaseOrderItem::where('purchase_order_id', $id)->delete();

            $purchaseOrder->delete();

            return redirect()->route('admin.purchaseOrder.index')->with('success', 'Purchase Order and its items deleted successfully.');
        } catch (\Exception $e) {
            Log::error("Error @ PurchaseOrder.destroy: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete Purchase Order.');
        }
    }

    public function items($id)
    {
        $query = PurchaseOrderItem::with(['order', 'product'])->where('purchase_order_id', $id);

        if (request('search')) {
            $search = request('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(10);

        return view('pages.admin.purchaseOrder.items.index', [
            'items'     => $items,
            'orderId'   => $id,
        ]);
    }

    public function itemCreate($id)
    {
        $orderItem          = null;
        $order              = PurchaseOrder::findOrFail($id);
        $existingProductIds = PurchaseOrderItem::where('purchase_order_id', $order->id)
            ->pluck('product_id');

        $products = Product::whereNotIn('id', $existingProductIds)->get();

        return view('pages.admin.purchaseOrder.items.form', compact('order', 'orderItem', 'products'));
    }

    public function itemStore(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id'   => 'required|exists:purchase_orders,id',
            'product_id'          => 'required|exists:products,id',
            'quantity_ordered'    => 'required|numeric|min:1',
            'quantity_received'   => 'nullable|numeric|min:0',
            'status'              => 'required|in:pending,received,short,damaged',
            'remarks'             => 'nullable|string',
        ]);

        if (!isset($validated['quantity_received'])) {
            unset($validated['quantity_received']);
        }

        $product = Product::with('listings.pricing')
            ->where('id', $validated['product_id'])
            ->first();

        if ($product && $product->listings->isNotEmpty()) {
            $listing   = $product->listings->first();
            $unitPrice = optional($listing->pricing)->item_price;

            if ($unitPrice !== null) {
                $validated['unit_price'] = $unitPrice;
                $validated['total_price'] = $unitPrice * $validated['quantity_ordered'];
            }
        }

        PurchaseOrderItem::create($validated);

        return redirect()->route('admin.purchaseOrder.items', $validated['purchase_order_id'])
            ->with('success', 'Item added successfully.');
    }

    public function itemEdit($id)
    {
        $orderItem = PurchaseOrderItem::findOrFail($id);
        $order     = PurchaseOrder::findOrFail($orderItem->purchase_order_id);

        $existingProductIds = PurchaseOrderItem::where('purchase_order_id', $order->id)
            ->where('id', '!=', $orderItem->id)
            ->pluck('product_id')->toArray();

        $products = Product::whereNotIn('id', $existingProductIds)
            ->orWhere('id', $orderItem->product_id)
            ->get();

        return view('pages.admin.purchaseOrder.items.form', compact('order', 'orderItem', 'products'));
    }

    public function itemUpdate(Request $request, $id)
    {
        $item = PurchaseOrderItem::findOrFail($id);

        $validated = $request->validate([
            'purchase_order_id'   => 'required|exists:purchase_orders,id',
            'product_id'          => 'required|exists:products,id',
            'quantity_ordered'    => 'required|numeric|min:1',
            'quantity_received'   => 'nullable|numeric|min:0',
            'status'              => 'required|in:pending,received,short,damaged',
            'remarks'             => 'nullable|string',
        ]);

        $product = Product::with('listings.pricing')
            ->where('id', $validated['product_id'])
            ->first();

        if ($product && $product->listings->isNotEmpty()) {
            $listing   = $product->listings->first();
            $unitPrice = optional($listing->pricing)->item_price;

            if ($unitPrice !== null) {
                $validated['unit_price']  = $unitPrice;
                $validated['total_price'] = $unitPrice * $validated['quantity_ordered'];
            }
        }

        $item->update($validated);

        return redirect()->route('admin.purchaseOrder.items', $item->purchase_order_id)
            ->with('success', 'Item updated successfully.');
    }

    public function itemDelete($id)
    {
        try {
            $item = PurchaseOrderItem::findOrFail($id);
            $item->delete();

            return redirect()->back()->with('success', 'Item deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while deleting item.');
        }
    }

    public function allPurchaseOrders(Request $request)
    {
        $this->authorize(PurchaseOrderEnum::AllPurchaseOrderList);

        // Filter Products
        $productsQuery = Product::select('id', 'sku')->with(['listings.containerInfo']);

        if ($request->filled('search')) {
            $productsQuery->where('sku', 'like', "%{$request->input('search')}%");
        }

        $products = $productsQuery->paginate(10);

        // Generate 8 Weekly Buckets
        $startDate = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weeks     = [];

        for ($i = 0; $i < 8; $i++) {
            $weekStart     = $startDate->copy()->addWeeks($i);
            $weekEnd       = $weekStart->copy()->addDays(6);
            $label         = 'Week ' . ($i + 1) . ' (' . $weekStart->format('M j') . ' - ' . $weekEnd->format('M j') . ')';
            $weeks[$label] = [$weekStart, $weekEnd];
        }

        $columnHeaders = array_keys($weeks);

        // Fetch Purchase Order Items with Related Purchase Orders
        $purchaseOrders = PurchaseOrder::whereNotIn('status', ['received', 'cancelled'])
            ->with('items')
            ->get();

        // Preprocess Items by Product ID
        $itemsByProduct = [];

        foreach ($purchaseOrders as $po) {
            $orderDate = Carbon::parse($po->order_date);

            foreach ($po->items as $item) {
                $itemsByProduct[$item->product_id][] = [
                    'quantity'        => $item->quantity_ordered,
                    'order_date'      => $orderDate,
                    'purchaseOrderId' => $item->id,
                ];
            }
        }

        //Generate Matrix
        $matrix = [];

        foreach ($products as $product) {
            $delayed = false;
            $row     = array_fill_keys($columnHeaders, 0);

            $leadTimeWeeks = optional(optional($product->listings->first())->containerInfo)->order_lead_time_weeks ?? 0;

            foreach ($itemsByProduct[$product->id] ?? [] as $entry) {
                $expectedDate = $entry['order_date']->copy()->addWeeks($leadTimeWeeks);

                foreach ($weeks as $label => [$start, $end]) {
                    if ($expectedDate < $startDate) {
                        $delayed = true;
                        $firstWeekLabel = array_key_first($weeks);
                        $row[$firstWeekLabel] += $entry['quantity'];
                        break;
                    } elseif ($expectedDate->between($start, $end)) {
                        $row[$label] += $entry['quantity'];
                        break;
                    }
                }
            }

            $matrix[] = [
                'sku'     => $product->sku,
                'delayed' => $delayed,
                'weeks'   => $row,
            ];
        }

        $products->setCollection(collect($matrix));

        return view('pages.admin.purchaseOrder.lists.index', [
            'skuMatrix'     => $products,
            'columnHeaders' => $columnHeaders,
        ]);
    }

    public function delayedLists($sku)
    {
        $product            = Product::with('listings.containerInfo')->where('sku', $sku)->firstOrFail();
        $leadTimeWeeks      = optional($product->listings->first()?->containerInfo)->order_lead_time_weeks ?? 0;

        $currentWeekStart   = now()->startOfWeek(Carbon::MONDAY);
        $currentWeekEnd     = now()->endOfWeek(Carbon::SUNDAY);
        $purchaseOrderItems = PurchaseOrderItem::with(['product', 'order'])
            ->whereHas('product', fn($q) => $q->where('sku', $sku))
            ->whereHas('order', fn($q) => $q->whereNotIn('status', ['received', 'cancelled']))
            ->get();
        $delayedItems = [];

        foreach ($purchaseOrderItems as $item) {
            $order = $item->order;

            if (!$order || !$order->order_date) {
                continue;
            }

            $orderDate    = Carbon::parse($order->order_date);
            $expectedDate = $orderDate->copy()->addWeeks($leadTimeWeeks);

            if ($expectedDate->lt($currentWeekStart)) {
                $delayedItems[] = [
                    'order_number'      => $order->order_number,
                    'sku'               => $item->product->sku,
                    'order_date'        => $orderDate->format('d M Y'),
                    'expected_date'     => $expectedDate->startOfWeek()->format('M j') . ' - ' . $expectedDate->endOfWeek()->format('M j'),
                    'delayed_new_date'  => $currentWeekStart->format('M j') . ' - ' . $currentWeekEnd->format('M j'),
                    'quantity_ordered'  =>  $item->quantity_ordered,
                ];
            }
        }
        
        return view('pages.admin.purchaseOrder.lists.delayedList', [
            'product' => $product,
            'items'   => $delayedItems,
        ]);
    }

    public function updatePurchaseOrderItems(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($request->hasFile('excel_file')) {
            $import = new UpdatePurchaseOrderItemsImport($purchaseOrder->id);
            Excel::import($import, $request->file('excel_file'));

            $skipped = $import->getExcludedRows();

            if (!empty($skipped)) {
                delete_old_files('temp', 1440, 'public');

                $fileName = 'update_po_skipped_items_' . now()->timestamp . '.xlsx';
                $filePath = 'temp/' . $fileName;

                Excel::store(new UpdatePurchaseOrderItemsExport($skipped), $filePath, 'public');

                return redirect()->back()
                    ->with('success', 'Purchase order items updated successfully.')
                    ->with('skipped_file', $fileName);
            }

            return redirect()->back()->with('success', 'Purchase order items updated successfully.');
        }

        return redirect()->back()->with('error', 'Unable to process the file');
    }
}
