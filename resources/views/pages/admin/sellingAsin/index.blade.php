@extends('layouts.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Selling</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
                    @can('selling.sku')
                        <li class="nav-item">
                            <a href="{{ route('admin.selling.index') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::is('admin/selling') ? 'active' : '' }}">
                                Selling Item (SKU)
                            </a>
                        </li>
                    @endcan
                    <hr class="d-sm-none my-0">
                    @can('selling.asin')
                        <li class="nav-item">
                            <a href="{{ route('admin.asin-selling.index') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::is('admin/asin-selling*') ? 'active' : '' }}">
                                Selling Item (ASIN)
                            </a>
                        </li>
                    @endcan
                    <hr class="d-sm-none my-0">
                    @can('selling.ads-item')
                        <li class="nav-item">
                            <a href="{{ route('admin.selling.adsItems.index') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::is('admin/sellingAdsItem*') ? 'active' : '' }}">
                                Ads Item (ASIN)
                            </a>
                        </li>
                    @endcan
                </ul>
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.asin-selling.index') }}" class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                                @if (!empty($reportingUsers))
                                    <div class="col col-md-auto">
                                        <div class="form-floating">
                                            <select class="form-select custom-dropdown" name="select"
                                                onchange="this.form.submit()">
                                                <option value="all"
                                                    {{ $targetUserId == 'all' || !$targetUserId ? 'selected' : '' }}>
                                                    All</option>
                                                <option value="{{ auth()->id() }}"
                                                    {{ $targetUserId == auth()->id() ? 'selected' : '' }}>
                                                    {{ auth()->user()->name }} (You)
                                                </option>
                                                @foreach ($reportingUsers as $id => $name)
                                                    <option value="{{ $id }}"
                                                        {{ $targetUserId == $id ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="floatingSelectGrid">Select User</label>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover"
                            id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($asins) && $asins->count() > 0)
                                    @foreach ($asins as $asin)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <a
                                                    href="{{ $asin ? route('admin.asin-selling.details', [$asin->asin1, 'type' => 'sp']) : '#' }}">
                                                    {{ $asin?->asin1 ?? '--' }}
                                                </a>
                                            </td>

                                            {{-- <td class="ellipsis-text"
                                                title="{{ $asin->products->first()?->short_title ?? '' }}">
                                                {{ $asin->products->first()?->short_title ?? '--' }}</td> --}}
                                            <td>{{ $asin->categorisation?->child_short_name ?? '--' }}</td>

                                            <td>
                                                @if ($asin->products->first()?->status === 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-warning">In Active</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown" style="position: relative;">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu">
                                                        <li>
                                                            <a href="{{ route('admin.asin-selling.details', $asin->asin1) }}"
                                                                class="dropdown-item">
                                                                <i class="mdi mdi-eye font-size-16 text-success me-1"></i>
                                                                View
                                                            </a>
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
                            {{ $asins->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>

                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> Click on the <span
                            class="text-primary">ASIN</span> to navigate to the selling
                        dashboard
                        <small>(only if the Product is <strong>completed</strong>).</small>
                    </p>

                </div>
            </div>
        </div>
    </div>
@endsection
