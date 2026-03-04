@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">All Shipments List</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4">
                            <form method="GET" action="{{ route('admin.shipments.shipmentLists') }}"
                                class="d-inline-block me-2 mb-2">
                                 <!-- Search -->
                                <x-elements.search-box />
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light table-striped">
                                <tr>
                                    <th class="sticky-column-0">#</th>
                                    <th class="sticky-column-1">SKU</th>
                                    @php
                                        $statusClasses = [
                                            'planned' => 'primary',
                                            'shipped' => 'info',
                                            'in_transit' => 'warning',
                                            'received' => 'success',
                                            'cancelled' => 'danger',
                                        ];
                                    @endphp
                                    @foreach ($columns as $shipment)
                                        <th>
                                            <a href="{{ route('admin.shipments.edit', $shipment['id']) }}">
                                                <div class="fw-bold text-uppercase text-success">
                                                    {{ $shipment['shipment_name'] ?? '--' }}</div>
                                            </a>
                                            <div class="text-muted small">Carrier : {{ $shipment['carrier_name'] ?? '--' }}
                                            </div>
                                            <div class="text-muted small">Expt Arrival :
                                                {{ $shipment['expected_arrival'] ?? '--' }}</div>
                                            <div class="text-muted small">Status :
                                                <span class="badge bg-{{ $statusClasses[$shipment['status']] ?? 'info' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $shipment['status'] ?? '--')) }}
                                                </span>
                                            </div>
                                            <div class="text-muted small">WH :
                                                {{ $shipment['warehouse_name'] ?? '--' }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="">
                                @if (isset($matrix) && count($matrix) > 0)
                                    @foreach ($matrix as $index => $row)
                                        <tr>
                                            <td class="sticky-column-0">{{ $loop->iteration }}</td>
                                            <td class="sticky-column-1">{{ $row['sku'] }}</td>
                                            @foreach ($columns as $shipment)
                                                <td >{{ $row[$shipment['id']] }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $matrix->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> This table displays only shipments with a status of 'planned', 'shipped', or
                        'in-transit'.
                    </p>
                    <!-- end table responsive -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
    @push('style')
        <style>
            .table-responsive {
                overflow-x: auto;
                scroll-padding-left: calc(60px + 120px);
            }

            .table thead th.sticky-column-0,
            .table thead th.sticky-column-1 {
                position: sticky;
                top: 0;
                background-color: var(--bs-table-bg, #f8f9fa);
                z-index: 30;
                white-space: nowrap;
                border-right: 1px solid var(--bs-border-border-color, #dee2e6);
            }

            .table thead th.sticky-column-0 {
                left: 0;
            }

            .table thead th.sticky-column-1 {
                left: 36px;
            }

            .table tbody td.sticky-column-0,
            .table tbody td.sticky-column-1 {
                position: sticky;
                background-color: var(--bs-table-bg, #ffffff);
                z-index: 20;
                white-space: nowrap;
                border-right: 1px solid var(--bs-border-border-color, #dee2e6);
            }

            .table tbody td.sticky-column-0 {
                left: 0;
            }

            .table tbody td.sticky-column-1 {
                left: 36px;
                border-right-color: #729fcd
            }

            .table-striped tbody tr:nth-of-type(odd) .sticky-column-0,
            .table-striped tbody tr:nth-of-type(odd) .sticky-column-1 {
                background-color: var(--bs-table-striped-bg, #f2f2f2);
            }

            .table th,
            .table td {
                min-width : fit-content;
                text-align: center
            }
        </style>
    @endpush
@endsection
