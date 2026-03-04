@extends('layouts.app')

@section('content')
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">{{ $shipmentItem ? 'Edit Inbound Item' : 'Add Inbound Item' }}
                    <span class="text-success"> -
                        {{ $shipment->shipment_name ?? 'No Items available for the Shipment Items' }}
                    </span>
                </h4>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">
                        <a href="{{ route('admin.shipments.items', $shipment->id) }}">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back to Shipments Items
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    </div>
    <div class="col-8">
        <!-- Form Card -->
        <div class="card">
            <div class="card-body">
                <form
                    action="{{ $shipmentItem ? route('admin.shipments.itemUpdate', $shipmentItem->id) : route('admin.shipments.itemStore') }}"
                    method="POST">
                    @csrf
                    @if ($shipmentItem)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="inbound_shipment_id" value="{{ $shipment->id ?? 'null' }}">
                    <div class="row">

                        <!-- Product (half width) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product</label>
                            <select name="product_id" id="productSelect"
                                class="form-select @error('product_id') is-invalid @enderror" style="width: 100%;">
                                <option value="">Select Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                        {{ old('product_id', $shipmentItem->product_id ?? '') == $product->id ? 'selected' : '' }}>
                                        {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @php
                            $statusColors = [
                                'pending' => '#ffc107', // yellow
                                'damaged' => '#e30733', // red
                                'received' => '#198754', // green
                                'short' => '#4f34eb', // blue
                            ];
                        @endphp

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">Select Status</option>
                                @foreach (['pending', 'damaged', 'received', 'short'] as $status)
                                    <option value="{{ $status }}"
                                        style="color: {{ $statusColors[$status] ?? '#000' }}"
                                        {{ old('status', $shipmentItem->status ?? '') == $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>

                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="row">
                        <!-- Quantity Ordered (one-third width) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity Ordered</label>
                            <input type="number" name="quantity_ordered"
                                class="form-control @error('quantity_ordered') is-invalid @enderror"
                                value="{{ old('quantity_ordered', $shipmentItem->quantity_ordered ?? '') }}">
                            @error('quantity_ordered')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Quantity Received (one-third width) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity Received</label>
                            <input type="number" name="quantity_received"
                                class="form-control @error('quantity_received') is-invalid @enderror"
                                value="{{ old('quantity_received', $shipmentItem->quantity_received ?? '') }}">
                            @error('quantity_received')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Empty space (one-third width) for balance -->
                        <div class="col-md-4 mb-3"></div>
                    </div>

                    <div class="row">
                        <!-- Submit button full width and aligned right -->
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                {{ $shipmentItem ? 'Update Item' : 'Save Item' }}
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#productSelect').select2({
                    placeholder   : "Select Product",
                    allowClear    : true,
                    width         : 'resolve',
                    dropdownParent: $('#productSelect').parent()
                });
            });
        </script>
    @endpush
@endsection
