@extends('layouts.app')

@section('content')
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">
                    {{ $forecast ? 'Edit Forecast' : 'Create Forecast' }}
                </h4>
            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">
                        <a href="{{ route('admin.orderforecast.index') }}">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back to Forecasts
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Forecast Form -->
    <div class="card">
        <div class="card-body">
            <form
                action="{{ $forecast ? route('admin.orderforecast.update', $forecast->id) : route('admin.orderforecast.store') }}"
                method="POST" id="forcastForm">

                @csrf
                @if ($forecast)
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- Order Name -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Forecast Name <span class="text-danger fw-bold">*</span></label>
                        <input type="text" name="order_name"
                            class="form-control @error('order_name') is-invalid @enderror"
                            value="{{ old('order_name', $forecast->order_name ?? '') }}" required>
                        @error('order_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Order Date -->
                    @php
                        $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
                    @endphp

                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            Forecast Date <span class="text-danger fw-bold">*</span>
                        </label>

                        @if (!empty($forecast))
                            <input type="date" value="{{ old('order_date', $forecast->order_date ?? $subdayTime) }}"
                                class="form-control" disabled>

                            <input type="hidden" name="order_date"
                                value="{{ old('order_date', $forecast->order_date ?? $subdayTime) }}">
                        @else
                            <input type="date" name="order_date"
                                class="form-control @error('order_date') is-invalid @enderror"
                                value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}" required>
                        @endif

                        @error('order_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    @php
                        $statusOptions = ['draft', 'finalized', 'archived'];
                        $statusColors = [
                            'draft' => '#0d6efd',
                            'finalized' => '#198754',
                            'archived' => '#dc3545',
                        ];
                    @endphp

                    @if ($forecast)
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status <span class="text-danger fw-bold">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="" disabled>Select Status</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}"
                                        style="color: {{ $statusColors[$status] ?? '#000' }}"
                                        {{ old('status', $forecast->status) == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif


                    <!-- Notes -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $forecast->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="col-md-12 text-end">
                        <button type="submit"
                            class="btn btn btn-success waves-effect waves-light btn-rounded" data-loading-false>{{ $forecast ? 'Update Forecast' : 'Create Forecast' }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
