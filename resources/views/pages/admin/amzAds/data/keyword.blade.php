@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Amazon ADS - Keywords {{ $keywordType ?? 'SP' }}</h4>
                <div class="text-muted small">
                    <span class="fw-semibold">Updated at:</span>
                    <span>
                        {{ $lastUpdated
                            ? \Carbon\Carbon::parse($lastUpdated)->setTimezone('America/Los_Angeles')->format('Y-m-d h:i A T')
                            : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Nav Bar import --}}
                @include('pages.admin.amzAds.data_nav')

                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            @php
                                $keywordType ??= 'SP';
                                $action =
                                    $keywordType === 'SB' ? route('admin.ads.keywordsSb') : route('admin.ads.keywords');
                            @endphp
                            <!-- Filters -->
                            <form method="GET" action="{{ $action }}" class="row g-2" id="filterForm">
                                <!-- Search -->
                                <x-elements.search-box />
                                <!--Country Select-->
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select class="form-select custom-dropdown-small" name="statusFilter"
                                            id="statusFilter" onchange="document.getElementById('filterForm').submit()">
                                            <option value="enabled"
                                                {{ request('statusFilter', 'enabled') === 'enabled' ? 'selected' : '' }}>
                                                Enabled</option>
                                            <option value="paused"
                                                {{ request('statusFilter') === 'paused' ? 'selected' : '' }}>Paused</option>
                                            <option value="all"
                                                {{ request('statusFilter') === 'all' ? 'selected' : '' }}>All</option>
                                        </select>
                                        <label for="statusFilter">Campaign Status</label>
                                    </div>
                                </div>
                                {{-- Pending Updates Filter --}}
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select class="form-select custom-dropdown-small" name="updateFilter"
                                            onchange="document.getElementById('filterForm').submit()">
                                            <option value="" {{ request('updateFilter') == '' ? 'selected' : '' }}>All
                                            </option>
                                            <option value="pending"
                                                {{ request('updateFilter') == 'pending' ? 'selected' : '' }}>
                                                Pending</option>
                                        </select>
                                        <label for="updateFilter">Update Status</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2 mt-2 mt-lg-0">

                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Keyword ID</th>
                                    <th>Keyword</th>
                                    <th>Match Type</th>
                                    <th>Bid</th>
                                    <th>Campaign ID</th>
                                    <th>Ad Group ID</th>
                                    <th>Country</th>
                                    <th>State</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($keywords as $keyword)
                                    @php
                                        $update = $pendingUpdates[$keyword->keyword_id] ?? null;
                                    @endphp
                                    <tr class="odd {{ $update ? 'table-update' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="javascript:void(0);" class="openKeywordModal"
                                                data-id="{{ $keyword->keyword_id }}"
                                                data-keyword="{{ $keyword->keyword_text }}" data-bid="{{ $keyword->bid }}"
                                                data-adgroup="{{ $keyword->ad_group_id }}"
                                                data-campaign="{{ $keyword->campaign_id }}"
                                                data-type="{{ $keywordType }}"
                                                data-country="{{ strtoupper($keyword->country) }}"
                                                data-state="{{ strtoupper($keyword->state) }}">
                                                {{ $keyword->keyword_id }}
                                            </a>
                                        </td>
                                        <td>{{ $keyword->keyword_text ?? 'N/A' }}</td>
                                        <td>{{ $keyword->match_type ?? 'N/A' }}</td>
                                        <td>
                                            @if ($update && $update->new_bid != $keyword->bid)
                                                <span class="text-decoration-line-through text-danger">
                                                    {{ $keyword->bid }}
                                                </span>
                                                <span class="fw-bold text-success">
                                                    {{ $update->new_bid }}
                                                </span>
                                            @else
                                                {{ $keyword->bid ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ $keyword->campaign_id ?? 'N/A' }}</td>
                                        <td>{{ $keyword->ad_group_id ?? 'N/A' }}</td>
                                        <td>{{ strtoupper($keyword->country ?? 'N/A') }}</td>
                                        <td>
                                            {{-- If status changed, show old → new --}}
                                            @if ($update && strtoupper($keyword->state) !== $update->new_state)
                                                <small class="text-muted">
                                                    <span class="badge bg-success">{{ $keyword->state }}</span>
                                                    →
                                                    @if ($update->new_state === 'ENABLED')
                                                        <span class="badge bg-success">{{ $update->new_state }}</span>
                                                    @elseif ($update->new_state === 'PAUSED')
                                                        <span class="badge bg-warning">{{ $update->new_state }}</span>
                                                    @endif
                                                </small>
                                            @else
                                                @if (strtoupper($keyword->state) === 'ENABLED')
                                                    <span class="badge bg-success">{{ strtoupper($keyword->state) }}</span>
                                                @elseif (strtoupper($keyword->state) === 'PAUSED')
                                                    <span class="badge bg-warning">{{ strtoupper($keyword->state) }}</span>
                                                @elseif (strtoupper($keyword->state) === 'ARCHIVED')
                                                    <span class="badge bg-danger">{{ strtoupper($keyword->state) }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @can('amazon-ads.keyword-update')
                                                <a href="javascript:void(0);" class="openKeywordModal"
                                                    data-id="{{ $keyword->keyword_id }}"
                                                    data-keyword="{{ $keyword->keyword_text }}" data-bid="{{ $keyword->bid }}"
                                                    data-adgroup="{{ $keyword->ad_group_id }}"
                                                    data-campaign="{{ $keyword->campaign_id }}"
                                                    data-type="{{ $keywordType }}"
                                                    data-country="{{ strtoupper($keyword->country) }}"
                                                    data-state="{{ strtoupper($keyword->state) }}">
                                                    <i class="mdi mdi-book-edit font-size-16 text-success me-1"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No keyword data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        {{ $keywords->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Keyword Update Modal -->
    <div class="modal fade" id="keyword" tabindex="-1" aria-labelledby="keywordLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="keywordLabel">
                        Update Keyword <span id="keywordTypeLabel"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Mini header --}}
                <div class="px-3 mt-2">
                    <h6 class="text-muted mb-0" id="keywordName"></h6>
                </div>

                <div class="modal-body">
                    <form autocomplete="off" class="updateKeywordForm" id="updateKeywordForm" method="POST"
                        action="{{ route('admin.ads.keyword.update') }}">
                        @csrf
                        {{-- Hidden fields --}}
                        <input type="hidden" name="keyword_id" id="keyword_id">
                        <input type="hidden" name="ad_group_id" id="ad_group_id">
                        <input type="hidden" name="campaign_id" id="campaign_id">
                        <input type="hidden" name="keyword_type" id="keyword_type">

                        <div class="row">
                            <div class="col-lg-12">
                                <!-- Old Bid -->
                                <div class="mb-3">
                                    <label class="form-label">Old Bid</label>
                                    <div class="input-group">
                                        <div class="input-group-text">$</div>
                                        <input type="text" id="old_bid" class="form-control" disabled>
                                    </div>
                                </div>

                                <!-- Old State -->
                                <div class="mb-3">
                                    <label class="form-label">Old State</label>
                                    <input type="text" id="old_state" class="form-control" disabled>
                                </div>

                                <!-- New Bid -->
                                <div class="mb-3">
                                    <label for="new_bid" class="form-label">New Bid</label>
                                    <div class="input-group">
                                        <div class="input-group-text">$</div>
                                        <input type="number" step="0.01" id="new_bid" name="new_bid"
                                            class="form-control" placeholder="Enter New Bid" />
                                    </div>
                                    <div id="newBidError" class="text-danger small mt-1" style="display: none;"></div>
                                </div>

                                <!-- New State -->
                                <div class="mb-3">
                                    <label class="form-label">New State</label>
                                    <select name="new_state" id="new_state" class="form-select">
                                        <option value="">-- Select State --</option>
                                        <option value="ENABLED">Enabled</option>
                                        <option value="PAUSED">Paused</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Update</button>
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const modalEl = document.getElementById("keyword");
                const newBidInput = document.getElementById("new_bid");
                const oldBidInput = document.getElementById("old_bid");
                const submitBtn = document.querySelector("#updateKeywordForm button[type='submit']");
                const errorEl = document.getElementById("newBidError");

                document.querySelectorAll(".openKeywordModal").forEach(function(el) {
                    el.addEventListener("click", function() {
                        document.getElementById("keyword_id").value = this.dataset.id;
                        document.getElementById("ad_group_id").value = this.dataset.adgroup;
                        document.getElementById("campaign_id").value = this.dataset.campaign;
                        document.getElementById("keyword_type").value = this.dataset.type;

                        // set old values
                        oldBidInput.value = this.dataset.bid ?? "N/A";
                        document.getElementById("old_state").value = this.dataset.state ?? "N/A";
                        document.getElementById("keywordName").innerText = this.dataset.keyword ??
                            "N/A";

                        // reset
                        newBidInput.value = "";
                        document.getElementById("new_state").value = "";
                        errorEl.style.display = "none";
                        submitBtn.disabled = true;

                        new bootstrap.Modal(modalEl).show();

                        // disable bid input if old bid = 0
                        if (parseFloat(this.dataset.bid) === 0) {
                            newBidInput.disabled = true;
                            newBidInput.placeholder = "Bid is 0 (cannot be increased)";
                        } else {
                            newBidInput.disabled = false;
                        }
                    });
                });

                function validateBid() {
                    const oldBid = parseFloat(oldBidInput.value) || 0;
                    const newBid = parseFloat(newBidInput.value) || 0;

                    if (newBidInput.disabled) {
                        submitBtn.disabled = false; // allow state change
                        return;
                    }

                    if (!newBid) {
                        submitBtn.disabled = true;
                        errorEl.style.display = "none";
                        return;
                    }

                    if (newBid > oldBid * 2) {
                        errorEl.innerText =
                            `New bid cannot be more than double the old bid ($${(oldBid * 2).toFixed(2)}).`;
                        errorEl.style.display = "block";
                        submitBtn.disabled = true;
                    } else {
                        errorEl.style.display = "none";
                        submitBtn.disabled = false;
                    }
                }

                newBidInput.addEventListener("input", validateBid);
            });
        </script>
    @endpush
@endsection
