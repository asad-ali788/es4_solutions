@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Amazon Ads - Performance Recommendations</h4>
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
                        <div class="col-12">
                            <form method="GET" action="{{ route('admin.ads.performance.asins.index') }}"
                                class="row g-2 align-items-center" id="filterForm">
                                {{-- Search --}}
                                <x-elements.search-box name="search" placeholder="Search…" />
                                {{-- Country --}}
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                {{-- Campaign --}}
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB']" />
                                {{-- Week --}}
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select name="week" id="week" class="form-select custom-dropdown-small"
                                            onchange="this.form.submit()">
                                            @foreach ($weeks as $week)
                                                <option value="{{ $week }}"
                                                    {{ $selectedWeek == $week ? 'selected' : '' }}>
                                                    {{ $week }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="week">Select Week</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive custom-sticky-wrapper mt-2">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>Report Week</th>
                                    <th>Active Campaigns</th>
                                    <th>Country</th>
                                    <th>Total Daily Budget</th>
                                    <th>Spend</th>
                                    <th>Sales</th>
                                    <th>ACoS (%)</th>
                                    <th>Campaign Type</th>
                                    <th style="width: 200px;">Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($asins->count() > 0)
                                    @foreach ($asins as $index => $asin)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $asin->asin ?? 'N/A' }}</td>
                                            <td>{{ $asin->product_name ?? 'N/A' }}</td>
                                            <td>
                                                @if ($asin->report_week)
                                                    @php
                                                        $start = \Carbon\Carbon::parse($asin->report_week)->startOfWeek(
                                                            \Carbon\Carbon::MONDAY,
                                                        );
                                                        $end = $start->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                                                    @endphp
                                                    {{ $start->format('Y-m-d') }}
                                                    to {{ $end->format('d') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $asin->active_campaigns }}</td>
                                            <td>{{ $asin->country ?? 'N/A' }}</td>
                                            <td class="table-success">
                                                ${{ number_format($asin->total_daily_budget, 2) ?? 'N/A' }}</td>
                                            <td class="table-warning">${{ number_format($asin->total_spend, 2) ?? 'N/A' }}
                                            </td>
                                            <td class="table-warning">${{ number_format($asin->total_sales, 2) ?? 'N/A' }}
                                            </td>
                                            <td class="table-warning">{{ $asin->acos ?? 'N/A' }}%</td>
                                            <td>{{ $asin->campaign_types ?? 'N/A' }}</td>
                                            <td style="word-break: break-word; white-space: normal;">
                                                @php
                                                    $icons = [
                                                        'Keep same budget (optimize keywords/placements)' => '🤔',
                                                        'Increase budget 30%' => '🔼',
                                                        'Keep same budget' => '✅',
                                                        'Reduce budget by 20%' => '🔽',
                                                    ];
                                                    $rec = $asin->recommendation ?? '-';
                                                    $icon = collect($icons)->first(function ($_, $key) use ($rec) {
                                                        return str_starts_with($rec, $key);
                                                    });
                                                @endphp
                                                {!! $icon ? $icon . ' ' . e($rec) : e($rec) !!}
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
                        <!-- end table -->
                    </div>
                    <div class="mt-2">
                        {{ $asins->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
