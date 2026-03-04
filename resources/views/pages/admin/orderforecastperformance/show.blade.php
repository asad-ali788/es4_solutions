@extends('layouts.app')
@section('content')
    <style>
        thead th {
            vertical-align: middle !important;
            white-space: nowrap;
        }

        .th-multiline {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            line-height: 1.2;
        }

        .th-multiline small {
            font-size: 11px;
            margin-top: 2px;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">Forecast Performance</h4>
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

    {{-- @dd($records->take(5)) --}}

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9 d-flex align-items-center">
                            <form method="GET" action="{{ route('admin.forecastperformance.index') }}"
                                class="row g-2 w-100">

                                <x-elements.search-box />

                                <div class="col-auto">
                                    <div class="form-floating">
                                        <input type="month" name="month" class="form-control"
                                            value="{{ request('month', $monthStart->format('Y-m')) }}"
                                            max="{{ now(config('timezone.market'))->format('Y-m') }}"
                                            onchange="this.form.submit()">
                                        <label>Month</label>
                                    </div>
                                </div>

                                {{-- FCF filter --}}
                                <div class="col-auto">
                                    <div class="form-floating">
                                        <select name="fcf_filter" class="form-select custom-dropdown-small"
                                            onchange="this.form.submit()">
                                            <option value="">All Fulfillment</option>
                                            <option value="gt100" {{ request('fcf_filter') == 'gt100' ? 'selected' : '' }}>
                                                Greater than 100</option>
                                            <option value="50to60"
                                                {{ request('fcf_filter') == '50to60' ? 'selected' : '' }}>50 – 60</option>
                                            <option value="60to70"
                                                {{ request('fcf_filter') == '60to70' ? 'selected' : '' }}>60 – 70</option>
                                            <option value="70to80"
                                                {{ request('fcf_filter') == '70to80' ? 'selected' : '' }}>70 – 80</option>
                                            <option value="80to90"
                                                {{ request('fcf_filter') == '80to90' ? 'selected' : '' }}>80 – 90</option>
                                            <option value="lt50" {{ request('fcf_filter') == 'lt50' ? 'selected' : '' }}>
                                                Less than 50</option>
                                        </select>
                                        <label>FCF % (Full Month)</label>
                                    </div>
                                </div>

                                {{-- ACOS filter --}}
                                <div class="col-auto">
                                    <div class="form-floating">
                                        <select name="acos_filter" class="form-select custom-dropdown-small"
                                            onchange="this.form.submit()">
                                            <option value="">All ACOS</option>
                                            <option value="lt30"
                                                {{ request('acos_filter') == 'lt30' ? 'selected' : '' }}>
                                                Less than 30</option>
                                            <option value="30to40"
                                                {{ request('acos_filter') == '30to40' ? 'selected' : '' }}>30 – 40</option>
                                            <option value="gt40"
                                                {{ request('acos_filter') == 'gt40' ? 'selected' : '' }}>Greater than 40
                                            </option>
                                        </select>
                                        <label>ACOS %</label>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- ✅ RIGHT END ACTIONS --}}
                        <div class="col-lg-3 d-flex justify-content-end gap-2">
                            <form action="{{ route('admin.forecastperformance.clearCache') }}" method="GET">
                                <button type="submit" class="btn btn-light btn-sm d-flex align-items-center">
                                    <i class="bx bx-loader text-primary me-1"></i> Refresh
                                </button>
                            </form>

                            <a href="{{ route('admin.forecastperformance.export', request()->query()) }}"
                                class="btn btn-success btn-sm d-flex align-items-center" title="Download Excel">
                                <i class="mdi mdi-file-excel me-1"></i> Export
                            </a>
                        </div>
                    </div>


                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100" id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>Agent Name</th>
                                    <th>Country</th>
                                    <th class="th-multiline">
                                        {{ $monthLabel }} Forecast
                                        <small class="text-muted">(Actual sold – last year)</small>
                                    </th>

                                    <th>{{ $monthLabel }} Sold</th>
                                    <th>Full Month Sales Projection</th>
                                    <th>Full Month Unit Delta</th>
                                    <th>Daily Forecast</th>
                                    <th>Daily Rate of Sale</th>
                                    <th>FCF % (Full Month)</th>
                                    <th>Last 7 Days Sold</th>
                                    <th>Last 14 Days Sold</th>
                                    <th>FCF % (7 Days)</th>
                                    <th>Amz Stock</th>
                                    <th>WH Stock</th>
                                    <th>Route Stock</th>
                                    <th>Total Spend (Ads)</th>
                                    <th>Total Sales (Ads)</th>
                                    <th>Total Unit Sold (Ads)</th>
                                    <th>ACoS %</th>
                                    <th>AFN 3PL Route__HV SR</th>
                                    <th>AFN 3PL Route__FC SR</th>

                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($records) && count($records) > 0)
                                    @foreach ($records as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->product_asin ?? '--' }}</td>
                                            <td>{{ $item->product_name ?? '--' }}</td>
                                            <td>{{ $item->agent_name ?? '--' }}</td>
                                            <td>{{ $country ?? 'North America' }}</td>

                                            <td>{{ number_format($item->forecast_units ?? 0) }}</td>
                                            <td>{{ number_format($item->month_sold ?? 0) }}</td>

                                            <td>{{ number_format($item->full_month_projection ?? 0) }}</td>
                                            <td
                                                class="{{ ($item->full_month_delta ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($item->full_month_delta ?? 0) }}
                                            </td>

                                            <td class="table-info">{{ number_format($item->daily_forecast ?? 0) }}</td>
                                            <td class="table-info">{{ number_format($item->daily_rate_of_sale ?? 0) }}</td>

                                            <td class="table-success">
                                                {{ !empty($item->fcf_full_month) && $item->fcf_full_month > 0 ? number_format($item->fcf_full_month, 2) . '%' : '-' }}
                                            </td>

                                            <td>{{ number_format($item->last_7_days_sold ?? 0) }}</td>
                                            <td>{{ number_format($item->last_14_days_sold ?? 0) }}</td>
                                            <td class="table-success">
                                                @php
                                                    $fcf = $item->fcf_7_days ?? null;
                                                @endphp

                                                @if ($fcf && $fcf > 0)
                                                    {{ number_format($fcf, 2) }}%

                                                    @if ($fcf < 50)
                                                        <i class="bx bx-chevrons-down text-danger fw-bold ms-1"
                                                            title="Very Low (<50%)"></i>
                                                    @elseif ($fcf >= 50 && $fcf < 60)
                                                        <i class="bx bx-down-arrow-alt text-danger ms-1"
                                                            title="Low (50–60%)"></i>
                                                    @elseif ($fcf >= 60 && $fcf < 70)
                                                        <i class="bx bx-right-arrow-alt text-warning ms-1"
                                                            title="Moderate (60–70%)"></i>
                                                    @elseif ($fcf >= 70 && $fcf < 80)
                                                        <i class="bx bx-up-arrow-alt text-success ms-1"
                                                            title="Good (70–80%)"></i>
                                                    @elseif ($fcf >= 80 && $fcf <= 90)
                                                        <i class="bx bx-chevrons-up text-success fw-bold ms-1"
                                                            title="Very Good (80–90%)"></i>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>


                                            <td class="table-primary">{{ number_format($item->amazon_stock ?? 0) }}</td>
                                            <td class="table-primary">{{ number_format($item->warehouse_stock ?? 0) }}</td>
                                            <td class="table-primary">{{ number_format($item->route_stock ?? 0) }}</td>

                                            <td class="table-light">{{ number_format($item->ad_spend ?? 0, 2) }}</td>
                                            <td class="table-light">{{ number_format($item->ad_sales ?? 0, 2) }}</td>
                                            <td class="table-light">{{ number_format($item->total_ads_units ?? 0) }}</td>
                                            <td class="table-warning">{{ number_format($item->acos ?? 0, 2) }}%</td>

                                            <td>{{ number_format($item->afn3pl ?? 0) }}</td>
                                            <td>
                                                {{ ($item->afn3pl_fc_sr ?? 0) >= 180 ? '180+ days' : round($item->afn3pl_fc_sr ?? 0) . ' days' }}
                                                {{-- {{ round($item->afn3pl_fc_sr ?? 0) . ' days' }} --}}
                                            </td>

                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="25" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $records->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
