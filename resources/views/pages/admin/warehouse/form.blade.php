@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18"> {{ isset($editWarehouse) ? 'Edit Warehouse' : 'Create Warehouse' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.warehouse.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Warehouse
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

                    <h4 class="card-title">{{ isset($editWarehouse) ? 'Edit Warehouse' : 'Create Warehouse' }}</h4>
                    <form autocomplete="off" id="addWarehouseForm" novalidate method="POST"
                        action="{{ isset($editWarehouse) ? route('admin.warehouse.update', $editWarehouse->uuid) : route('admin.warehouse.createWarehouse') }}">
                        @csrf
                        @if (isset($editWarehouse))
                            @method('PUT')
                        @endif
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="warehouse_name" class="form-label">Warehouse Name <span
                                            class="text-danger fw-bold">*</span></label>
                                    <input type="text" id="warehouse_name" name="warehouse_name" class="form-control"
                                        placeholder="Enter Warehouse Name"
                                        value="{{ $editWarehouse->warehouse_name ?? '' }}"
                                        @if (isset($editWarehouse)) disabled @endif />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location
                                        <span class="text-danger fw-bold">*</span></label>
                                    <select name="location" id="location"
                                        class="form-select @error('country') is-invalid @enderror">
                                        <option value="" selected disabled>Select Country</option>
                                        @foreach ($countries as $code => $name)
                                            <option value="{{ $name }}"
                                                {{ (old('country') ?? ($editWarehouse->location ?? '')) === $name ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 text-end">
                                <button class="btn btn-success waves-effect waves-light btn-rounded" type="submit">
                                    {{ isset($editWarehouse) ? 'Update' : 'Create' }}
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
            <!-- end select2 -->
        </div>
    </div>
@endsection
