@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    {{ isset($editInventory) ? 'Edit Inventory' : 'Create Inventory' }} -
                    <span class="text-success">{{ $warehouse->warehouse_name }}</span>
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.warehouse.quantities', $warehouse->uuid) }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Warehouse inventory
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    <h4 class="card-title">{{ isset($editInventory) ? 'Edit Inventory' : 'Create Inventory' }}</h4>
                    <form autocomplete="off" id="addWarehouseForm" novalidate method="POST"
                        action="{{ isset($editInventory) ? route('admin.warehouse.editInventory', $editInventory->id) : route('admin.warehouse.addInventory') }}">
                        @csrf
                        @if (isset($editInventory))
                            @method('PUT')
                        @endif

                        {{-- Hidden warehouse_id --}}
                        <input type="hidden" name="warehouse_id"
                            value="{{ $warehouse->id ?? ($editInventory->warehouse_id ?? '') }}">

                        <div class="row">
                            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">
                                        Product <span class="text-danger fw-bold">*</span>
                                    </label>

                                    <select name="product_id" id="product_id" class="form-select" required
                                        @if ($editInventory) disabled @endif>
                                        <option value="" disabled
                                            {{ empty(old('product_id')) && empty($editInventory) ? 'selected' : '' }}>
                                            Select Product
                                        </option>

                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ (old('product_id') ?? ($editInventory->product_id ?? '')) == $product->id ? 'selected' : '' }}>
                                                {{ $product->sku }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if ($editInventory)
                                        <input type="hidden" name="product_id" value="{{ $editInventory->product_id }}">
                                    @endif
                                </div>
                            </div>


                            {{-- Quantity --}}
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity <span
                                            class="text-danger fw-bold">*</span></label>
                                    <input type="number" id="quantity" name="quantity" class="form-control"
                                        placeholder="Enter Quantity"
                                        value="{{ old('quantity', $editInventory->quantity ?? '') }}">
                                </div>
                            </div>

                            {{-- Reserved Quantity --}}
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="reserved_quantity" class="form-label">Reserved Quantity</label>
                                    <input type="number" id="reserved_quantity" name="reserved_quantity"
                                        class="form-control" placeholder="Enter Reserved Quantity"
                                        value="{{ old('reserved_quantity', $editInventory->reserved_quantity ?? '') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Submit + Cancel --}}
                        <div class="col-12 text-end">
                            <button class="btn btn-success waves-effect waves-light btn-rounded" type="submit">
                                {{ isset($editInventory) ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>


                </div>
            </div>
            <!-- end select2 -->
        </div>
    </div>
@endsection
