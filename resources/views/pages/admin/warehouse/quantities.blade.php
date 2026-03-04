@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Warehouse Qty - <span class="text-success">
                        {{ $warehouse->warehouse_name }}</span> </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.warehouse.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Warehouses
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2 align-items-center justify-content-between">
                        <!-- Left: Search + Dropdown -->
                        <div class="col-md-6 d-flex align-items-center">
                            <!-- Search -->
                            <form method="GET" action="{{ route('admin.warehouse.quantities', $warehouse->uuid) }}"
                                class="d-flex me-2 w-50">
                                <!-- Search -->
                                <x-elements.search-box />
                            </form>
                        </div>
                        <!-- Right: Two Buttons -->
                        <div class="col-md-6 text-end">
                            <button class="btn btn-primary btn-rounded" data-bs-toggle="modal"
                                data-bs-target="#importModal">
                                <i class="mdi mdi-upload"></i> Import Inventory
                            </button>

                            <a href="{{ route('admin.warehouse.inventoryForm', ['id' => $warehouse->id]) }}"
                                class="btn btn-success waves-effect waves-light me-2 btn-rounded">
                                <i class="mdi mdi-plus"></i> Add Inventory
                            </a>
                        </div>

                    </div>


                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover" id="inventory-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name (SKU)</th>
                                    <th>Quantity</th>
                                    <th>Available Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @if ($inventories->count() > 0)
                                    @foreach ($inventories as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $item->product->sku ?? '' }}
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->available_quantity }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="mdi mdi-dots-horizontal font-size-18 text-success"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="{{ route('admin.warehouse.inventoryForm', ['id' => $warehouse->id, 'inventoryId' => $item->id]) }}"
                                                                class="dropdown-item">
                                                                <i
                                                                    class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form method="POST"
                                                                action="{{ route('admin.warehouse.deleteInventory', $item->id) }}"
                                                                onsubmit="return confirm('Are you sure you want to delete this inventory?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="mdi mdi-delete font-size-16 me-1"></i>
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center">No inventory found for this warehouse.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                    </div>
                    <div class="mt-2">
                        @if (method_exists($inventories, 'links'))
                            {{ $inventories->appends(request()->query())->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            {{-- <form method="POST" action="{{ route('admin.warehouse.importInventory', $warehouse->uuid) }}" --}}
            <form method="POST" action="{{ route('admin.warehouse.importInventory', $warehouse->uuid) }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Inventory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" class="form-control" name="warehouse_id" value="{{ $warehouse->id }}">

                        <div class="mb-3">
                            <label for="import_file" class="form-label">Upload Excel File</label>
                            <input type="file" class="form-control" name="import_file" required accept=".csv,.xlsx,.xls">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
