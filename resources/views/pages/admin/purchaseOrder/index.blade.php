@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Purchase Order</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.purchaseOrder.index') }}" class="row g-2">
                                <x-elements.search-box />
                            </form>
                        </div>

                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2">
                            <div class="flex-fill flex-lg-none" style="min-width: 0;">
                                <a href="{{ route('admin.purchaseOrder.create') }}" class="w-100">
                                    <button type="button"
                                        class="btn btn-success btn-rounded waves-effect waves-light w-100 addCustomers-modal">
                                        <i class="mdi mdi-plus me-1"></i> New Purchase Order
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Order Number</th>
                                    <th>Warehouse</th>
                                    <th>Supplier</th>
                                    <th>Order Date</th>
                                    <th>Expected Arrival</th>
                                    <th>Total Cost</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $statusClasses = [
                                        'draft' => 'primary',
                                        'confirmed' => 'info',
                                        'shipped' => 'warning',
                                        'received' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                @endphp
                                @if (isset($purchaseOrder) && count($purchaseOrder) > 0)
                                    @foreach ($purchaseOrder as $index => $order)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <a href="{{ route('admin.purchaseOrder.items', $order->id) }}">
                                                    {{ $order->order_number ?? '--' }} </a>
                                            </td>
                                            <td>{{ $order->warehouse->warehouse_name ?? '-' }}</td>
                                            <td>{{ $order->supplier->name ?? '-' }}</td>
                                            <td>{{ $order->order_date ?? '--' }}</td>
                                            <td>{{ $order->expected_arrival ?? '-' }}</td>
                                            <td></td>
                                            <td>
                                                <span class="badge bg-{{ $statusClasses[$order->status] ?? 'dark' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $order->status ?? '--')) }}
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
                                                            <a href="{{ route('admin.purchaseOrder.edit', $order->id) }}"
                                                                class="dropdown-item">
                                                                <i
                                                                    class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.purchaseOrder.items', $order->id) }}"
                                                                class="dropdown-item">
                                                                <i class="bx bx-detail font-size-16 text-success me-1"></i>
                                                                Order Items
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form
                                                                action="{{ route('admin.purchaseOrder.destroy', $order->id) }}"
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
                            {{ $purchaseOrder->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>

                        <!-- end table -->
                    </div>
                    <!-- end table responsive -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
    </div>
    <!-- end col -->
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
