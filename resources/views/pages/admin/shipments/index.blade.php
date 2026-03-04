@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Shipments</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        {{-- Search --}}
                        <div class="col-12 col-lg-6">
                            <form method="GET" action="{{ route('admin.shipments.index') }}" class="row g-2">
                                <x-elements.search-box />
                            </form>
                        </div>
                        {{-- Button --}}
                        <div class="col-12 col-lg-auto ms-lg-auto">
                            <div class="row g-2 justify-content-lg-end">
                                <div class="col-12 col-sm-auto d-grid d-sm-block">
                                    <a href="{{ route('admin.shipments.create') }}" class="w-100">
                                        <button type="button"
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 text-nowrap">
                                            <i class="mdi mdi-plus me-1"></i> New Shipment
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Shipment Name</th>
                                    <th>Warehouse</th>
                                    <th>Supplier</th>
                                    <th>Carrier Name</th>
                                    <th>Tracking Number</th>
                                    <th>Dispatch Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $statusClasses = [
                                        'planned' => 'primary',
                                        'shipped' => 'info',
                                        'in_transit' => 'warning',
                                        'received' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                @endphp
                                @if (isset($shipments) && count($shipments) > 0)
                                    @foreach ($shipments as $index => $shipment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <a href="{{ route('admin.shipments.items', $shipment->id) }}">
                                                    {{ $shipment->shipment_name ?? '--' }} </a>
                                            </td>
                                            <td>{{ $shipment->warehouse->warehouse_name ?? '-' }}</td>
                                            <td>{{ $shipment->supplier->name ?? '-' }}</td>
                                            <td>{{ $shipment->carrier_name ?? '--' }}</td>
                                            <td>{{ $shipment->tracking_number ?? '-' }}</td>
                                            <td>{{ $shipment->dispatch_date ? \Carbon\Carbon::parse($shipment->dispatch_date)->format('d M Y') : '-' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $statusClasses[$shipment->status] ?? 'dark' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $shipment->status ?? '--')) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown" style="position: relative;">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="{{ route('admin.shipments.edit', $shipment->id) }}"
                                                                class="dropdown-item">
                                                                <i
                                                                    class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.shipments.items', $shipment->id) }}"
                                                                class="dropdown-item">
                                                                <i class="bx bx-detail font-size-16 text-success me-1"></i>
                                                                Items
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.shipments.itemExport', $shipment->id) }}"
                                                                onclick="return confirm('Are you sure?');"
                                                                class="dropdown-item">
                                                                <i
                                                                    class="mdi mdi-file-excel label-icon font-size-16 text-success me-1"></i>
                                                                Export
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form
                                                                action="{{ route('admin.shipments.destroy', $shipment->id) }}"
                                                                method="POST" onsubmit="return confirm('Are you sure?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i
                                                                        class="mdi mdi-trash-can font-size-16 text-danger me-1"></i>
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
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

                        <div class="mt-2">
                            {{ $shipments->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>

                        <!-- end table -->
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
    @if (session('skipped_file'))
        <script>
            $(document).ready(function() {
                var downloadLink = $('<a>', {
                    href: "{{ asset('storage/temp/' . session('skipped_file')) }}",
                    download: 'update_skipped_items.xlsx'
                }).appendTo('body');

                downloadLink[0].click();
                downloadLink.remove();
            });
        </script>
    @endif
@endsection
