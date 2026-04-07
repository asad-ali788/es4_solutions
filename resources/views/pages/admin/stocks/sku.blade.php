@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Stocks</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
                    @can('stocks.sku')
                        <li class="nav-item">
                            <a href="{{ route('admin.stocks.skuStocks') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::routeIs('admin.stocks.skuStocks') ? 'active' : '' }}">
                                Stocks by SKU
                            </a>
                        </li>
                    @endcan
                    <hr class="d-sm-none my-0">
                    @can('stocks.asin')
                        <li class="nav-item">
                            <a href="{{ route('admin.stocks.asinStocks') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::routeIs('admin.stocks.asinStocks') ? 'active' : '' }}">
                                Stocks by ASIN
                            </a>
                        </li>
                    @endcan
                </ul>
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.stocks.skuStocks') }}" class="row g-2">
                                <x-elements.search-box />
                            </form>
                        </div>

                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2">
                            @can('stocks.sku-export')
                                <div class="flex-fill flex-lg-none w-100">
                                    <a class="w-100 w-lg-auto"
                                        onclick="return confirm('Do you want to export the stock data to Excel?');"
                                        href="{{ route('admin.stocks.exportSku', ['search' => request('search')]) }}">
                                        <button type="button"
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 w-lg-auto">
                                            <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                                        </button>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>SKU</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>
                                        <div>AFN</div>
                                        <div class="timestamp">Updated at:
                                            {{ \Carbon\Carbon::parse($lastUpdated['afn'])->format('M-d H:i') }}</div>
                                    </th>
                                    <!-- <th>
                                        <div>FBA</div>
                                        <div class="timestamp">Updated at:
                                            {{ \Carbon\Carbon::parse($lastUpdated['fba'])->format('M-d H:i') }}</div>
                                    </th> -->
                                    <th>
                                        <div>Inbound</div>
                                        <div class="timestamp">Updated at:
                                            {{ \Carbon\Carbon::parse($lastUpdated['inbound'])->format('M-d H:i') }}</div>
                                    </th>

                                    @foreach ($warehouses as $wh)
                                        <th>
                                            <div>{{ $wh->warehouse_name }}</div>
                                            <div class="timestamp">
                                                Updated
                                                at:
                                                {{ isset($lastUpdated['warehouses'][$wh->id])
                                                    ? \Carbon\Carbon::parse($lastUpdated['warehouses'][$wh->id])->format('M-d H:i')
                                                    : '—' }}
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stocks as $stock)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $stock->sku }}</td>
                                        <td>{{ $stock->asin ?? '—' }}</td>
                                        <td>{{ $stock->product_name ?? '—' }}</td>
                                        <td>{{ $stock->afn_quantity }}</td>
                                        <!-- <td>{{ $stock->fba_totalstock }}</td> -->
                                        <td>{{ $stock->inbound_qty }}</td>
                                        @foreach ($warehouses as $wh)
                                            <td>{{ $stock->{'wh_' . $wh->id . '_stock'} }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-2">
                            {{ $stocks->appends(request()->query())->links('pagination::bootstrap-5') }}
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
@endsection
