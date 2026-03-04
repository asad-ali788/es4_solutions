@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    {{ isset($rule) ? 'Edit' : 'Create' }} Budget Recommendation Rule
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.ads.performance.rules.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Rules
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
                    <h4 class="card-title">{{ isset($rule) ? 'Edit' : 'Create' }} Rule</h4>
                    <p class="card-title-desc">Define ACOS range, spend condition, and budget adjustment</p>
                    <form
                        action="{{ isset($rule) ? route('admin.ads.performance.rules.update', $rule->id) : route('admin.ads.performance.rules.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($rule))
                            @method('PUT')
                        @endif
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Left Side -->
                                <div class="mb-3">
                                    <label class="form-label">Min ACOS</label>
                                    <input type="number" step="0.01" name="min_acos" class="form-control"
                                        value="{{ old('min_acos', $rule->min_acos ?? '') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Max ACOS</label>
                                    <input type="number" step="0.01" name="max_acos" class="form-control"
                                        value="{{ old('max_acos', $rule->max_acos ?? '') }}">
                                    <small class="text-muted">Leave empty for no upper limit</small>
                                </div>
                                <div class="mb-3">
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
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Adjustment Value (%)</label>
                                    <input type="number" step="0.01" name="adjustment_value" class="form-control"
                                        value="{{ old('adjustment_value', $rule->adjustment_value ?? '') }}">
                                    <small class="text-muted">Percentage to increase/decrease. Leave empty if type is
                                        "Keep".</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Right Side -->
                                <div class="mb-3">
                                    <label class="form-label">Condition</label>
                                    <select name="spend_condition" class="form-select" required>
                                        <option value="any"
                                            {{ old('spend_condition', $rule->spend_condition ?? '') == 'any' ? 'selected' : '' }}>
                                            Any</option>
                                        <option value="gte_budget"
                                            {{ old('spend_condition', $rule->spend_condition ?? '') == 'gte_budget' ? 'selected' : '' }}>
                                            Spend ≥ Daily Budget</option>
                                        <option value="lt_budget"
                                            {{ old('spend_condition', $rule->spend_condition ?? '') == 'lt_budget' ? 'selected' : '' }}>
                                            Spend < Daily Budget</option>
                                        <option value="spend_zero"
                                            {{ old('spend_condition', $rule->spend_condition ?? '') == 'spend_zero' ? 'selected' : '' }}>
                                            Spend > 0</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Recommendation</label>
                                    <input type="text" name="action_label" class="form-control"
                                        value="{{ old('action_label', $rule->action_label ?? '') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <input type="number" name="priority" class="form-control"
                                        value="{{ old('priority', $rule->priority ?? 1) }}" required>
                                    <small class="text-muted">Rules are applied in order of priority (lower = first)</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="is_active" class="form-select" required>
                                        <option value="1"
                                            {{ old('is_active', $rule->is_active ?? 1) == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0"
                                            {{ old('is_active', $rule->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive
                                        </option>
                                    </select>
                                </div>
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
