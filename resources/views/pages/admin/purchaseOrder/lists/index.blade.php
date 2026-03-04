@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">All Purchase Order List (upcoming 8 weeks)</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="overflow: visible;">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.purchaseOrder.allPurchaseOrders') }}"
                                class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="sticky-column-id">#</th>
                                    <th class="sticky-column-0">SKU</th>
                                    @foreach ($columnHeaders as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($skuMatrix as $row)
                                    <tr>
                                        <td class="sticky-column-id">{{ $loop->iteration }}</td>
                                        @if ($row['delayed'] == true)
                                            <td class="sticky-column-0 p-0">
                                                <div class="w-100 h-100 px-3 py-2 bg-light">
                                                    <a href="{{ route('admin.purchaseOrder.delayedLists', $row['sku']) }}"
                                                        class="d-block text-danger fw-semibold">
                                                        {{ $row['sku'] }}
                                                    </a>
                                                </div>
                                            </td>
                                        @else
                                            <td class="sticky-column-0">
                                                {{ $row['sku'] }}
                                            </td>
                                        @endif
                                        @foreach ($row['weeks'] as $qty)
                                            <td>{{ $qty == 0 ? '--' : $qty }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $skuMatrix->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <p class="text-muted">
                        <strong class="text-danger">SKU Highlight:</strong> Products with delayed shipments are highlighted
                        in red for quick identification.
                    </p>
                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> This table displays purchase orders adjusted by
                        each product's Order Lead
                        Time (in weeks) and grouped into the nearest upcoming 8 weeks.
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
            }

            .table thead th.sticky-column-id,
            .table tbody td.sticky-column-id {
                position: sticky;
                left: 0;
                background-color: var(--bs-table-bg, #fff);
                z-index: 2;
                white-space: nowrap;
                min-width: 50px;
                max-width: 80px;
            }

            .table-striped tbody tr:nth-of-type(odd) td.sticky-column-id {
                background-color: var(--bs-table-striped-bg, #f9f9f9);
            }

            .table thead th.sticky-column-0,
            .table tbody td.sticky-column-0 {
                position: sticky;
                left: 0;
                /* background-color: var(--bs-table-bg, #fff); */
                z-index: 2;
                white-space: nowrap;
                min-width: 120px;
                max-width: 200px;
            }

            .table-striped tbody tr:nth-of-type(odd) td.sticky-column-0 {
                /* background-color: var(--bs-table-striped-bg, #f9f9f9); */
            }

            .table th,
            .table td {
                min-width: 150px;
                white-space: nowrap;
                text-align: center
            }
        </style>
    @endpush
@endsection
