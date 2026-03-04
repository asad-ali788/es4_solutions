@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    {{ isset($rule) ? 'Edit' : 'Create' }} Target Recommendation Rule
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.ads.performance.rules.target.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Target Rules
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
                    <h4 class="card-title">{{ isset($rule) ? 'Edit' : 'Create' }} Target Rule</h4>
                    <p class="card-title-desc">Define thresholds for CTR, Conversion, ACoS, and budget adjustment</p>
                    <form
                        action="{{ isset($rule) ? route('admin.ads.performance.rules.target.update', $rule->id) : route('admin.ads.performance.rules.target.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($rule))
                            @method('PUT')
                        @endif

                        <div class="row">
                            {{-- CTR FIELDS --}}
                            @if (!isset($rule) || in_array('min_ctr', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Min CTR (%)</label>
                                    <input type="number" step="0.01" name="min_ctr" class="form-control"
                                        value="{{ old('min_ctr', $rule->min_ctr ?? '') }}">
                                    @error('min_ctr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            @if (!isset($rule) || in_array('max_ctr', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Max CTR (%)</label>
                                    <input type="number" step="0.01" name="max_ctr" class="form-control"
                                        value="{{ old('max_ctr', $rule->max_ctr ?? '') }}">
                                    @error('max_ctr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            {{-- CONVERSION FIELDS --}}
                            @if (!isset($rule) || in_array('min_conversion_rate', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Min Conversion Rate (%)</label>
                                    <input type="number" step="0.01" name="min_conversion_rate" class="form-control"
                                        value="{{ old('min_conversion_rate', $rule->min_conversion_rate ?? '') }}">
                                    @error('min_conversion_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            @if (!isset($rule) || in_array('max_conversion_rate', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Max Conversion Rate (%)</label>
                                    <input type="number" step="0.01" name="max_conversion_rate" class="form-control"
                                        value="{{ old('max_conversion_rate', $rule->max_conversion_rate ?? '') }}">
                                    @error('max_conversion_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            {{-- ACOS FIELDS --}}
                            @if (!isset($rule) || in_array('min_acos', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Min ACoS (%)</label>
                                    <input type="number" step="0.01" name="min_acos" class="form-control"
                                        value="{{ old('min_acos', $rule->min_acos ?? '') }}">
                                    @error('min_acos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            @if (!isset($rule) || in_array('max_acos', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Max ACoS (%)</label>
                                    <input type="number" step="0.01" name="max_acos" class="form-control"
                                        value="{{ old('max_acos', $rule->max_acos ?? '') }}">
                                    @error('max_acos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            {{-- OTHER METRICS --}}
                            @if (!isset($rule) || in_array('min_clicks', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Min Clicks</label>
                                    <input type="number" name="min_clicks" class="form-control"
                                        value="{{ old('min_clicks', $rule->min_clicks ?? '') }}">
                                    @error('min_clicks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            @if (!isset($rule) || in_array('min_sales', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Min Sales</label>
                                    <input type="number" name="min_sales" class="form-control"
                                        value="{{ old('min_sales', $rule->min_sales ?? '') }}">
                                    @error('min_sales')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            @if (!isset($rule) || in_array('min_impressions', $activeFields ?? []))
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Min Impressions</label>
                                    <input type="number" name="min_impressions" class="form-control"
                                        value="{{ old('min_impressions', $rule->min_impressions ?? '') }}">
                                    @error('min_impressions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            {{-- COMMON FIELDS --}}
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Action Label</label>
                                <input type="text" name="action_label" class="form-control"
                                    value="{{ old('action_label', $rule->action_label ?? '') }}" required>
                                @error('action_label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Adjustment Type</label>
                                <select name="adjustment_type" class="form-select" required>
                                    <option value="keep"
                                        {{ old('adjustment_type', $rule->adjustment_type ?? '') == 'keep' ? 'selected' : '' }}>
                                        Keep</option>
                                    <option value="increase"
                                        {{ old('adjustment_type', $rule->adjustment_type ?? '') == 'increase' ? 'selected' : '' }}>
                                        Increase</option>
                                    <option value="decrease"
                                        {{ old('adjustment_type', $rule->adjustment_type ?? '') == 'decrease' ? 'selected' : '' }}>
                                        Decrease</option>
                                </select>
                                @error('adjustment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Adjustment Value (%)</label>
                                <input type="number" step="0.01" name="adjustment_value" class="form-control"
                                    value="{{ old('adjustment_value', $rule->adjustment_value ?? '') }}">
                                @error('adjustment_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select" required>
                                    <option value="1"
                                        {{ old('is_active', $rule->is_active ?? 1) == 1 ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="0"
                                        {{ old('is_active', $rule->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($rule) ? 'Update Rule' : 'Create Rule' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
