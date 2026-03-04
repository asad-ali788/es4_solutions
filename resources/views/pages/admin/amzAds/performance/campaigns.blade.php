@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    Amazon Ads - Performance Recommendations
                </h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.amzAds.performance_nav')
                <div class="card-body pt-2">
                    <div class="row g-2 align-items-end mb-3">
                        {{-- LEFT: Form (filters only) --}}
                        <div class="col-12 col-lg">
                            <form method="GET" action="{{ route('admin.ads.performance.capaigns.index') }}"
                                id="filterForm" class="row g-2 align-items-end">
                                {{-- Search --}}
                                <div class="col-12 col-md-4 col-lg-3">
                                    <x-elements.search-box />
                                </div>
                                {{-- Country --}}
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                {{-- Campaign --}}
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB', 'SD' => 'SD']" />
                                {{-- Date --}}
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        @php
                                            $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
                                            $filteredAsin = request('asins', []);
                                        @endphp
                                        <input class="form-control" type="date" name="date"
                                            value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}"
                                            onchange="document.getElementById('filterForm').submit()"
                                            onclick="this.showPicker()">
                                        <label for="date">Select Date</label>
                                    </div>
                                </div>
                                {{-- Advance --}}
                                <div class="col-6 col-md-auto">
                                    <button type="button"
                                        class="btn btn-light py-3 px-2 custom-dropdown-small w-100 w-md-auto"
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"
                                        aria-controls="offcanvasRight">
                                        <i class="mdi mdi-filter-plus-outline"></i> Advance
                                    </button>
                                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight"
                                        aria-labelledby="offcanvasRightLabel">
                                        <div class="offcanvas-header">
                                            <h5 id="offcanvasRightLabel">Advance Filters</h5>
                                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                                aria-label="Close"></button>
                                        </div>
                                        @include('pages.admin.amzAds.performance.partials.campaigns-filter')
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Actions button (OUTSIDE form, but aligned with filters) --}}
                        <div class="col-6 col-lg-auto">
                            <button type="button"
                                class="btn btn-primary py-2 py-sm-1 px-2 custom-dropdown-small w-100 w-sm-auto
                                    d-flex flex-sm-column align-items-center justify-content-center"
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightActions"
                                aria-controls="offcanvasRightActions">
                                <i class="mdi mdi-cloud-sync font-size-16"></i>
                                <span class="d-sm-block d-none">Actions</span>
                                <span class="d-block d-sm-none ms-2">Actions</span>
                            </button>

                            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightActions"
                                aria-labelledby="offcanvasRightActionsLabel">
                                <div class="offcanvas-header">
                                    <h5 id="offcanvasRightActionsLabel">Actions</h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                                </div>
                                @include('pages.admin.amzAds.performance.partials.campaigns-actions')
                            </div>
                        </div>

                        {{-- RIGHT: 3-dots menu only (right on lg, normal on mobile) --}}
                        <div class="col-6 col-lg-auto ms-lg-auto d-flex justify-content-end">
                            <div class="dropdown">
                                <button class="btn btn-light w-100 d-flex align-items-center justify-content-center menuBtn"
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="dropdownMenuButton1"
                                    style="--bs-dropdown-item-padding-y: 0.5rem;">
                                    <small class="text-muted fw-semibold ms-3">More Actions</small>
                                    @can('amazon-ads.campaign-performance.excel-export')
                                        <li>
                                            <a href="{{ route('admin.ads.performance.campaigns.export', request()->query()) }}"
                                                class="dropdown-item d-flex align-items-center"
                                                onclick="return confirm('Are you sure you want to download Campaign Performance for the selected date?');">
                                                <i class="mdi mdi-file-excel font-size-18 label-icon text-success me-1"></i>
                                                Excel Export
                                            </a>
                                        </li>
                                    @endcan
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center"
                                            onclick="Livewire.dispatch('open-budget-optimization')">
                                            <i class="mdi mdi-book-information-variant font-size-18 text-primary me-1"></i>
                                            Recommendation Rules
                                        </a>
                                    </li>
                                    @can('amazon-ads.update-logs')
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center"
                                                href="{{ route('admin.ads.performance.showLogs', ['type' => $type, 'date' => $selectedWeek]) }}">
                                                <i class="bx bx-history font-size-18 text-primary me-1"></i>
                                                Budget Update Logs
                                            </a>
                                        </li>
                                    @endcan
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="#"
                                            data-bs-toggle="modal" data-bs-target="#columnFilterPopupModal">
                                            <i class="bx bx-columns font-size-18 text-primary me-1"></i>
                                            Show / Hide Columns
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    {{-- Column Visibility --}}
                    <x-elements.column-visibility :columns="[
                        'campaign_id' => 'Campaign ID',
                        'date' => 'Date',
                        'country' => 'Country',
                        'campaign_type' => 'Campaign Type',
                        'group' => 'Group',
                        'total_spend_14d' => 'Spend 14d',
                        'total_sales_14d' => 'Sales 14d',
                        'purchases7d_14d' => 'Purchase 14d',
                        'acos_14d' => 'ACoS 14d',
                        'suggested_bid' => 'Suggested Budget',
                        'ai_recommendation' => 'Ai Recommendation ✨',
                    ]" :default-visible="['country', 'campaign_type','ai_recommendation','suggested_bid']" />
                    {{--  Column Visibility --}}

                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th data-col="campaign_id">Campaign Id</th>
                                    <th>Campaign Name</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th data-col="campaign_type">Type</th>
                                    <th data-col="date">Date</th>
                                    {{-- <th>Enabled Campaigns</th> --}}
                                    <th data-col="country">Country</th>
                                    <th data-col="group">Group</th>
                                    <th>Spend 1d</th>
                                    <th>Sales 1d</th>
                                    <th>Purchase 1d</th>
                                    <th>ACoS 1d</th>

                                    <th>Spend 7d</th>
                                    <th>Sales 7d</th>
                                    <th>Purchase 7d</th>
                                    <th>ACoS 7d (%) </th>

                                    <th data-col="total_spend_14d">Spend 14d</th>
                                    <th data-col="total_sales_14d">Sales 14d</th>
                                    <th data-col="purchases7d_14d">Purchase 14d</th>
                                    <th data-col="acos_14d">ACoS 14d</th>
                                    <th>Status</th>
                                    <th>Daily Budget</th>
                                    <th class="wide-col" data-col="ai_recommendation">
                                        <span class="ai-gradient-text">AI Recommendation ✨
                                        </span>
                                    </th>
                                    <th class="wide-col" data-col="ai_recommendation">
                                        <span class="ai-gradient-text">AI Suggested Budget ✨
                                        </span>
                                    </th>
                                    <th data-col="suggested_bid">Suggested Budget</th>
                                    @can('amazon-ads.campaign-performance.manual-budget')
                                        <th>Manual Budget</th>
                                    @endcan
                                    <th style="width: 300px;">Recommendation</th>
                                    @can('amazon-ads.campaign-performance.run-update')
                                        <th>
                                            <div class="dropdown" data-bs-auto-close="false">
                                                <button class="btn btn-outline-info btn-sm dropdown-toggle fw-bold"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Apply to All
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 200px">
                                                    <form id="bulkRunUpdateForm" method="POST"
                                                        action="{{ route('admin.ads.performance.campaigns.runUpdate', request()->query()) }}">
                                                        @csrf
                                                        <input type="hidden" name="bulk" value="1">
                                                        <div class="mb-2">
                                                            <input class="form-check-input" type="radio" name="run_update"
                                                                value="1">
                                                            <label class="form-check-label ms-1" style="pointer-events:none;">
                                                                Check All
                                                            </label>
                                                        </div>
                                                        <div class="mb-3">
                                                            <input class="form-check-input" type="radio" name="run_update"
                                                                value="0">
                                                            <label class="form-check-label ms-1"
                                                                style="pointer-events:none;">Uncheck All</label>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary  w-100">Apply</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @if ($campaigns->count() > 0)
                                    @foreach ($campaigns as $index => $campaign)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td data-col="campaign_id">{{ $campaign->campaign_id ?? 'N/A' }}</td>
                                            <td class="ellipsis-text" title="{{ $campaign->campaign_name ?? '' }}">
                                                {{ $campaign->campaign_name ?? 'N/A' }}</td>
                                            @php
                                                $relatedAsins = is_string($campaign->related_asins)
                                                    ? json_decode($campaign->related_asins, true)
                                                    : $campaign->related_asins;
                                            @endphp
                                            <td style="white-space: pre-line;">
                                                @if (!empty($relatedAsins))
                                                    {{ implode("\n", (array) $relatedAsins) }}@else{{ $campaign->asin ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty($campaign->product_names))
                                                    {{ implode(', ', $campaign->product_names) }}
                                                @else
                                                    {{ $campaign->product_name ?? '--' }}
                                                @endif
                                            </td>

                                            <td data-col="campaign_type">{{ $campaign->campaign_types ?? 'N/A' }}</td>
                                            <td data-col="date">{{ $campaign->report_week ?? 'N/A' }}</td>
                                            {{-- <td>{{ $campaign->enabled_campaigns_count }}</td> --}}
                                            <td data-col="country">{{ $campaign->country ?? 'N/A' }}</td>
                                            @php
                                                $from = (int) ($campaign->from_group ?? 0);
                                                $to = (int) ($campaign->to_group ?? 0);

                                                $badgeClass = fn(int $g) => match ($g) {
                                                    1 => 'success',
                                                    2 => 'warning',
                                                    3, 4 => 'danger',
                                                    default => 'secondary',
                                                };

                                                $label = fn(int $g) => "Group {$g}";
                                            @endphp

                                            <td data-col="group">
                                                @if ($to <= 0)
                                                    <span class="text-muted">-</span>
                                                @elseif($from > 0 && $from !== $to)
                                                    <span
                                                        class="badge badge-soft-{{ $badgeClass($from) }}">{{ $label($from) }}</span>
                                                    <span class="text-muted"><i class="bx bx-right-arrow-alt"></i></span>
                                                    <span
                                                        class="badge badge-soft-{{ $badgeClass($to) }}">{{ $label($to) }}</span>
                                                @else
                                                    <span
                                                        class="badge badge-soft-{{ $badgeClass($to) }}">{{ $label($to) }}</span>
                                                @endif
                                            </td>
                                            <td>${{ number_format($campaign->total_spend, 2) ?? 'N/A' }}</td>
                                            <td>${{ number_format($campaign->total_sales, 2) ?? 'N/A' }}</td>
                                            <td>{{ $campaign->purchases7d ?? 'N/A' }}</td>
                                            <td>{{ $campaign->acos ?? 'N/A' }}</td>

                                            <td class="table-warning">
                                                ${{ number_format($campaign->total_spend_7d, 2) ?? 'N/A' }}</td>
                                            <td class="table-warning">
                                                ${{ number_format($campaign->total_sales_7d, 2) ?? 'N/A' }}</td>
                                            <td class="table-warning">{{ $campaign->purchases7d_7d ?? 'N/A' }}</td>
                                            <td class="table-warning">{{ $campaign->acos_7d ?? 'N/A' }}%</td>

                                            <td data-col="total_spend_14d">
                                                ${{ number_format($campaign->total_spend_14d, 2) ?? 'N/A' }}</td>
                                            <td data-col="total_sales_14d">
                                                ${{ number_format($campaign->total_sales_14d, 2) ?? 'N/A' }}</td>
                                            <td data-col="purchases7d_14d">{{ $campaign->purchases7d_14d ?? 'N/A' }}</td>
                                            <td data-col="acos_14d">{{ $campaign->acos_14d ?? 'N/A' }}</td>

                                            <td>
                                                @php
                                                    $state =
                                                        $campaign->sp_campaign_state ??
                                                        ($campaign->sb_campaign_state ?? null);
                                                @endphp

                                                @if ($state === 'ENABLED')
                                                    <span class="badge bg-success">{{ $state }}</span>
                                                @elseif ($state === 'PAUSED')
                                                    <span class="badge bg-warning">{{ $state }}</span>
                                                @elseif ($state)
                                                    <span class="badge bg-danger">{{ $state }}</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>

                                            <td class="table-success">
                                                @if (!is_null($campaign->old_budget) && $campaign->total_daily_budget != $campaign->old_budget)
                                                    <span class="text-decoration-line-through text-danger">
                                                        ${{ $campaign->old_budget }}
                                                    </span>
                                                    <span class="fw-bold text-success">
                                                        ${{ $campaign->total_daily_budget }}
                                                    </span>
                                                @else
                                                    ${{ number_format($campaign->total_daily_budget, 2) ?? 'N/A' }}
                                                @endif
                                            </td>

                                            {{-- Recommendation column --}}
                                            <livewire:ads.campaign-ai-recommendation :campaign-id="$campaign->id" column="rec"
                                                :wire:key="'rec-'.$campaign->id" />
                                            {{-- Budget column --}}
                                            <livewire:ads.campaign-ai-recommendation :campaign-id="$campaign->id" column="budget"
                                                :wire:key="'budget-'.$campaign->id" />
                                            <td data-col="suggested_bid">
                                                @if (is_numeric($campaign->suggested_budget))
                                                    ${{ number_format($campaign->suggested_budget, 2) }}
                                                @else
                                                    {{ $campaign->suggested_budget ?? '--' }}
                                                @endif
                                            </td>
                                            @can('amazon-ads.campaign-performance.manual-budget')
                                                <td>
                                                    <livewire:ads.campaign-manual-budget-input :campaign-id="$campaign->id"
                                                        :manual-budget="$campaign->manual_budget" :wire:key="'mb-'.$campaign->id" />
                                                </td>
                                            @endcan
                                            <td class="td-break-col">
                                                @php
                                                    $icons = [
                                                        'Keep same budget (optimize keywords/placements)' => '🤔',
                                                        'Increase budget 30%' => '🔼',
                                                        'Keep same budget' => '✅',
                                                        'Reduce budget by 20%' => '🔽',
                                                    ];
                                                    $rec = $campaign->recommendation ?? '-';
                                                    $icon = collect($icons)->first(function ($_, $key) use ($rec) {
                                                        return str_starts_with($rec, $key);
                                                    });
                                                @endphp
                                                {!! $icon ? $icon . ' ' . e($rec) : e($rec) !!}
                                            </td>
                                            @can('amazon-ads.campaign-performance.run-update')
                                                <td>
                                                    <div class="form-check">
                                                        <!-- Check box -->
                                                        <input type="checkbox" class="form-check-input single-check"
                                                            data-id="{{ $campaign->id }}"
                                                            {{ $campaign->run_update ? 'checked' : '' }}>
                                                        <!-- Make Live Status -->
                                                        @if ($campaign->run_update && $campaign->run_status === 'pending')
                                                            <span class="badge bg-info">{{ $campaign->run_status }}</span>
                                                        @elseif ($campaign->run_status === 'dispatched')
                                                            <span class="badge bg-warning">{{ $campaign->run_status }}</span>
                                                        @elseif ($campaign->run_status === 'done')
                                                            <span class="badge bg-success">{{ $campaign->run_status }}</span>
                                                        @elseif ($campaign->run_status === 'failed')
                                                            <span class="badge bg-danger">{{ $campaign->run_status }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <!-- end table -->
                    </div>
                    <div class="mt-2">
                        {{ $campaigns->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                    <p class="text-muted mb-0">
                        <span class="badge badge-soft-info">Note :</span> 7d / 14d are calculated from yesterday’s date to
                        the past 7 or 14 days based on the date picker.
                    </p>
                    <p class="text-muted mb-0">
                        <span class="badge badge-soft-info">Note :</span> Campaign groups are calculated daily based on
                        ACOS, spend, and sales:
                        <span class="ms-1">
                            <span class="badge badge-soft-success">Group 1 (ACOS &lt; 30)</span>
                            <span class="badge badge-soft-warning ms-1">Group 2 (ACOS &gt; 30)</span>
                            <span class="badge badge-soft-danger ms-1">Group 3 (Spend &gt; 0 &amp; ACOS = 0)</span>
                            <span class="badge badge-soft-danger ms-1">Group 4 (No Spend &amp; No Sales)</span>
                        </span>
                    </p>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
    <livewire:ads.campaign-budget-optimization-modal />
    @push('scripts')
        <script>
            function clearCampaignFilters() {
                const form = document.getElementById('filterForm');
                if (!form) return;

                // Manually clear specific fields
                const runStatus = form.querySelector('[name="run_status"]');
                const runUpdate = form.querySelector('[name="run_update"]');
                const asins = form.querySelector('[name="asins[]"], [name="asins"]');
                const rules = form.querySelectorAll('[name="rules[]"], [name="rules"]');

                // Clear checkboxes (rules[])
                if (rules.length > 0) {
                    rules.forEach(rule => rule.checked = false);
                }

                // Clear normal inputs/selects
                if (runUpdate) runUpdate.value = '';
                if (asins) {
                    if (asins.multiple) {
                        Array.from(asins.options).forEach(opt => opt.selected = false);
                    } else {
                        asins.value = '';
                    }
                }

                // Submit immediately
                form.submit();
            }

            document.addEventListener('DOMContentLoaded', () => {
                const checkAll = document.getElementById('checkAll');
                const singleChecks = document.querySelectorAll('.single-check');
                const updateUrl = "{{ route('admin.ads.performance.campaigns.runUpdate') }}";
                const selectedDate = "{{ $selectedWeek }}";
                const csrfToken = "{{ csrf_token() }}";

                if (singleChecks.length === 0) return;

                // Helper: gather current filters from UI
                const getFilters = () => ({
                    search: document.querySelector('input[name="search"]')?.value || null,
                    country: document.querySelector('select[name="country"]')?.value || 'all',
                    campaign: document.querySelector('select[name="campaign"]')?.value || 'all',
                    rules: Array.from(document.querySelectorAll('input[name="rules[]"]:checked')).map(el => el
                        .value),
                    asins: $('.asin-select')?.val() || [],
                    date: selectedDate
                });

                // Helper: update checkboxes visually
                const syncCheckboxes = (checked) => {
                    singleChecks.forEach(cb => cb.checked = checked);
                };

                // Single Update
                document.addEventListener('change', async function(e) {
                    const cb = e.target.closest('.single-check');
                    if (!cb) return;

                    const campaignId = cb.dataset.id; // make sure data-id exists
                    const isChecked = cb.checked;

                    try {
                        const res = await fetch(updateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                campaign_id: campaignId,
                                run_update: isChecked ? 1 : 0
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            showToast('success', data.message);
                        } else {
                            let msg = data.message || "Server busy. Try again later.";
                            if (
                                msg.includes("SQLSTATE") ||
                                msg.includes("Lock wait timeout") ||
                                msg.includes("HY000") ||
                                msg.includes("Exception") ||
                                msg.length > 120
                            ) msg = "Server is busy. Please try again later.";

                            showToast('error', msg);
                            cb.checked = !isChecked;
                        }
                    } catch (err) {
                        showToast('error', 'Something went wrong!');
                        console.error(err);
                        cb.checked = !isChecked;
                    }
                });

            });
        </script>

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof $.fn.select2 === 'undefined') {
                    console.error("Select2 not loaded!");
                    return;
                }

                // Pass PHP array to JS
                const filteredAsin = @json($filteredAsin ?? []);

                // Init Select2 with AJAX + preloaded data
                $('.asin-select').select2({
                    placeholder: "Search ASIN",
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route('admin.ads.performance.productAsins') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        }
                    },
                    width: '100%',
                    // Tell Select2 about initial data so pre-selected options show
                    data: filteredAsin.map(asin => ({
                        id: asin,
                        text: asin
                    }))
                });

                // Append assigned ASINs as selected options if missing
                filteredAsin.forEach(function(asin) {
                    if (!$(".asin-select option[value='" + asin + "']").length) {
                        const newOption = new Option(asin, asin, true, true);
                        $('.asin-select').append(newOption).trigger('change');
                    }
                });
            });
        </script>
    @endpush
@endsection
