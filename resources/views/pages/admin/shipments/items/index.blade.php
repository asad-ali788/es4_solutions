@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">
                        Shipment Items -
                        <span class="text-success">
                            {{ $items->first()->shipment->shipment_name ?? 'No Items available for the Shipment' }}
                        </span>
                    </h4>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.shipments.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Shipments
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
                            <form method="GET" action="{{ route('admin.shipments.items', $shipmentId) }}"
                                class="d-inline-block me-2 mb-2">
                                <div class="search-box position-relative">
                                    <input type="text" name="search" class="form-control" placeholder="Enter to Search ..."
                                        value="{{ request('search') }}">
                                    <i class="bx bx-search-alt search-icon"></i>
                                </div>
                            </form>
                        </div>
                        <div class="col-sm-8 text-end">
                            @if (isset($items) && count($items) > 0)
                            <a href="{{ asset('assets/example/example_updateShipmentQuantity.xlsx') }}" download>
                                <button class="btn btn-primary btn-rounded waves-effect waves-light mb-2 me-2">
                                    <i class="mdi mdi-download me-1"></i>Example Excel
                                </button>
                            </a>

                            <!-- Hidden Upload Form -->
                            <form id="excelUploadForm" action="{{ route('admin.shipments.updateShipments', $shipmentId) }}"
                                method="POST" enctype="multipart/form-data" style="display: none;">
                                @csrf
                                <input type="file" name="excel_file" id="excelFileInput" accept=".xlsx,.xls">
                            </form>

                            <!-- Trigger Button -->
                            <button type="button" class="btn btn-primary btn-rounded waves-effect waves-light mb-2 me-2"
                                onclick="$('#excelFileInput').click();">
                                <i class="mdi mdi-upload me-1"></i> Upload Recieived Shipment
                            </button>
                            @endif
                            <a href="{{ route('admin.shipments.itemCreate', $shipmentId) }}">
                                <button
                                    class="btn btn-success btn-rounded waves-effect waves-light mb-2 me-2 addCustomers-modal">
                                    <i class="mdi mdi-plus me-1"></i> New Shipment Item
                                </button>
                            </a>
                        </div>
                        <!-- end col-->
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product SKU</th>
                                    <th>Ordered Qty</th>
                                    <th>Received Qty</th>
                                    <th>Unit Cost</th>
                                    <th>Total Cost</th>
                                    <th>Status</th>
                                    <th>Updated at</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $statusClasses = [
                                        'pending'  => 'warning',
                                        'received' => 'success',
                                        'damaged'  => 'danger',
                                        'short'    => 'primary',
                                    ];
                                @endphp
                                @if (isset($items) && count($items) > 0)
                                    @foreach ($items as $index => $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->product->sku ?? '-' }}</td>
                                            <td>{{ $item->quantity_ordered ?? 0 }}</td>
                                            <td>{{ $item->quantity_received ?? 0 }}</td>
                                            <td>${{ number_format($item->unit_cost, 2) }}</td>
                                            <td>${{ number_format($item->total_cost, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $statusClasses[$item->status] ?? 'dark' }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $item->updated_at ?? '-' }}</td>
                                            <td>
                                                <!-- Action Dropdown -->
                                                <div class="dropdown">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="{{ route('admin.shipments.itemEdit', $item->id) }}"
                                                                class="dropdown-item">
                                                                <i
                                                                    class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form
                                                                action="{{ route('admin.shipments.itemDelete', $item->id) }}"
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
                            {{ $items->appends(request()->query())->links('pagination::bootstrap-5') }}
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
    <!-- Auto-submit on file selection -->
    @push('scripts')
        @if (session('skipped_file'))
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const downloadLink          = document.createElement('a');
                          downloadLink.href     = "{{ asset('storage/temp/' . session('skipped_file')) }}";
                          downloadLink.download = 'skipped_items.xlsx';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                });
            </script>
        @endif
        <script>
            $(document).ready(function() {
                $('#excelFileInput').on('change', function() {
                    const fileInput = this;
                    if (fileInput.files.length > 0) {
                        const fileName = fileInput.files[0].name;
                        // Delay confirm to ensure it's not auto-skipped
                        setTimeout(function() {
                            if (confirm(`Are you sure you want to upload "${fileName}"?`)) {
                                $('#excelUploadForm').submit();
                            } else {
                                $(fileInput).val('');
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endsection
