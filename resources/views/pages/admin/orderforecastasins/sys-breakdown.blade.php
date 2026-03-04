@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0 font-size-18">SYS SOLD – Seasonal Breakdown (ASIN: {{ $asin }})</h4>
                </div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.orderforecastasin.show', $forecastId) }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Forecast
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>



    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">

                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover"
                            id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th>Sales</th>
                                    <th>Seasonality Index</th>
                                    <th>Base Forecast</th>
                                    <th>Multiplier (S/R)</th>
                                    <th>Adjusted SYS SOLD</th>
                                    <th>Safety Floor</th>
                                    {{-- <th>Final Safety Floor</th> --}}
                                    <th>Final SYS SOLD</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($breakdown as $month => $row)
                                    <tr>
                                        <td>{{ $month }}</td>
                                        <td>{{ $row['actual_sold'] }}</td>
                                        <td>{{ $row['seasonality_index'] }}</td>
                                        <td>{{ $row['base_forecast'] }}</td>
                                        <td>{{ $row['weights'] }} ({{ $row['month_type'] }})</td>
                                        <td>{{ $row['final_sys_sold'] }}</td>
                                        <td>{{ $row['safety_floor'] }}</td>
                                        {{-- <td>{{ $row['final_without_floor'] }}</td> --}}
                                        <td><b>{{ $row['final_sys_sold'] }}</b></td>
                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>


                </div>
            </div>
        </div>
    </div>

    {{-- Textual Breakdown --}}

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body bg-light" style="font-family: monospace; font-size: 13px;">
                    @foreach ($breakdown as $month => $row)
                        @php
                            [$seasonalWeight, $recentWeight] = explode(' / ', $row['weights']);
                        @endphp

                        <div class="mb-4 p-3 border rounded bg-white">

                            <div class="mb-2">
                                <b>📅 Month:</b> {{ $month }}
                            </div>

                            <div>• Actual Sales = <b>{{ $row['actual_sold'] }}</b></div>
                            <div>• Annual Total = {{ round($annualTotal) }}</div>
                            <div>• Monthly Avg = {{ round($monthlyAvg, 2) }}</div>

                            <hr class="my-2">

                            <div>
                                • Seasonality Index =
                                {{ $row['actual_sold'] }} ÷ {{ round($annualTotal) }}
                                = <b>{{ $row['seasonality_index'] }}</b>
                            </div>

                            <div>
                                • Growth Applied = {{ $effectiveGrowth * 100 }}%
                                @if ($isSeasonal)
                                    <span class="text-warning">(Capped for Seasonal ASIN)</span>
                                @endif
                            </div>

                            <div>
                                • Next Year Total =
                                {{ round($annualTotal) }} × (1 + {{ $effectiveGrowth }})
                                = {{ round($annualTotal * (1 + $effectiveGrowth), 2) }}
                            </div>

                            <div>
                                • Base Forecast =
                                round({{ $row['seasonality_index'] }} ×
                                {{ round($annualTotal * (1 + $effectiveGrowth), 2) }})
                                = <b>{{ $row['base_forecast'] }}</b>
                            </div>

                            <hr class="my-2">

                            <div>• Month Type = <b>{{ $row['month_type'] }}</b></div>

                            <div>• Weights Applied =
                                Seasonal {{ $seasonalWeight }} + Recent Avg {{ $recentWeight }}
                            </div>

                            <div>
                                • Adjusted Forecast =
                                ({{ $row['base_forecast'] }} × {{ $seasonalWeight }})
                                +
                                ({{ $row['recent_avg'] }} × {{ $recentWeight }})
                            </div>

                            <hr class="my-2">

                            <div>
                                • Safety Floor =
                                {{ $row['actual_sold'] }} × 0.6
                                = <b>{{ $row['safety_floor'] }}</b>
                            </div>

                            @if (!empty($row['override_applied']))
                                <div class="text-danger">
                                    🔥 Seasonal Override Applied
                                    (Off-season inflation guard triggered → fallback to Base Forecast)
                                </div>
                            @else
                                <div class="text-success">
                                    ✔ No Override Needed
                                </div>
                            @endif

                            <hr class="my-2">

                            <div class="fw-bold fs-6">
                                ✅ Final SYS SOLD = {{ $row['final_sys_sold'] }}
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
