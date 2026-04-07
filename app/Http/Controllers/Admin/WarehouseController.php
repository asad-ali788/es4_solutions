<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\WarehouseEnum;
use App\Exports\WarehouseStockExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Warehouse;
use App\Models\ProductWhInventory;
use Illuminate\Support\Facades\Log;
use App\Imports\WarehouseInventoryImport;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(WarehouseEnum::WarehouseList);
        try {
            $query = Warehouse::with('inventories.product');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('warehouse_name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $warehouses = $query->latest()->paginate($request->input('per_page', 10));
            $perPage = 25;

            foreach ($warehouses as $warehouse) {
                $inventory = collect($warehouse->inventories);
                $currentPage = LengthAwarePaginator::resolveCurrentPage("inventory_page_{$warehouse->id}");

                $warehouse->pagedInventories = new LengthAwarePaginator(
                    $inventory->forPage($currentPage, $perPage),
                    $inventory->count(),
                    $perPage,
                    $currentPage,
                    [
                        'pageName' => "inventory_page_{$warehouse->id}",
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]
                );
            }

            return view('pages.admin.warehouse.index', compact('warehouses'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong while loading warehouses.');
        }
    }

    public function create()
    {
        $countries = config('countries');
        $editWarehouse = null;
        return view('pages.admin.warehouse.form', compact('editWarehouse', 'countries'));
    }

    // Store new warehouse
    public function createWarehouse(Request $request)
    {
        $request->validate([
            'warehouse_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            Warehouse::create([
                'uuid' => Str::uuid(),
                'warehouse_name' => $request->warehouse_name,
                'location' => $request->location,
            ]);
            return redirect()->route('admin.warehouse.index')->with('success', 'Warehouse created successfully.');
        } catch (Exception $e) {
            Log::error('Warehouse Store Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to create warehouse.');
        }
    }

    public function edit($uuid)
    {
        $countries = config('countries');

        $editWarehouse = Warehouse::where('uuid', $uuid)->firstOrFail();
        return view('pages.admin.warehouse.form', compact('editWarehouse', 'countries'));
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'warehouse_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            $warehouse = Warehouse::where('uuid', $uuid)->firstOrFail();
            $warehouse->update($request->only('warehouse_name', 'location'));
            return redirect()->route('admin.warehouse.index')->with('success', 'Warehouse updated successfully.');
        } catch (Exception $e) {
            Log::error('Warehouse Update Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update warehouse.');
        }
    }

    public function quantities(Request $request, $uuid)
    {
        $warehouse   = Warehouse::where('uuid', $uuid)->firstOrFail();
        $search      = $request->input('search');
        $inventories = ProductWhInventory::with('product')
            ->where('warehouse_id', $warehouse->id)
            ->when($search, function ($query, $search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhere('fnsku', 'like', "%{$search}%");
                });
            })
            ->paginate($request->input('per_page', 10))
            ->appends(['search' => $search]);

        return view('pages.admin.warehouse.quantities', compact('warehouse', 'inventories'));
    }


    public function importInventory(Request $request, $uuid)
    {
        $warehouse = Warehouse::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'import_file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        Excel::import(new WarehouseInventoryImport($warehouse->id), $request->file('import_file'));

        return redirect()->back()->with('success', 'Warehouse inventory imported successfully!');
    }

    public function inventoryForm($id, $inventoryId = null)
    {
        $editInventory = null;

        if ($inventoryId) {
            $editInventory = ProductWhInventory::where('id', $inventoryId)
                ->where('warehouse_id', $id)
                ->firstOrFail();
        }

        $excludedProductIds = ProductWhInventory::where('warehouse_id', $id)
            ->when($inventoryId, function ($query) use ($inventoryId) {
                $query->where('id', '!=', $inventoryId);
            })
            ->pluck('product_id')
            ->toArray();

        $products = Product::whereNotIn('id', $excludedProductIds)->latest()->get();

        $warehouse = Warehouse::where('id', $id)->firstOrFail();

        return view('pages.admin.warehouse.inventoryForm', compact('editInventory', 'products', 'warehouse'));
    }


    public function addInventory(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'nullable|integer',
            'reserved_quantity' => 'nullable|integer',
        ]);

        try {

            ProductWhInventory::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id ?? 0,
                'quantity' => $request->quantity ?? 0,
                'reserved_quantity' => $request->reserved_quantity ?? 0,
            ]);

            $warehouse = Warehouse::find($request->warehouse_id);

            if ($warehouse) {
                return redirect()
                    ->route('admin.warehouse.quantities', $warehouse->uuid)
                    ->with('success', 'Inventory added successfully.');
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Inventory added, but warehouse not found for redirection.');
            }
        } catch (Exception $e) {
            Log::error('Inventory Add Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to add inventory.');
        }
    }

    public function editInventory(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'nullable|integer',
            'reserved_quantity' => 'nullable|integer',
        ]);

        try {
            $inventory = ProductWhInventory::findOrFail($id);

            $inventory->update([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'quantity' => $request->quantity ?? 0,
                'reserved_quantity' => $request->reserved_quantity ?? 0,
            ]);

            $warehouse = Warehouse::find($request->warehouse_id);

            if ($warehouse) {
                return redirect()
                    ->route('admin.warehouse.quantities', $warehouse->uuid)
                    ->with('success', 'Inventory updated successfully.');
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Inventory updated, but warehouse not found for redirection.');
            }
        } catch (Exception $e) {
            Log::error('Inventory Update Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update inventory.');
        }
    }

    public function deleteInventory($id)
    {
        $inventory = ProductWhInventory::findOrFail($id);
        $inventory->delete();

        return redirect()->back()->with('success', 'Inventory deleted successfully.');
    }

    public function allWarehouseInventory(Request $request)
    {
        $this->authorize(WarehouseEnum::AllWarehouseStock);
        $warehouses = Warehouse::all();

        $stocksQuery = Product::query()
            ->select(['products.id', 'products.sku']);

        $warehouseLastUpdated = [];

        foreach ($warehouses as $wh) {
            $whSub = DB::table('product_wh_inventory')
                ->select(
                    'product_id',
                    DB::raw('SUM(available_quantity) as available'),
                    DB::raw('MAX(updated_at) as last_updated')
                )
                ->where('warehouse_id', $wh->id)
                ->groupBy('product_id');

            $alias = 'wh_' . $wh->id;

            $stocksQuery->leftJoinSub($whSub, $alias, function ($join) use ($alias) {
                $join->on('products.id', '=', "{$alias}.product_id");
            });

            // select alias name for stock
            $stocksQuery->addSelect([
                DB::raw("COALESCE({$alias}.available, 0) as wh_{$wh->id}_stock"),
            ]);

            // last updated timestamp per warehouse
            $warehouseLastUpdated[$wh->id] = ProductWhInventory::where('warehouse_id', $wh->id)->max('updated_at');
        }

        // --- Search Filter ---
        if ($request->filled('search')) {
            $stocksQuery->where('products.sku', 'like', '%' . $request->search . '%');
        }

        // --- Filter: hide SKUs with zero total stock across all warehouses ---
        $totalStockExpr = collect($warehouses)
            ->map(fn($wh) => "COALESCE(wh_{$wh->id}_stock, 0)")
            ->join(' + ');

        $stocksQuery->havingRaw("({$totalStockExpr}) > 0");

        // --- Paginate ---
        $stocks = $stocksQuery->paginate($request->input('per_page', 25));

        return view('pages.admin.warehouse.all_inventory_list', [
            'stocks'      => $stocks,
            'warehouses'  => $warehouses,
            'lastUpdated' => [
                'warehouses' => $warehouseLastUpdated,
            ],
        ]);
    }


    public function warehouseStockDownload()
    {
        $this->authorize(WarehouseEnum::AllWarehouseStockExport);
        try {
            return Excel::download(new WarehouseStockExport(), "warehouse_stocks" . now()->timestamp . ".xlsx");
        } catch (\Throwable $e) {
            Log::error("Warehouse Stocks export failed: " . $e->getMessage());
            return back()->with('error', 'Failed to generate Warehouse Stocks export.');
        }
    }
}
