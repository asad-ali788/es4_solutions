<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\ShipmentEnum;
use App\Http\Controllers\Controller;
use App\Imports\ShipmentItemsImport;
use App\Models\InboundShipment;
use App\Models\InboundShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Exports\ExcludedShipmentItemsExport;
use App\Exports\ShipmentItemExport;
use App\Exports\UpdateShipmentItemsExport;
use App\Http\Requests\Admin\ShippingRequest;
use App\Imports\UpdateShipmentItemsImport;
use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(ShipmentEnum::Shipment);
        $query = InboundShipment::with(['supplier', 'warehouse']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('carrier_name', 'like', "%{$search}%")
                    ->orWhere('shipment_name', 'like', "%{$search}%");
            });
        }

        $shipments = $query->paginate(10)->appends($request->query());

        return view('pages.admin.shipments.index', compact('shipments'));
    }
    public function create()
    {
        $this->authorize(ShipmentEnum::ShipmentCreate);
        $shipment     = null;
        $warehouses   = Warehouse::get();
        $supplierUser = User::role('Supplier')->get();
        return view('pages.admin.shipments.form', compact('shipment', 'supplierUser', 'warehouses'));
    }

    public function store(ShippingRequest $request)
    {
        $this->authorize(ShipmentEnum::ShipmentCreate);
        $shipment =  InboundShipment::create($request->validated());
        if ($request->hasFile('excel_file')) {
            $import = new ShipmentItemsImport($shipment->id);
            Excel::import($import, $request->file('excel_file'));

            $skipped = $import->getExcludedRows();

            if (!empty($skipped)) {
                // Use the helper function
                delete_old_files('temp', 1440, 'public');

                // Store file temporarily in storage
                $fileName = 'skipped_items_' . now()->timestamp . '.xlsx';
                $filePath = 'temp/' . $fileName;

                Excel::store(new ExcludedShipmentItemsExport($skipped), $filePath, 'public');

                return redirect()->route('admin.shipments.index')
                    ->with('success', 'Shipment updated successfully. Some SKUs were skipped and available for download.')
                    ->with('skipped_file', $fileName);
            }
        }

        return redirect()->route('admin.shipments.index')->with('success', 'Shipment created successfully.');
    }

    public function edit($id)
    {
        $this->authorize(ShipmentEnum::ShipmentUpdate);
        $shipment     = InboundShipment::findOrFail($id);
        $supplierUser = User::role('Supplier')->get();
        $warehouses   = Warehouse::all();

        return view('pages.admin.shipments.form', compact('shipment', 'supplierUser', 'warehouses'));
    }

    public function update(ShippingRequest $request, $id)
    {
        $this->authorize(ShipmentEnum::ShipmentUpdate);
        $shipment  = InboundShipment::findOrFail($id);
        $shipment->update($request->validated());

        if ($request->hasFile('excel_file')) {
            $import = new ShipmentItemsImport($shipment->id);
            Excel::import($import, $request->file('excel_file'));

            $skipped = $import->getExcludedRows();

            if (!empty($skipped)) {
                // Use the helper function
                delete_old_files('temp', 1440, 'public');

                // Store file temporarily in storage
                $fileName = 'skipped_items_' . now()->timestamp . '.xlsx';
                $filePath = 'temp/' . $fileName;


                Excel::store(new ExcludedShipmentItemsExport($skipped), $filePath, 'public');

                return redirect()->route('admin.shipments.index')
                    ->with('success', 'Shipment updated successfully. Some SKUs were skipped and available for download.')
                    ->with('skipped_file', $fileName);
            }
        }

        return redirect()->route('admin.shipments.index')
            ->with('success', 'Shipment updated successfully.');
    }

    public function destroy(InboundShipment $shipment)
    {
        $this->authorize(ShipmentEnum::ShipmentDelete);
        try {
            $shipment->items()->delete();
            $shipment->delete();
            return redirect()->back()->with('success', 'Shipment and its items deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while fetching the sourcing data.');
        }
    }

    public function updateShipments(Request $request, $id)
    {
        $this->authorize(ShipmentEnum::ShipmentUpdate);
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
        ]);

        $shipment = InboundShipment::findOrFail($id);

        if ($request->hasFile('excel_file')) {
            $import = new UpdateShipmentItemsImport($shipment->id);
            Excel::import($import, $request->file('excel_file'));

            $skipped = $import->getExcludedRows();

            if (!empty($skipped)) {
                delete_old_files('temp', 1440, 'public');

                $fileName = 'update_skipped_items_' . now()->timestamp . '.xlsx';
                $filePath = 'temp/' . $fileName;

                Excel::store(new UpdateShipmentItemsExport($skipped), $filePath, 'public');

                return redirect()->back()
                    ->with('success', 'Shipment items received quantity updated successfully.')
                    ->with('skipped_file', $fileName);
            }

            return redirect()->back()->with('success', 'Shipment items received quantity updated successfully.');
        }

        return redirect()->back()->with('error', 'Unable to process the file');
    }

    // Shipment Item functionalities

    public function items($id)
    {
        $query = InboundShipmentItem::with(['shipment', 'product'])
            ->where('inbound_shipment_id', $id);

        if (request('search')) {
            $search = request('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('sku', 'like', '%' . $search . '%');
            });
        }

        $items = $query->latest()->paginate(10);

        return view('pages.admin.shipments.items.index', [
            'items'      => $items,
            'shipmentId' => $id,
        ]);
    }

    public function itemCreate($id)
    {
        $shipmentItem       = null;
        $shipment           = InboundShipment::findOrFail($id);
        $existingProductIds = InboundShipmentItem::where('inbound_shipment_id', $shipment->id)
            ->pluck('product_id'); // only fetch IDs, not the whole models

        $products = Product::whereNotIn('id', $existingProductIds)->get();
        return view('pages.admin.shipments.items.form', compact('shipment', 'shipmentItem', 'products'));
    }

    public function itemStore(Request $request)
    {
        $validated = $request->validate([
            'inbound_shipment_id' => 'required|exists:inbound_shipments,id',
            'product_id'          => 'required|exists:products,id',
            'quantity_ordered'    => 'required|numeric|min:1',
            'quantity_received'   => 'nullable|numeric|min:0',
            'status'              => 'required|in:pending,damaged,received,short',
        ]);
        // Remove 'quantity_received' if it's null
        if (is_null($validated['quantity_received'] ?? null)) {
            unset($validated['quantity_received']);
        }

        // Load product with related pricing
        $product = Product::with('listings.pricing')
            ->where('id', $validated['product_id'])
            ->first();

        if ($product && $product->listings->isNotEmpty()) {
            $listing  = $product->listings->first();
            $unitCost = optional($listing->pricing)->item_price;

            if ($unitCost !== null) {
                $validated['unit_cost']  = $unitCost;
                $validated['total_cost'] = $unitCost * $validated['quantity_ordered'];
            }
        }
        InboundShipmentItem::create($validated);

        return redirect()
            ->route('admin.shipments.items', $validated['inbound_shipment_id'])
            ->with('success', 'Item added successfully.');
    }

    public function itemEdit($id)
    {
        $this->authorize(ShipmentEnum::AllShipmentUpdate);
        $shipmentItem = InboundShipmentItem::findOrFail($id);
        $shipment     = InboundShipment::findOrFail($shipmentItem->inbound_shipment_id);

        // Get all product IDs already in the shipment, except the one being edited
        $existingProductIds = InboundShipmentItem::where('inbound_shipment_id', $shipment->id)
            ->where('id', '!=', $shipmentItem->id)
            ->pluck('product_id')
            ->toArray();

        // Exclude existing product IDs, but allow the current one
        $products = Product::whereNotIn('id', $existingProductIds)
            ->orWhere('id', $shipmentItem->product_id)
            ->get();

        return view('pages.admin.shipments.items.form', compact('shipment', 'shipmentItem', 'products'));
    }

    public function itemUpdate(Request $request, $id)
    {
        $this->authorize(ShipmentEnum::AllShipmentUpdate);
        $item = InboundShipmentItem::findOrFail($id);

        $validated = $request->validate([
            'inbound_shipment_id' => 'required|exists:inbound_shipments,id',
            'product_id'          => 'required|exists:products,id',
            'quantity_ordered'    => 'required|numeric|min:1',
            'quantity_received'   => 'nullable|numeric|min:0',
            'status'              => 'required|in:pending,damaged,received,short',
        ]);

        // Load product with related pricing
        $product = Product::with('listings.pricing')
            ->where('id', $validated['product_id'])
            ->first();

        if ($product && $product->listings->isNotEmpty()) {
            $listing  = $product->listings->first(); // Or find specific one if needed
            $unitCost = optional($listing->pricing)->item_price;

            if ($unitCost !== null) {
                $validated['unit_cost']  = $unitCost;
                $validated['total_cost'] = $unitCost * $validated['quantity_ordered'];
            }
        }

        $item->update($validated);

        return redirect()
            ->route('admin.shipments.items', $item->inbound_shipment_id)
            ->with('success', 'Item updated successfully.');
    }

    public function itemDelete($inboundShipmentItem)
    {
        try {
            $isi = InboundShipmentItem::findOrFail($inboundShipmentItem);
            $isi->delete();
            return redirect()->back()
                ->with('success', 'Item Deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while deleting data.');
        }
    }

    //All Shiment List function

    public function shipmentLists(Request $request)
    {
        $this->authorize(ShipmentEnum::AllShipmentList);
        try {
            $products = Product::with('inboundShipmentItems');
            if ($request->filled('search')) {
                $products->where('sku', 'like', "%{$request->input('search')}%");
            }

            $paginatedProducts = $products->paginate(10);
            $shipments         = InboundShipment::whereNotIn('status', ['received', 'cancelled'])->with('items')->get();
            $columns = $shipments->map(function ($shipment) {
                return [
                    'id'                => $shipment->id,
                    'shipment_name'     => $shipment->shipment_name,
                    'carrier_name'      => $shipment->carrier_name,
                    'expected_arrival'  => $shipment->expected_arrival,
                    'warehouse_name'    => optional($shipment->warehouse)->warehouse_name, // Safely access
                    'status'            => $shipment->status,
                ];
            })->toArray();            // Check if shipments exist
            if ($shipments->isEmpty()) {
                return redirect()->back()->with('warning', 'No shipments found.');
            }
            // Build matrix based on paginated products
            $matrix = [];
            foreach ($paginatedProducts as $product) {
                $row = ['sku' => $product->sku];

                foreach ($columns as $shipment) {
                    $item = $product->inboundShipmentItems
                        ->where('inbound_shipment_id', $shipment['id'])
                        ->first();

                    $row[$shipment['id']] = $item ? $item->quantity_ordered : 0;
                }
                $matrix[] = $row;
            }
            $matrixPaginator = $paginatedProducts->setCollection(collect($matrix));
            return view('pages.admin.shipments.lists.index', [
                'matrix'  => $matrixPaginator,
                'columns' => $columns,
            ]);
        } catch (\Exception $e) {
            Log::warning($e);
            return redirect()->back()->with('error', 'An error occurred while fetching data.');
        }
    }

    //Shipment Item Exports

    public function shipmentItemExport($id)
    {
        try {
            $items = InboundShipmentItem::where('inbound_shipment_id', $id)->with('product', 'shipment.warehouse')->get();
            return Excel::download(new ShipmentItemExport($items), 'shipment_items_export_' . now()->timestamp . '.xlsx');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while fetching data.');
        }
    }
}
