<div class="offcanvas-body">
    <form method="POST" action="{{ route('admin.ads.performance.keywords.bulkBidUpdate', request()->query()) }}">
        @csrf

        <input type="hidden" name="bulkBid" value="true">

        <div class="form-group mb-2">
            <label class="fw-semibold mb-2">
                Keyword Bid Bulk Update
                <span class="text-muted fw-normal d-block small">
                    Only keywords selected in the filter will be updated.
                    Changes will apply to manual keyword bid values.
                </span>
            </label>

            <div class="row g-3">

                {{-- UPDATE TYPE --}}
                <div class="col-12">
                    <div class="form-floating">
                        <select class="form-select" id="bidAction" name="update_type">
                            <option value="increase">Increase Bid</option>
                            <option value="decrease">Decrease Bid</option>
                        </select>
                        <label for="bidAction">Update type</label>
                    </div>
                </div>

                {{-- PERCENTAGE --}}
                <div class="col-12">
                    <div class="form-floating">
                        <select class="form-select" id="bidPercent" name="percentage">
                            <option value="none">None (Reset manual bid)</option>
                            @foreach ([5, 10, 15, 20, 25, 30, 35, 40, 45, 50] as $p)
                                <option value="{{ $p }}">{{ $p }}%</option>
                            @endforeach
                        </select>
                        <label for="bidPercent">Select Percentage</label>
                    </div>
                </div>

                {{-- SUBMIT BUTTON --}}
                <div class="col-12">
                    <button type="submit" class="btn btn-success w-100 rounded-pill"
                        onclick="return confirm('Apply this bulk bid change to all selected keywords?');">
                        <i class="mdi mdi-percent-outline me-1"></i>
                        Apply Bulk Bid Update
                    </button>

                    <div class="text-muted small mt-2">
                        This updates manual keyword bids locally.
                        Use <strong>Make Live</strong> to push changes to Amazon Ads.
                    </div>
                </div>

            </div>
        </div>
    </form>

    {{-- DIVIDER --}}
    <hr class="my-4">

    {{-- BIG WARNING --}}
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
        <i class="mdi mdi-alert-outline fs-4"></i>
        <div>
            <div class="fw-bold mb-1">
                Warning: This action updates Amazon Ads directly
            </div>
            <div class="small">
                All keywords/rows you have
                <span class="fw-semibold">checked for update</span>
                will be pushed to the Amazon Ads console.
                <span class="fw-bold">This cannot be undone</span>.
                Please review carefully before continuing.
            </div>

            {{-- ACKNOWLEDGEMENT --}}
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="confirmMakeLive">
                <label class="form-check-label small" for="confirmMakeLive">
                    I understand this is not reversible.
                </label>
            </div>
        </div>
    </div>

    {{-- MAKE LIVE --}}
    @can('amazon-ads.keyword-performance.make-live')
        <div class="border rounded-pill px-3 py-2 d-flex align-items-center justify-content-between">

            <div>
                <div class="fw-semibold">Make Live</div>
                <div class="text-muted small">Apply to Amazon Ads</div>
            </div>

            <a onclick="
                if (!document.getElementById('confirmMakeLive')?.checked) {
                    alert('Please confirm: I understand this is not reversible.');
                    return false;
                }
                return confirm('Are you sure you want to update these keyword bid changes to live?');
            "
                href="{{ route('admin.ads.performance.keywords.keywordMakeLive', ['date' => request('date', $subdayTime)]) }}">

                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="mdi mdi-cloud-sync me-1"></i> Live
                </button>

            </a>

        </div>
    @endcan
</div>
