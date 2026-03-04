@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">All Warehouse Stocks</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-2">
            <!-- Search & Export -->
            <div class="row g-3 align-items-center justify-content-between mb-3">
                <div class="col-lg-9">
                    <form method="GET" action="{{ route('admin.warehouse.allWarehouseInventory') }}" class="row g-2">
                        <!-- Search -->
                        <x-elements.search-box />
                    </form>
                </div>
                <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2">
                    <div class="flex-fill flex-lg-none" style="min-width: 0;">
                        <a href="{{ route('admin.warehouse.exportExcel') }}"
                            onclick="return confirm('Export all warehouse Stocks to Excel?')">
                            <button class="btn btn-success btn-rounded waves-effect waves-light w-100">
                                <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                            </button>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table align-middle nowrap w-100 table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>SKU</th>
                            @foreach ($warehouses as $wh)
                                <th>
                                    {{ $wh->warehouse_name }}
                                    <div class="timestamp">
                                        Updated at:
                                        {{ $lastUpdated['warehouses'][$wh->id] ? \Carbon\Carbon::parse($lastUpdated['warehouses'][$wh->id])->format('M-d H:i') : '--' }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $index => $row)
                            <tr>
                                <td>{{ $stocks->firstItem() + $index }}</td>
                                <td>{{ $row->sku }}</td>
                                @foreach ($warehouses as $wh)
                                    <td>{{ $row->{'wh_' . $wh->id . '_stock'} }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + count($warehouses) }}" class="text-center text-muted">
                                    No inventory data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="mt-2">
                {{ $stocks->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
