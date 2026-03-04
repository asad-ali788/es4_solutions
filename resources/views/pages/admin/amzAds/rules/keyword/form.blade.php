@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ isset($rule) ? 'Edit' : 'Create' }} Keyword Rule</h4>
                <div class="page-title-right">
                    <a href="{{ route('admin.ads.performance.rules.keyword.index') }}" class="text-dark">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back to Rules
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form
                        action="{{ isset($rule) ? route('admin.ads.performance.rules.keyword.update', $rule->id) : route('admin.ads.performance.rules.keyword.store') }}"
                        method="POST" id="keywordRuleForm">
                        @csrf
                        @if (isset($rule))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <!-- Left Column: Conditions -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">CTR Condition (%)</label>
                                    <input type="number" step="0.01" name="ctr_condition" class="form-control"
                                        value="{{ old('ctr_condition', $rule->ctr_condition ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Conversion Condition (%)</label>
                                    <input type="number" step="0.01" name="conversion_condition" class="form-control"
                                        value="{{ old('conversion_condition', $rule->conversion_condition ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ACoS Condition (%)</label>
                                    <input type="number" step="0.01" name="acos_condition" class="form-control"
                                        value="{{ old('acos_condition', $rule->acos_condition ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Click Condition</label>
                                    <input type="number" name="click_condition" class="form-control"
                                        value="{{ old('click_condition', $rule->click_condition ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Sales Condition</label>
                                    <input type="number" step="0.01" name="sales_condition" class="form-control"
                                        value="{{ old('sales_condition', $rule->sales_condition ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Impressions Condition</label>
                                    <input type="number" name="impressions_condition" class="form-control"
                                        value="{{ old('impressions_condition', $rule->impressions_condition ?? '') }}">
                                </div>
                            </div>

                            <!-- Right Column: Recommendation and Settings -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Recommendation</label>
                                    <input type="text" name="action_label" class="form-control"
                                        value="{{ old('action_label', $rule->action_label ?? '') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Adjustment Value (%)</label>
                                    <input type="number" step="0.01" name="bid_adjustment" id="bidAdjustmentInput"
                                        class="form-control"
                                        value="{{ old('bid_adjustment', $rule->bid_adjustment ?? '') }}">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="pauseCheckbox"
                                            {{ old('bid_adjustment', $rule->bid_adjustment ?? '') === '❌ Pause' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="pauseCheckbox">
                                            Pause keyword (❌ Pause)
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Enter a numeric multiplier (e.g., 1.15 for +15%, 0.90 for -10%) or check the box to
                                        pause the keyword.
                                    </small>
                                </div>

                                {{-- <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <input type="number" name="priority" class="form-control"
                                        value="{{ old('priority', $rule->priority ?? 1) }}">
                                </div> --}}

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
                            <button type="submit"
                                class="btn btn-primary">{{ isset($rule) ? 'Update Rule' : 'Create Rule' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                var $checkbox = $('#pauseCheckbox');
                var $input = $('#bidAdjustmentInput');

                // Initialize input state based on checkbox
                if ($checkbox.is(':checked')) {
                    $input.val('❌ Pause').prop('readonly', true);
                }

                // Toggle input value and readonly on checkbox change
                $checkbox.change(function() {
                    if ($(this).is(':checked')) {
                        $input.val('❌ Pause').prop('readonly', true);
                    } else {
                        $input.val('').prop('readonly', false);
                    }
                });
            });
        </script>
    @endpush
@endsection
