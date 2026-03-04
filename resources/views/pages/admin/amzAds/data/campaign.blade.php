@extends('layouts.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0">Amazon ADS - Campaigns {{ $campaignType ?? 'SP' }}</h4>
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
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Nav Bar import --}}
                @include('pages.admin.amzAds.data_nav')

                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">

                        <div class="col-lg-9">
                            {{-- Search Form --}}
                            @php
                                $campaignType ??= 'SP';
                                $action = match ($campaignType) {
                                    'SB' => route('admin.ads.campaignsSb'),
                                    'SD' => route('admin.ads.campaignsSd'),
                                    default => route('admin.ads.campaigns'),
                                };
                            @endphp
                            <form method="GET" action="{{ $action }}" id="filterForm" class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                                <input type="hidden" name="type" value="{{ $campaignType }}">

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
                            @can('amazon-ads.data.campaign-create')
                                <div class="flex-fill flex-lg-none" style="min-width: 0;">
                                    @if ($campaignType == 'SP')
                                        <a href="{{ route('admin.ads.campaigns.create', $campaignType) }}">
                                            <button
                                                class="btn btn-success btn-rounded waves-effect waves-light w-100 addCustomers-modal">
                                                <i class="mdi mdi-plus me-1"></i>New Campaign</button>
                                        </a>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover"
                            id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Campaign Id</th>
                                    <th style="width: 250px;">Campaign Name</th>
                                    <th>Country</th>
                                    <th>Daily Budget $</th>
                                    <th>Campaign Status</th>
                                    <th>Schedule Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($campaigns) && $campaigns->count() > 0)
                                    @foreach ($campaigns as $campaign)
                                        @php
                                            $update = $pendingUpdates[$campaign->campaign_id] ?? null;
                                        @endphp

                                        <tr class="odd {{ $update ? 'table-update' : '' }}">
                                            <td>{{ $loop->iteration }}</td>
                                            {{-- Campaign ID clickable --}}
                                            @php
                                                $tooltip = [];
                                                if ($campaign->keywords_exists) {
                                                    $tooltip[] = 'keywords exist';
                                                }
                                                if ($campaign->target_sb_exists) {
                                                    $tooltip[] = 'Sponsored Brands targets exist';
                                                }
                                            @endphp
                                            <td>
                                                <a href="javascript:void(0);" class="openCampaignModal"
                                                    data-id="{{ $campaign->campaign_id }}"
                                                    data-type="{{ $campaign->campaign_type }}"
                                                    data-budget="{{ $campaign->daily_budget }}"
                                                    data-name="{{ $campaign->campaign_name }}"
                                                    data-status="{{ $campaign->campaign_state }}">
                                                    {{ $campaign->campaign_id ?? 'N/A' }}
                                                    @if ($tooltip)
                                                        <i class="mdi mdi-alpha-k-circle-outline text-warning font-size-16"
                                                            title="{{ implode(' | ', $tooltip) }}"></i>
                                                    @endif
                                                </a>
                                            </td>
                                            {{-- Name --}}
                                            <td class="ellipsis-text" title="{{ $campaign->campaign_name ?? '' }}">
                                                {{ $campaign->campaign_name ?? 'N/A' }}
                                            </td>
                                            {{-- Country --}}
                                            <td>{{ $campaign->country ?? 'N/A' }}</td>
                                            {{-- Budget with pending update highlight --}}
                                            <td>
                                                @if ($update && $update->new_budget != $campaign->daily_budget)
                                                    <span class="text-decoration-line-through text-danger">
                                                        {{ $campaign->daily_budget }}
                                                    </span>
                                                    <span class="fw-bold text-success">
                                                        {{ $update->new_budget }}
                                                    </span>
                                                @else
                                                    {{ $campaign->daily_budget ?? 'N/A' }}
                                                @endif
                                            </td>
                                            {{-- Status with pending update highlight --}}
                                            <td>
                                                @php
                                                    $currentState = Str::upper($campaign->campaign_state);
                                                    $newState = $update ? Str::upper($update->new_status) : null;

                                                    $badgeClass = match ($currentState) {
                                                        'PAUSED'   => 'bg-warning',     // always yellow
                                                        'ENABLED'  => 'bg-success',     // always green
                                                        'ARCHIVED' => 'bg-danger',
                                                        default    => 'bg-secondary',
                                                    };
                                                @endphp

                                                {{-- If there is a pending status change --}}
                                                @if ($update && $currentState !== $newState)
                                                    <small class="text-muted">
                                                        {{-- Current status (color based on CURRENT state only) --}}
                                                        <span class="badge {{ $badgeClass }}">
                                                            {{ $currentState }}
                                                        </span>
                                                        →
                                                        {{-- New status (color based on NEW state) --}}
                                                        <span
                                                            class="badge {{ $newState === 'PAUSED' ? 'bg-warning' : ($newState === 'ENABLED' ? 'bg-success' : 'bg-danger') }}">
                                                            {{ $newState }}
                                                        </span>
                                                    </small>
                                                @else
                                                    {{-- No pending update: show current status only --}}
                                                    <span class="badge {{ $badgeClass }}">
                                                        {{ $currentState }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if (isset($scheduleUpdates[$campaign->campaign_id]))
                                                    @php
                                                        $schedule = $scheduleUpdates[$campaign->campaign_id];
                                                    @endphp
                                                    <span
                                                        class="badge {{ $schedule->run_status ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $schedule->campaign_status }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">Not Scheduled</span>
                                                @endif
                                            </td>
                                            {{-- Actions --}}
                                            <td>
                                                <div class="dropdown" style="position: relative;">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @can('amazon-ads.data.campaign-related-keywords')
                                                            <li>
                                                                <a href="{{ route('admin.ads.campaignKeywords', [$campaign->campaign_id, $campaignType]) }}"
                                                                    class="dropdown-item">
                                                                    <i
                                                                        class="mdi mdi-alpha-k-circle-outline font-size-16 text-success me-1"></i>
                                                                    @if ($campaignType === 'SD')
                                                                        Targets
                                                                    @else
                                                                        Keywords
                                                                    @endif
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        @can('amazon-ads.data.campaign-update')
                                                            <li>
                                                                <a href="javascript:void(0);"
                                                                    class="openCampaignModal dropdown-item"
                                                                    data-id="{{ $campaign->campaign_id }}"
                                                                    data-type="{{ $campaign->campaign_type }}"
                                                                    data-budget="{{ $campaign->daily_budget }}"
                                                                    data-name="{{ $campaign->campaign_name }}"
                                                                    data-status="{{ $campaign->campaign_state }}">
                                                                    <i
                                                                        class="mdi mdi-book-edit font-size-16 text-success me-1"></i>
                                                                    Update
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        @can('amazon-ads.data.campaign-schedule')
                                                            <li>
                                                                @php
                                                                    $isScheduled = isset(
                                                                        $scheduleUpdates[$campaign->campaign_id],
                                                                    );
                                                                    $schedule = $isScheduled
                                                                        ? $scheduleUpdates[$campaign->campaign_id]
                                                                        : null;
                                                                @endphp

                                                                <a href="{{ route('admin.ads.schedule.enable', $campaign->campaign_id) }}"
                                                                    class="dropdown-item">
                                                                    <i
                                                                        class="mdi mdi-camera-timer font-size-16 {{ $schedule && $schedule->run_status ? 'text-danger' : 'text-success' }} me-1"></i>
                                                                    {{ $schedule && $schedule->run_status ? 'Unschedule' : 'Schedule' }}
                                                                </a>
                                                            </li>
                                                        @endcan
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>

                        </table>
                        <div class="mt-2">
                            {{ $campaigns->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        <span class="badge badge-soft-info">Note :</span> Click on the <span class="text-primary">Campaign
                            ID</span> to update the
                        campaign.
                        The <i class="mdi mdi-alpha-k-circle-outline text-warning" title="Inventory available"></i>
                        icon indicates that keywords or targets exist for that campaign.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="campaign" tabindex="-1" aria-labelledby="campaignLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="campaignLabel">
                        Update Campaign <span id="campaignTypeLabel"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Mini header --}}
                <div class="px-3 mt-2">
                    <h6 class="text-muted mb-0" id="campaignName"></h6>
                </div>
                <div class="modal-body">
                    <form autocomplete="off" class="updateCampaignForm" id="updateCampaignForm" method="POST"
                        action="{{ route('admin.ads.campaign.update') }}">
                        @csrf
                        {{-- Hidden fields for ID + Type --}}
                        <input type="hidden" name="campaign_id" id="campaign_id">
                        <input type="hidden" name="campaign_type" id="campaign_type">

                        <div class="row">
                            <div class="col-lg-12">
                                <!-- Old Budget -->
                                <div class="mb-3">
                                    <label class="form-label">Old Budget</label>
                                    <div class="input-group">
                                        <div class="input-group-text">$</div>
                                        <input type="text" id="old_budget" class="form-control" disabled>
                                    </div>
                                </div>
                                <!-- Old Status -->

                                <div class="mb-3">
                                    <label class="form-label">Old Status</label>
                                    <input type="text" id="old_status" class="form-control" disabled>
                                </div>

                                <!-- New Budget Input -->
                                <div class="mb-3">
                                    <label for="new_budget" class="form-label">New Budget <span
                                            class="text-danger fw-bold">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text">$</div>
                                        <input type="number" step="0.01" id="new_budget" name="new_budget"
                                            class="form-control" placeholder="Enter New Budget" />
                                    </div>
                                    <div id="newBudgetError" class="text-danger small mt-1" style="display: none;">
                                    </div>


                                </div>
                                <!-- New Status -->
                                <div class="mb-3">
                                    <label class="form-label">New Status <span
                                            class="text-danger fw-bold">*</span></label>
                                    <select name="new_status" id="new_status" class="form-select" required>
                                        <option value="">-- Select Status --</option>
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
                const modalEl = document.getElementById("campaign");
                const newBudgetInput = document.getElementById("new_budget");
                const oldBudgetInput = document.getElementById("old_budget");
                const submitBtn = document.querySelector("#updateCampaignForm button[type='submit']");
                const errorEl = document.getElementById("newBudgetError");
                // Attach click listeners to campaign name links
                document.querySelectorAll(".openCampaignModal").forEach(function(el) {
                    el.addEventListener("click", function() {
                        // Fill hidden fields
                        document.getElementById("campaign_id").value = this.dataset.id;
                        document.getElementById("campaign_type").value = this.dataset.type;
                        // Fill old values
                        document.getElementById("old_budget").value = this.dataset.budget;
                        document.getElementById("old_status").value = this.dataset.status;
                        // Reset new values
                        newBudgetInput.value = "";
                        document.getElementById("new_status").value = "";
                        // Reset validation
                        submitBtn.disabled = true;
                        errorEl.style.display = "none";
                        // Update modal title with type (SB/SP/SD etc.)
                        let typeLabel = this.dataset.type ? this.dataset.type.toUpperCase() : "";
                        document.getElementById("campaignTypeLabel").innerText = typeLabel ? " " +
                            typeLabel : "";
                        // Show modal
                        let modal = new bootstrap.Modal(modalEl);
                        modal.show();
                        document.getElementById("campaignName").innerText = this.dataset.name
                    });
                });
                // Validation function for budget
                function validateBudget() {
                    let oldBudget = parseFloat(oldBudgetInput.value) || 0;
                    let newBudget = parseFloat(newBudgetInput.value) || 0;

                    if (!newBudget) {
                        submitBtn.disabled = true;
                        errorEl.style.display = "none";
                        return;
                    }

                    if (newBudget > oldBudget + 10) {
                        submitBtn.disabled = true;
                        errorEl.innerText = `New budget cannot exceed ${oldBudget + 10}.`;
                        errorEl.style.display = "block";
                    } else {
                        submitBtn.disabled = false;
                        errorEl.style.display = "none";
                    }
                }
                // Validate while typing
                newBudgetInput.addEventListener("input", validateBudget);
                // Reset when modal opens
                modalEl.addEventListener("shown.bs.modal", function() {
                    submitBtn.disabled = true;
                    errorEl.style.display = "none";
                });
            });
        </script>
    @endpush
@endsection
