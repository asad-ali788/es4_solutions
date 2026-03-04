@extends('layouts.app')

@section('content')
    {{-- Page Title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">
                        Today Ads Sales Snapshot (2-Hour Buckets)
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

    {{-- Tabs --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard.detailed-todays-sales-summery') }}"
                            class="nav-link w-100 w-sm-auto">
                            Sales Summary
                        </a>
                    </li>

                    <hr class="d-sm-none my-0">

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard.snapshot-todays-sales-summery') }}"
                            class="nav-link w-100 w-sm-auto active">
                            Sales Snapshot
                        </a>
                    </li>
                </ul>

                {{-- Filters --}}
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-2">
                        <form method="GET" action="{{ route('admin.dashboard.snapshot-todays-sales-summery') }}"
                            class="row g-2 align-items-center">

                            <div class="col-auto">
                                <div class="form-floating">
                                    <select name="type" id="type" class="form-select custom-dropdown-small"
                                        onchange="this.form.submit()">
                                        <option value="">All Types</option>
                                        <option value="SP" @selected(request('type') === 'SP')>SP</option>
                                        <option value="SB" @selected(request('type') === 'SB')>SB</option>
                                        <option value="SD" @selected(request('type') === 'SD')>SD</option>
                                    </select>
                                    <label for="type">Type</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Time Bucket</th>
                                    <th>Type</th>
                                    <th>Country</th>
                                    <th class="text-end">Sales (USD)</th>
                                    <th class="text-end">Spend (USD)</th>
                                    <th class="text-end">ACOS %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $row->report_date_pst }}
                                        </td>

                                        <td class="fw-semibold">
                                            {{ $row->time_bucket_pst }}
                                        </td>


                                        <td>
                                            @if ($row->type === 'SP')
                                                <span class="badge bg-primary">SP</span>
                                            @elseif ($row->type === 'SB')
                                                <span class="badge bg-info text-dark">SB</span>
                                            @else
                                                <span class="badge bg-warning text-dark">SD</span>
                                            @endif
                                        </td>

                                        <td class="fw-semibold">
                                            {{ $row->country }}
                                        </td>

                                        <td class="text-end table-success fw-semibold">
                                            {{ number_format((float) $row->sales_usd, 2) }}
                                        </td>

                                        <td class="text-end table-info fw-semibold">
                                            {{ number_format((float) $row->spend_usd, 2) }}
                                        </td>

                                        <td class="text-end table-warning fw-semibold">
                                            {{ number_format((float) $row->acos, 2) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No snapshot data available for today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Pagination --}}
                {{-- <div class="px-3 py-3">
                    {{ $rows->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div> --}}
            </div>
        </div>
    </div>
@endsection
