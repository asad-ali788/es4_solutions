@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Delayed Items for SKU - <span class="text-success">
                        {{ $product->sku }}</span></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.purchaseOrder.allPurchaseOrders') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to All Purchase Order List
                            </a>
                        </li>
                    </ol>
                </div>
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
                            <form method="GET" action="#" class="d-inline-block me-2 mb-2">
                                <div class="search-box position-relative">
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Number</th>
                                    <th>SKU</th>
                                    <th>Order Date</th>
                                    <th>Quantity Ordered</th>
                                    <th>Expected Date</th>
                                    <th>Delayed New Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>{{ $item['order_number'] ?? '--' }}</td>
                                        <td>{{ $item['sku'] ?? '--' }}</td>
                                        <td>{{ $item['order_date'] ?? '--' }}</td>
                                        <td>{{ $item['quantity_ordered'] ?? '--' }}</td>
                                        <td>{{ $item['expected_date'] ?? '--' }}</td>
                                        <td>{{ $item['delayed_new_date'] ?? '--' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No delayed items found for this SKU.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
@endsection
