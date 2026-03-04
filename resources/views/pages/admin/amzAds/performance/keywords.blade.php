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
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <!-- Left side: filters + info -->
                        <div class="col-12 col-lg">
                            <form method="GET" action="{{ route('admin.ads.performance.keywords.index') }}"
                                class="row g-2" id="filterForm">
                                <!-- Search -->
                                <x-elements.search-box />
                                <!-- Country Filter -->
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                <!-- Campaign Select-->
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB']" />
                                <!-- Date Filter -->
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        @php
                                            $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
                                            $filteredAsin = request('asins', []);
                                            $selectedDate = request('date', $subdayTime);
                                            $isPastDate = $selectedDate !== $subdayTime;
                                        @endphp
                                        <input class="form-control" type="date" name="date"
                                            value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}"
                                            onchange="document.getElementById('filterForm').submit()"
                                            onclick="this.showPicker()">
                                        <label for="date">Select Date</label>
                                    </div>
                                </div>

                                <!-- Advance Filters Button -->
                                <div class="col-6 col-md-auto">
                                    <button type="button" class="btn btn-light py-3 px-1 custom-dropdown-small"
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

                                        @include('pages.admin.amzAds.performance.partials.keywords-filter')
                                    </div>
                                    <!-- end offcanvas -->
                                </div>
                            </form>
                            <!-- Info button -->
                        </div>
                        <!-- right side -->
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
                                @include('pages.admin.amzAds.performance.partials.keywords-actions')
                            </div>
                        </div>

                        <div class="col-6 col-lg-auto ms-lg-auto d-flex justify-content-end">
                            <div class="dropdown">
                                <button class="btn btn-light w-100 d-flex align-items-center justify-content-center menuBtn"
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="dropdownMenuButton1"
                                    style="--bs-dropdown-item-padding-y: 0.4rem;">
                                    <small class="text-muted fw-semibold ms-3">More Actions</small>
                                    @can('amazon-ads.keyword-performance.excel-export')
                                        <li>
                                            <a href="{{ route('admin.ads.performance.keywords.export', request()->query()) }}"
                                                class="dropdown-item d-flex align-items-center"
                                                onclick="return confirm('Are you sure you want to download Keyword Performance for the selected date?');">
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
                                                href="{{ route('admin.ads.performance.showLogs', ['type' => $type, 'date' => $selectedDate]) }}">
                                                <i class="bx bx-history font-size-18 text-primary me-1"></i>
                                                Bid Update Logs
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
                        'keyword_id' => 'Keyword ID',
                        'campaign_name' => 'Campaign Name',
                        'k_campaign_id' => 'Campaign ID',
                        'campaign_type' => 'Campaign Type',
                        'date' => 'Date',
                        'country' => 'Country',
                        'bid_start' => 'Bid Start',
                        'bid_suggest' => 'Bid Suggestion',
                        'bid_end' => 'Bid End',
                    ]" :default-visible="['country', 'campaign_type']" />
                    {{--  Column Visibility --}}

                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th data-col="keyword_id">Keyword Id</th>
                                    {{-- <th>Target Id</th> --}}
                                    <th data-col="campaign_name">Campaign Name</th>
                                    <th>Keyword Name</th>
                                    <th data-col="k_campaign_id">Campaign Id</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th data-col="campaign_type">Type</th>
                                    {{-- <th>Target Type</th> --}}
                                    <th data-col="date">Date</th>
                                    <th data-col="country">Country</th>
                                    <th>Click 1d</th>
                                    <th>CTR 1d</th>
                                    <th>CPC 1d</th>

                                    <th>Conversion Rate 1d</th>
                                    <th>Impressions 1d</th>
                                    <th>Click 7d</th>
                                    <th>CTR 7d</th>
                                    <th>CPC 7d</th>
                                    <th>Conversion Rate 7d</th>
                                    <th>Impressions 7d</th>

                                    <th>Spend 1d</th>
                                    <th>Sales 1d</th>
                                    <th>Purchase 1d</th>
                                    <th>ACoS 1d (%)</th>

                                    <th>Spend 7d</th>
                                    <th>Sales 7d</th>
                                    <th>Purchase 7d</th>
                                    <th>ACoS 7d (%)</th>

                                    <th>Spend 14d</th>
                                    <th>Sales 14d</th>
                                    <th>Purchase 14d</th>
                                    <th>ACoS 14d (%)</th>
                                    <th data-col="bid_start">Bid Start</th>
                                    <th data-col="bid_suggest">Bid Suggestion</th>
                                    <th data-col="bid_end">Bid End</th>
                                    <th>Status</th>
                                    <th>Bid</th>
                                    <th class="wide-col">
                                        <span class="ai-gradient-text">AI Recommendation ✨
                                        </span>
                                    </th>
                                    <th class="wide-col">
                                        <span class="ai-gradient-text">AI Suggested Bid ✨
                                        </span>
                                    </th>
                                    <th>Suggested Bid</th>
                                    @can('amazon-ads.keyword-performance.manual-budget')
                                        <th>Manual Bid</th>
                                    @endcan
                                    <th style="width: 300px;">Recommendation</th>
                                    {{-- @if (!$isPastDate)
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select-all">
                                                <label class="form-check-label" for="select-all">Select All</label>
                                            </div>
                                        </th>
                                    @else
                                        <th></th>
                                    @endif --}}

                                    @if (!$isPastDate)
                                        <th>
                                            <div class="dropdown" data-bs-auto-close="false">
                                                <button class="btn btn-outline-info btn-sm dropdown-toggle fw-bold"
                                                    type="button" data-bs-toggle="dropdown">
                                                    Apply to All
                                                </button>

                                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 220px">
                                                    <form method="POST"
                                                        action="{{ route('admin.ads.performance.keywords.runKeywordUpdate', request()->query()) }}">
                                                        @csrf
                                                        <input type="hidden" name="bulk" value="1">

                                                        <div class="mb-2">
                                                            <input class="form-check-input" type="radio"
                                                                name="run_update" value="1" id="bulkCheckAll">
                                                            <label class="form-check-label ms-1" for="bulkCheckAll">
                                                                Check All
                                                            </label>
                                                        </div>

                                                        <div class="mb-3">
                                                            <input class="form-check-input" type="radio"
                                                                name="run_update" value="0" id="bulkUncheckAll">
                                                            <label class="form-check-label ms-1" for="bulkUncheckAll">
                                                                Uncheck All
                                                            </label>
                                                        </div>

                                                        <button type="submit" class="btn btn-primary w-100">
                                                            Apply
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if ($keywords->count() > 0)
                                    @foreach ($keywords as $index => $keyword)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td data-col="keyword_id">{{ $keyword->keyword_id ?? 'N/A' }}</td>
                                            @php
                                                $nameMap = [
                                                    'SP' => $keyword->c_name ?? 'N/A',
                                                    'SB' => $keyword->sb_c_name ?? 'N/A',
                                                ];

                                                $campaignName = $nameMap[$keyword->campaign_types] ?? 'N/A';
                                            @endphp

                                            <td class="ellipsis-text" title="{{ $campaignName }}"
                                                data-col="campaign_name">
                                                {{ $campaignName }}
                                            </td>
                                            {{-- <td>{{ $keyword->target_id ?? 'N/A' }}</td> --}}
                                            <td>{{ $keyword->keyword ?? 'N/A' }}</td>
                                            <td data-col="k_campaign_id">{{ $keyword->campaign_id ?? 'N/A' }}</td>
                                            @php
                                                $relatedAsins = is_string($keyword->related_asin)
                                                    ? json_decode($keyword->related_asin, true)
                                                    : $keyword->related_asin;
                                            @endphp
                                            <td style="white-space: pre-line;">
                                                @if (!empty($relatedAsins))
                                                    {{ implode("\n", (array) $relatedAsins) }}@else{{ $keyword->asin ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty($keyword->product_names))
                                                    {{ implode(', ', $keyword->product_names) }}
                                                @else
                                                    {{ $keyword->product_name ?? '--' }}
                                                @endif
                                            </td>

                                            <td data-col="campaign_type">{{ $keyword->campaign_types ?? 'N/A' }}</td>
                                            {{-- <td>{{ $keyword->targeting_type ?? 'N/A' }}</td> --}}
                                            <td data-col="date">{{ $keyword->date ?? 'N/A' }}</td>
                                            <td data-col="country">{{ $keyword->country ?? 'N/A' }}</td>

                                            <td>{{ $keyword->clicks ?? 'N/A' }}</td>
                                            <td>{{ $keyword->ctr ?? 'N/A' }}</td>
                                            <td>{{ $keyword->cpc ?? 'N/A' }}</td>
                                            <td>{{ $keyword->impressions ?? 'N/A' }}</td>
                                            <td>{{ $keyword->conversion_rate ?? 'N/A' }}</td>

                                            <td class="table-success fw-bold">{{ $keyword->clicks_7d ?? 'N/A' }}</td>
                                            <td class="table-success fw-bold">{{ $keyword->ctr_7d ?? 'N/A' }}</td>
                                            <td class="table-success fw-bold">{{ $keyword->cpc_7d ?? 'N/A' }}</td>
                                            <td class="table-success fw-bold">{{ $keyword->conversion_rate_7d ?? 'N/A' }}
                                            </td>
                                            <td class="table-success fw-bold">{{ $keyword->impressions_7d ?? 'N/A' }}</td>

                                            <td class="table-primary fw-bold">
                                                ${{ number_format($keyword->total_spend, 2) ?? 'N/A' }}</td>
                                            <td class="table-primary fw-bold">
                                                ${{ number_format($keyword->total_sales, 2) ?? 'N/A' }}</td>
                                            <td class="table-primary fw-bold">{{ $keyword->purchases1d ?? 'N/A' }}</td>
                                            <td class="table-warning fw-bold">{{ $keyword->acos ?? 'N/A' }}%</td>

                                            <td class="table-info fw-bold">
                                                ${{ number_format($keyword->total_spend_7d, 2) ?? 'N/A' }}</td>
                                            <td class="table-info fw-bold">
                                                ${{ number_format($keyword->total_sales_7d, 2) ?? 'N/A' }}</td>
                                            <td class="table-info fw-bold">{{ $keyword->purchases1d_7d ?? 'N/A' }}</td>
                                            <td class="table-warning fw-bold">{{ $keyword->acos_7d ?? 'N/A' }}%</td>
                                            <td class="table-light fw-bold">
                                                ${{ number_format($keyword->total_spend_14d, 2) ?? 'N/A' }}</td>
                                            <td class="table-light fw-bold">
                                                ${{ number_format($keyword->total_sales_14d, 2) ?? 'N/A' }}</td>
                                            <td class="table-light fw-bold">{{ $keyword->purchases1d_14d ?? 'N/A' }}</td>
                                            <td class="table-warning fw-bold">{{ $keyword->acos_14d ?? 'N/A' }}%</td>

                                            <td data-col="bid_start"
                                                class="{{ $keyword->targeting_type === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                                @if ($keyword->targeting_type === 'MANUAL')
                                                    ${{ number_format($keyword->manual_bid_start ?? 0, 2) ?? 'N/A' }}
                                                @else
                                                    ${{ number_format($keyword->auto_bid_start ?? 0, 2) ?? 'N/A' }}
                                                @endif
                                            </td>

                                            <td data-col="bid_suggest"
                                                class="{{ $keyword->targeting_type === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                                @if ($keyword->targeting_type === 'MANUAL')
                                                    ${{ number_format($keyword->manual_bid_suggestion ?? 0, 2) ?? 'N/A' }}
                                                @else
                                                    ${{ number_format($keyword->auto_bid_median ?? 0, 2) ?? 'N/A' }}
                                                @endif
                                            </td>

                                            <td data-col="bid_end"
                                                class="{{ $keyword->targeting_type === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                                @if ($keyword->targeting_type === 'MANUAL')
                                                    ${{ number_format($keyword->manual_bid_end ?? 0, 2) ?? 'N/A' }}
                                                @else
                                                    ${{ number_format($keyword->auto_bid_end ?? 0, 2) ?? 'N/A' }}
                                                @endif
                                            </td>

                                            <td>
                                                <div>
                                                    @if (!empty($keyword->sp_state) && $keyword->sp_state !== 'N/A')
                                                        <span class="badge bg-success">
                                                            {{ ucfirst($keyword->sp_state) }}</span>
                                                    @elseif(!empty($keyword->sb_state) && $keyword->sb_state !== 'N/A')
                                                        <span class="badge bg-success">
                                                            {{ ucfirst($keyword->sb_state) }}</span>
                                                    @else
                                                        <span class="badge bg-warning">N/A</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="table-success">
                                                @if (!is_null($keyword->old_bid) && $keyword->bid != $keyword->old_bid)
                                                    <span class="text-decoration-line-through text-danger">
                                                        ${{ number_format($keyword->old_bid, 2) }}
                                                    </span>
                                                    <span class="fw-bold text-success">
                                                        ${{ number_format($keyword->bid, 2) }}
                                                    </span>
                                                @else
                                                    ${{ number_format($keyword->bid ?? 0, 2) }}
                                                @endif
                                            </td>

                                            <td id="rec-{{ $keyword->id }}" class="ai-col td-break-col"
                                                @if ($keyword->ai_status != 'done') onclick="generateRecommendation({{ $keyword->id }})" style="cursor:pointer;" @endif>
                                                {{ $keyword->ai_recommendation ?? '✨Ai Generate' }}
                                            </td>

                                            <td id="bid-{{ $keyword->id }}">
                                                @if (is_numeric($keyword->ai_suggested_bid))
                                                    ${{ number_format($keyword->ai_suggested_bid, 2) }}
                                                @else
                                                    {{ $keyword->ai_suggested_bid ?? '--' }}
                                                @endif
                                            </td>

                                            <td class="td-break-col">{{ $keyword->suggested_bid ?? 'N/A' }}</td>
                                            @can('amazon-ads.keyword-performance.manual-budget')
                                                <td>
                                                    <livewire:ads.keyword-manual-budget-input :keyword-id="$keyword->id"
                                                        :manual-bid="$keyword->manual_bid" :wire:key="'kwb-'.$keyword->id" />
                                                </td>
                                            @endcan
                                            <td class="td-break-col">{{ $keyword->recommendation ?? 'N/A' }}</td>
                                            @can('amazon-ads.keyword-performance.run-update')
                                                <td class="">
                                                    @if (!$isPastDate)
                                                        {{-- Single keyword toggle --}}
                                                        <input type="checkbox" class="form-check-input row-checkbox"
                                                            value="{{ $keyword->id }}"
                                                            {{ $keyword->run_update ? 'checked' : '' }}
                                                            @if ($keyword->run_status === 'done') disabled @endif>

                                                        {{-- Status badges --}}
                                                        @if ($keyword->run_status === 'done')
                                                            <span class="badge bg-success">Done</span>
                                                        @elseif ($keyword->run_status === 'dispatched')
                                                            <span class="badge bg-warning text-dark">Dispatched</span>
                                                        @endif
                                                    @endif
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
                        {{ $keywords->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                    <p class="text-muted mb-0">
                        <span class="badge badge-soft-info">Note :</span> 7d / 14d are calculated from yesterday’s date to
                        the past 7 or 14 days based on the date picker.
                    </p>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
    <livewire:ads.keyword-budget-optimization-modal />
    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            window.KeywordPageConfig = {
                filteredAsin: @json($filteredAsin ?? []),
                routes: {
                    asinList: "{{ route('admin.ads.performance.productAsins') }}",
                    generate: "{{ route('admin.ads.performance.recommendation.keywordgenerate', ':id') }}",
                    pollStatus: "{{ route('admin.ads.performance.recommendation.poll.keywordStatus', ':id') }}",
                    runKeywordUpdate: "{{ route('admin.ads.performance.keywords.runKeywordUpdate') }}",
                },
                csrfToken: "{{ csrf_token() }}"
            };
        </script>
        <script src="{{ asset('assets/js/keyword-performance.js') }}"></script>
    @endpush
@endsection
