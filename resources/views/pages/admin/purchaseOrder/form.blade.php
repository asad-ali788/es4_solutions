@extends('layouts.app')

@section('content')
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">
                    {{ $purchaseOrder ? 'Edit Order' : 'Create Order' }}
                </h4>
            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">
                        <a href="{{ route('admin.purchaseOrder.index') }}">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back to Purchase Orders
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Shipment Form -->
    <div class="card">
        <div class="card-body">
            <form id="purchaseOrderForm"
                action="{{ $purchaseOrder ? route('admin.purchaseOrder.update', $purchaseOrder->id) : route('admin.purchaseOrder.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @if ($purchaseOrder)
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- Order Number -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Order Number</label>
                        <input type="text" name="order_number"
                            class="form-control @error('order_number') is-invalid @enderror"
                            value="{{ old('order_number', $purchaseOrder->order_number ?? '') }}" required>
                        @error('order_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Supplier -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option value="" selected disabled>Select Supplier</option>
                            @foreach ($supplierUser as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id', $purchaseOrder->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Warehouse -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                            <option value="" selected disabled>Select Warehouse</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    {{ old('warehouse_id', $purchaseOrder->warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Order Date -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Order Date</label>
                        <input type="date" name="order_date"
                            class="form-control @error('order_date') is-invalid @enderror"
                            value="{{ old('order_date', isset($purchaseOrder->order_date) ? \Carbon\Carbon::parse($purchaseOrder->order_date)->format('Y-m-d') : '') }}">

                        @error('order_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Expected Arrival -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expected Arrival</label>
                        <input type="date" name="expected_arrival"
                            class="form-control @error('expected_arrival') is-invalid @enderror"
                            value="{{ old('expected_arrival', $purchaseOrder->expected_arrival ?? '') }}">
                        @error('expected_arrival')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Payment Terms -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Terms</label>
                        <select name="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
                            <option value="" selected disabled>-- Select Payment Terms --</option>
                            @foreach (['Advance', 'Net 15', 'Net 30', 'Net 45', 'COD'] as $term)
                                <option value="{{ $term }}"
                                    {{ old('payment_terms', $purchaseOrder->payment_terms ?? '') == $term ? 'selected' : '' }}>
                                    {{ $term === 'COD' ? 'Cash on Delivery (COD)' : $term }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_terms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Shipping Method -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Shipping Method</label>
                        <input type="text" name="shipping_method"
                            class="form-control @error('shipping_method') is-invalid @enderror"
                            value="{{ old('shipping_method', $purchaseOrder->shipping_method ?? '') }}">
                        @error('shipping_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="" disabled
                                {{ old('status', $purchaseOrder->status ?? '') == '' ? 'selected' : '' }}>Select Status
                            </option>
                            @foreach (['draft', 'confirmed', 'shipped', 'received', 'cancelled'] as $status)
                                <option value="{{ $status }}"
                                    {{ old('status', $purchaseOrder->status ?? '') == $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    @if (!$purchaseOrder)
                        <!-- Excel File -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Excel File</label>
                            <input type="file" name="excel_file" class="form-control">
                            @if (!empty($shipment->excel_file))
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $shipment->excel_file) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">View File</a>
                                </div>
                            @endif
                            <a href="{{ asset('assets/example/example_shipmentUpload.xlsx') }}" download>
                                <i class="mdi mdi-download me-1 mt-3"></i> Download Example Excel
                            </a>
                        </div>
                    @endif

                    <!-- Notes -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Purchase Notes</label>
                        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $purchaseOrder->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="col-md-12 text-end">
                        <button type="submit"
                            class="btn btn-primary">{{ $purchaseOrder ? 'Update Order' : 'Create Order' }}</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection
