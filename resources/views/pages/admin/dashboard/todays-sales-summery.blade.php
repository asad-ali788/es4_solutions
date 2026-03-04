@extends('layouts.app')

@section('content')
    {{-- Page Title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">
                        Today Ads Detailed Sales Summary
                    </h4>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Dashboard
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard.detailed-todays-sales-summery') }}"
                            class="nav-link w-100 w-sm-auto {{ Request::routeIs('admin.dashboard.detailed-todays-sales-summery') ? 'active' : '' }}">
                            Sales Summary
                        </a>
                    </li>

                    <hr class="d-sm-none my-0">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard.snapshot-todays-sales-summery') }}"
                            class="nav-link w-100 w-sm-auto {{ Request::routeIs('admin.dashboard.snapshot-todays-sales-summery') ? 'active' : '' }}">
                            Sales Snapshot
                        </a>
                    </li>
                </ul>
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-2">

                        {{-- Left: Search + Filters --}}
                        <div class="col-lg-10 d-flex flex-wrap align-items-center gap-2">
                            <form method="GET"
                                action="{{ route('admin.dashboard.detailed-todays-sales-summery', request()->query()) }}"
                                class="d-flex flex-wrap align-items-center gap-2 w-100">

                                {{-- Search (reuse your component) --}}
                                <x-elements.search-box />

                                {{-- Type --}}
                                <div class="form-floating">
                                    <select name="type" id="type" class="form-select custom-dropdown-small"
                                        onchange="this.form.submit()">
                                        <option value="">All Types</option>
                                        <option value="SP" @selected(request('type') === 'SP' || request('type') === 'sp')>SP</option>
                                        <option value="SB" @selected(request('type') === 'SB' || request('type') === 'sb')>SB</option>
                                        <option value="SD" @selected(request('type') === 'SD' || request('type') === 'sd')>SD</option>
                                    </select>
                                    <label for="type">Type</label>
                                </div>
                            </form>
                        </div>
                    </div>
                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Product Name</th>
                                    <th>Country</th>
                                    <th>Campaign ID</th>
                                    <th class="text-end">Sales (USD)</th>
                                    <th class="text-end">Spend (USD)</th>
                                    <th class="text-end">ACOS %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startIndex = ($rows->currentPage() - 1) * $rows->perPage();
                                @endphp
                                @forelse ($rows as $i => $r)
                                    @php
                                        $sales = (float) ($r->sales_usd ?? 0);
                                        $spend = (float) ($r->spend_usd ?? 0);
                                        $acos = $sales > 0 ? ($spend / $sales) * 100 : 0;
                                    @endphp

                                    <tr>
                                        <td>{{ $startIndex + $i + 1 }}</td>

                                        <td>{{ \Carbon\Carbon::parse($r->report_date)->format('d M Y') }}</td>

                                        <td class="fw-semibold">
                                            @if ($r->type === 'SP')
                                                <span class="badge bg-primary">SP</span>
                                            @elseif ($r->type === 'SB')
                                                <span class="badge bg-info text-dark">SB</span>
                                            @else
                                                <span class="badge bg-warning text-dark">SD</span>
                                            @endif
                                        </td>

                                        <td class="fw-semibold">{{ $r->product_names ?? '--' }}</td>
                                        <td class="table-light fw-semibold">{{ $r->country ?? '--' }}</td>

                                        <td class="text-muted">{{ $r->campaign_id ?? '--' }}</td>


                                        <td class="table-success fw-semibold text-end">
                                            {{ number_format($sales, 2) }}
                                        </td>

                                        <td class="table-info fw-semibold text-end">
                                            {{ number_format($spend, 2) }}
                                        </td>

                                        <td class="table-warning fw-semibold text-end">
                                            {{ number_format($acos, 2) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No records found for today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- Pagination --}}
                    <div class="px-3 pb-3">
                        {{ $rows->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
