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
                            <form method="GET" action="{{ route('admin.selling.index') }}" class="row g-2">
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
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover" id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>SKU</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>Item Price</th>
                                    <th>Postage</th>
                                    <th>Disc Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($products) && $products->count() > 0)
                                    @foreach ($products as $product)
                                        @php
                                            $listing = $product->listings->first();
                                        @endphp
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <a
                                                    href="{{ $listing ? route('admin.selling.createSelling', [$listing->uuid, 'from' => 'sellingItems']) : '#' }}">
                                                    {{ $product->sku ?? '--' }}
                                                </a>
                                            </td>
                                            <td>{{ $product?->asins?->asin1 ?? '--' }}</td>
                                            <td>{{ $product->asins?->categorisation?->child_short_name ?? '--' }}</td>
                                            <td>
                                                {{ $listing && $listing->pricing && $listing->pricing->item_price ? $listing->pricing->item_price : '--' }}
                                            </td>
                                            <td>
                                                {{ $listing && $listing->pricing && $listing->pricing->postage ? $listing->pricing->postage : '--' }}
                                            </td>
                                            <td>
                                                @if ($listing && $listing->disc_status === 1)
                                                    <span class="badge bg-danger">Discontinued</span>
                                                @elseif ($listing && $listing->disc_status === 0)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">--</span>
                                                @endif
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
                            {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>

                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> Click on the <span class="text-primary">SKU</span>
                        to navigate to the selling
                        dashboard
                        <small>(only if the Product is <strong>completed</strong>).</small>
                    </p>

                </div>
            </div>
        </div>
    </div>
@endsection
