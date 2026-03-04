@extends('layouts.app')

@section('content')
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">
                    {{ $shipment ? 'Edit Shipment' : 'Create Shipment' }}
                </h4>
            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">
                        <a href="{{ route('admin.shipments.index') }}">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back to Shipments
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Shipment Form -->
    <div class="card">
        <div class="card-body">
            <form action="{{ $shipment ? route('admin.shipments.update', $shipment->id) : route('admin.shipments.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @if ($shipment)
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- Shipment Name -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Shipment Name</label>
                        <input type="text" name="shipment_name"
                            class="form-control @error('shipment_name') is-invalid @enderror"
                            value="{{ old('shipment_name', $shipment->shipment_name ?? '') }}" required>
                        @error('shipment_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tracking Number -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tracking Number</label>
                        <input type="text" name="tracking_number"
                            class="form-control @error('tracking_number') is-invalid @enderror"
                            value="{{ old('tracking_number', $shipment->tracking_number ?? '') }}">
                        @error('tracking_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Carrier Name -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Carrier Name</label>
                        <input type="text" name="carrier_name"
                            class="form-control @error('carrier_name') is-invalid @enderror"
                            value="{{ old('carrier_name', $shipment->carrier_name ?? '') }}">
                        @error('carrier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Dispatch Date -->
                    @php
                        $carbon = \Carbon\Carbon::now();
                        $defaultDispatchDate = $carbon->format('Y-m-d');
                        $defaultExpectedArrival = $carbon->addWeeks(6)->format('Y-m-d');
                    @endphp

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Dispatch Date</label>
                        <input type="date" name="dispatch_date"
                            class="form-control @error('dispatch_date') is-invalid @enderror"
                            value="{{ old('dispatch_date', $shipment->dispatch_date ?? $defaultDispatchDate) }}">
                        @error('dispatch_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Expected Arrival -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expected Arrival</label>
                        <input type="date" name="expected_arrival"
                            class="form-control @error('expected_arrival') is-invalid @enderror"
                            value="{{ old('expected_arrival', $shipment->expected_arrival ?? $defaultExpectedArrival) }}">
                        @error('expected_arrival')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    <!-- Actual Arrival -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Actual Arrival</label>
                        <input type="date" name="actual_arrival"
                            class="form-control @error('actual_arrival') is-invalid @enderror"
                            value="{{ old('actual_arrival', $shipment->actual_arrival ?? '') }}">
                        @error('actual_arrival')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Supplier -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option value="">Select Supplier</option>
                            @foreach ($supplierUser as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id', $shipment->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>
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
                            <option value="">Select Warehouse</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    {{ old('warehouse_id', $shipment->warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @php
                        $statusColors = [
                            'planned' => '#0d6efd', // blue
                            'shipped' => '#0dcaf0', // cyan
                            'in_transit' => '#ffc107', // yellow
                            'received' => '#198754', // green
                            'cancelled' => '#dc3545', // red
                        ];
                    @endphp

                    <!-- Status -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="">Select Status</option>
                            @foreach (['planned', 'shipped', 'in_transit', 'received', 'cancelled'] as $status)
                                <option value="{{ $status }}" style="color: {{ $statusColors[$status] ?? '#000' }}"
                                    {{ old('status', $shipment->status ?? '') == $status ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>

                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @if (!$shipment)
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
                        <label class="form-label">Shipping Notes</label>
                        <textarea name="shipping_notes" rows="4" class="form-control @error('shipping_notes') is-invalid @enderror">{{ old('shipping_notes', $shipment->shipping_notes ?? '') }}</textarea>
                        @error('shipping_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="col-md-12 text-end">
                        <button type="submit"
                            class="btn btn-primary">{{ $shipment ? 'Update Shipment' : 'Create Shipment' }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
