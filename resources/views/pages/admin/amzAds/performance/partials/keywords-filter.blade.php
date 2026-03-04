<div class="offcanvas-body">
    <h5 class="mb-3">Recommendation Filter</h5>
    @foreach ($ruleFilter as $rule)
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="rules[]" value="{{ $rule->id }}"
                id="rule_{{ $rule->id }}" {{ in_array($rule->id, request('rules', [])) ? 'checked' : '' }}>
            <label class="form-check-label" for="rule_{{ $rule->id }}">
                {{ $rule->action_label }}
            </label>
        </div>
    @endforeach

    <div class="mb-3">
        <label class="form-label">Asin Search</label>
        <select name="asins[]" class="form-select asin-select" multiple="multiple" style="width: 100%;">
            @foreach ($filteredAsin as $asin)
                <option value="{{ $asin }}" selected>
                    {{ $asin }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Run Status Filter --}}
    <div class="form-group mb-2">
        <label for="run_status" class="me-2 fw-semibold">Status:</label>
        <select name="run_status" id="run_status" class="form-select form-select">
            <option value="">All</option>
            <option value="pending" {{ request('run_status') == 'pending' ? 'selected' : '' }}>Pending
            </option>
            <option value="dispatched" {{ request('run_status') == 'dispatched' ? 'selected' : '' }}>
                Dispatched</option>
            <option value="failed" {{ request('run_status') == 'failed' ? 'selected' : '' }}>Failed
            </option>
            <option value="done" {{ request('run_status') == 'done' ? 'selected' : '' }}>Done
            </option>
        </select>
    </div>
    {{-- READY TO MAKE LIVE FILTER --}}
    <div class="form-group mb-2">
        <label for="run_update" class="me-2 fw-semibold">Make Live:</label>
        <select name="run_update" id="run_update" class="form-select form-select">
            <option value="">All</option>
            <option value="1" {{ request('run_update') == '1' ? 'selected' : '' }}>Ready to Make
                Live (checked)</option>
            <option value="0" {{ request('run_update') == '0' ? 'selected' : '' }}>Not Ready (not
                checked)
            </option>
        </select>
    </div>

    {{-- KEYWORD STATE FILTER --}}
    <div class="form-group mb-2">
        <label for="keyword_state" class="me-2 fw-semibold">Keyword State:</label>
        <select name="keyword_state" id="keyword_state" class="form-select form-select">
            <option value="">All</option>
            <option value="enabled" {{ request('keyword_state') == 'enabled' ? 'selected' : '' }}>
                Enabled</option>
            <option value="paused" {{ request('keyword_state') == 'paused' ? 'selected' : '' }}>Paused
            </option>
            {{-- <option value="archived"
                                                        {{ request('keyword_state') == 'archived' ? 'selected' : '' }}>
                                                        Archived</option> --}}
            <option value="na" {{ request('keyword_state') == 'na' ? 'selected' : '' }}>N/A
            </option>
        </select>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1 mt-3">
            Apply Filters
        </button>
        <a class="btn btn-outline-secondary w-50 mt-3" onclick="clearFilters()">
            <i class="mdi mdi-filter-remove"></i> Clear Filters
        </a>
    </div>
</div>

{{-- Modal pop up for the Column hide and show --}}

<div class="modal fade" id="columnPopupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Show / Hide Columns</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- Body -->
            <div class="modal-body pt-2">
                <div class="card jobs-categories mb-0">
                    <div class="card-body p-2 form-check">
                        <!-- Column Item -->
                        <label class="px-3 py-2 rounded bg-light bg-opacity-50 d-block mb-2 cursor-pointer">
                            Campaign ID
                            <input type="checkbox" class="form-check-input font-size-16 float-end mt-1"
                                data-column-toggle data-col="campaign_id">
                        </label>
                        <!-- Column Item -->
                        <label class="px-3 py-2 rounded bg-light bg-opacity-50 d-block mb-2 cursor-pointer">
                            Date
                            <input type="checkbox" class="form-check-input font-size-16 float-end mt-1"
                                data-column-toggle data-col="date">
                        </label>

                    </div>
                </div>
            </div>
            <!-- Footer -->

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>

                <button type="button" class="btn btn-sm btn-primary" id="btnApplyColumns" data-bs-dismiss="modal">
                    Submit
                </button>
            </div>
        </div>
    </div>
</div>
