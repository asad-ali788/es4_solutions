@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    Amazon Ads - Search Terms
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.amzAds.performance_nav')
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <!-- Left side: filters + info -->
                        <div class="col-12 col-lg-9">
                            <form method="GET" action="{{ route('admin.searchterms.index') }}" class="row g-2"
                                id="filterForm">
                                <!-- Search -->
                                <x-elements.search-box />

                                <!-- Country Filter -->
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />

                                <!-- Date Filter -->
                                <div class="col-12 col-md-auto">
                                    <div class="form-floating">
                                        @php
                                            $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
                                            $selectedDate = request('date', $subdayTime);
                                            $isPastDate = $selectedDate !== $subdayTime;
                                            $filteredAsin = request('asins', []);
                                        @endphp
                                        <input class="form-control custom-dropdown-small" type="date" name="date"
                                            value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}"
                                            onchange="document.getElementById('filterForm').submit()"
                                            onclick="this.showPicker()">
                                        <label for="date">Select Date</label>
                                    </div>
                                </div>

                                <!-- Advanced Filters Button -->
                                <div class="col-12 col-md-auto">
                                    <button type="button"
                                        class="btn btn-light py-3 px-1 custom-dropdown-small w-100 w-md-auto"
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"
                                        aria-controls="offcanvasRight">
                                        <i class="mdi mdi-filter-plus-outline"></i> Advance
                                    </button>

                                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight"
                                        aria-labelledby="offcanvasRightLabel">
                                        <div class="offcanvas-header">
                                            <h5 id="offcanvasRightLabel">Filter Options</h5>
                                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="offcanvas-body">
                                            <h5 class="mb-3">Advanced Filters</h5>

                                            <div class="row g-3">
                                                <!-- Days Filter -->
                                                <div class="col-12">
                                                    <div class="form-floating">
                                                        <select class="form-select" name="days">
                                                            <option value="">All</option>
                                                            <option value="7"
                                                                {{ request('days') == '7' ? 'selected' : '' }}>Last 7 Days
                                                            </option>
                                                            <option value="14"
                                                                {{ request('days') == '14' ? 'selected' : '' }}>Last 14 Days
                                                            </option>
                                                        </select>
                                                        <label>Filter By Days</label>
                                                    </div>
                                                </div>

                                                <!-- Search Term Performance -->
                                                <div class="col-12">
                                                    <div class="form-floating">
                                                        <select class="form-select" name="search_term_type">
                                                            <option value="">All Terms</option>
                                                            <option value="positive"
                                                                {{ request('search_term_type') == 'positive' ? 'selected' : '' }}>
                                                                High-Performing Terms</option>
                                                            <option value="negative"
                                                                {{ request('search_term_type') == 'negative' ? 'selected' : '' }}>
                                                                Under-Performing Terms</option>
                                                        </select>
                                                        <label>Term Performance</label>
                                                    </div>
                                                </div>

                                                <!-- Keyword Match Type -->
                                                <div class="col-12">
                                                    <div class="form-floating">
                                                        <select class="form-select" name="keyword_match_type">
                                                            <option value="">All Search Terms</option>
                                                            <option value="matching"
                                                                {{ request('keyword_match_type') == 'matching' ? 'selected' : '' }}>
                                                                Matching Keywords</option>
                                                            <option value="not_matching"
                                                                {{ request('keyword_match_type') == 'not_matching' ? 'selected' : '' }}>
                                                                Not Matching Keywords</option>
                                                        </select>
                                                        <label>Keyword Match</label>
                                                    </div>
                                                </div>

                                                <!-- ASIN Filter -->
                                                <div class="col-12 form-group mb-3">
                                                    <label class="me-2 fw-semibold">ASIN Filter:</label>
                                                    <select name="asins[]" class="form-select asin-select" multiple
                                                        style="width: 100%;">
                                                        @foreach ($filteredAsin as $asin)
                                                            <option value="{{ $asin }}" selected>
                                                                {{ $asin }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="d-flex justify-content-between mt-4">
                                                    <button type="submit" class="btn btn-primary w-50 me-2">
                                                        <i class="mdi mdi-filter-check"></i> Apply Filters
                                                    </button>
                                                    <a href="{{ route('admin.searchterms.index') }}"
                                                        class="btn btn-outline-secondary w-50">
                                                        <i class="mdi mdi-filter-remove"></i> Clear Filters
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- right side -->
                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2 mt-2 mt-lg-0">
                            <div class="dropdown flex-fill flex-lg-none" style="min-width: 0;">
                                <button class="btn btn-light w-100 d-flex align-items-center justify-content-center menuBtn"
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="dropdownMenuButton1"
                                    style="--bs-dropdown-item-padding-y: 0.4rem;">
                                    <small class="text-muted fw-semibold ms-3">More Actions</small>
                                    @can('amazon-ads.search-terms.export')
                                        <li>
                                            <a href="{{ route('admin.searchterms.export', request()->query()) }}"
                                                class="dropdown-item d-flex align-items-center"
                                                onclick="return confirm('Download Search Terms report in Excel?');">
                                                <i class="mdi mdi-file-excel font-size-18 label-icon text-success me-1"></i>
                                                Excel Export
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Campaign Id</th>
                                    <th>Campaign Name</th>
                                    <th>Keyword Id</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>Country</th>
                                    @if (empty(request('days')))
                                        <th>Date</th>
                                    @endif
                                    <th>Keyword Name</th>
                                    @if (empty(request('days')))
                                        <th>Search Terms</th>
                                    @endif
                                    <th>Impressions</th>
                                    <th>Click</th>
                                    <th>CPC</th>
                                    <th>Cost</th>
                                    <th>Purchases</th>
                                    <th>Sales</th>
                                    <th>Keyword Type</th>
                                    <th>Bid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($searchTerms as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if (request('days') && in_array(request('days'), [7, 14]))
                                                <a href="javascript:void(0)"
                                                    onclick="Livewire.dispatch('open-search-term-modal', { keywordId:'{{ $row->keyword_id }}', days:'{{ request('days') }}' })"
                                                    class="text-success fw-bold">
                                                    {{ $row->campaign_id }}
                                                </a>
                                            @else
                                                {{ $row->campaign_id }}
                                            @endif
                                        </td>
                                        <td class="ellipsis-text" title="{{ $row->campaign_name ?? '' }}">
                                            {{ $row->campaign_name ?? '--' }}
                                        </td>
                                        <td>{{ $row->keyword_id }}</td>
                                        <td>
                                            {{ $row->asin ?? '-' }}
                                        </td>
                                        <td>{{ $row->product_name ?? '-' }}</td>
                                        <td>{{ strtoupper($row->country) }}</td>
                                        @if (empty(request('days')))
                                            <td>{{ $row->formatted_date }}</td>
                                        @endif
                                        <td>{{ $row->keyword }}</td>
                                        @if (empty(request('days')))
                                            <td>{{ $row->search_term }}</td>
                                        @endif
                                        <td class="table-info">{{ number_format($row->impressions) }}</td>
                                        <td class="table-success">{{ number_format($row->clicks) }}</td>
                                        <td class="table-success">${{ number_format($row->cost_per_click, 2) }}</td>
                                        <td class="table-success">${{ number_format($row->cost, 2) }}</td>
                                        <td>{{ number_format($row->purchases_7d) }}</td>
                                        <td class="table-warning">${{ number_format($row->sales_7d, 2) }}</td>
                                        <td>{{ strtoupper($row->keyword_type) }}</td>
                                        <td class="table-danger">{{ number_format($row->keyword_bid, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center text-danger">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                        <!-- end table -->
                    </div>
                    <div class="mt-2">
                        {{ $searchTerms->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                    <p class="text-muted mb-0">
                        @if (request('days') && in_array(request('days'), [7, 14]))
                            <p>Click on a <strong class="text-success fw-bold">Campaign ID</strong> to view detailed search
                                terms.</p>
                        @endif
                    </p>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>

    <!-- Modal -->
    <livewire:ads.search-term-modal />

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
                    url: '{{ route('admin.searchterms.productAsins') }}',
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
@endsection
