@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Products</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-center justify-content-between mb-3">

                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.products.index') }}"
                                class="row g-2 align-items-center">
                                {{-- Search --}}
                                <x-elements.search-box />
                                @if (!empty($reportingUsers))
                                    <div class="col-6 col-md-auto">
                                        <div class="form-floating">
                                            <select class="form-select custom-dropdown" name="select"
                                                onchange="this.form.submit()">
                                                <option value="all"
                                                    {{ $targetUserId == 'all' || !$targetUserId ? 'selected' : '' }}>
                                                    All
                                                </option>
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

                                {{-- Status --}}
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select class="form-select custom-dropdown" name="status"
                                            onchange="this.form.submit()">
                                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All
                                            </option>
                                            <option value="active"
                                                {{ request('status') == 'active' || !request('status') ? 'selected' : '' }}>
                                                Active
                                            </option>
                                            <option value="inactive"
                                                {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                                Inactive
                                            </option>
                                        </select>
                                        <label for="floatingSelectStatus">Status</label>
                                    </div>
                                </div>

                            </form>
                        </div>

                        @can('product.create')
                            <div class="col-12 col-lg-auto ms-lg-auto">
                                <div class="row g-2 justify-content-lg-end">
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#newProductModal"
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 text-nowrap addCustomers-modal">
                                            <i class="mdi mdi-plus me-1"></i> New Product
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endcan

                    </div>


                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover" id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>SKU</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    {{-- <th>Amazon Title</th> --}}
                                    <th>Item Price</th>
                                    <th>Translator</th>
                                    <th>Postage</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($products) && count($products) > 0)
                                    @foreach ($products as $product)
                                        @php
                                            $listing = $product->listings[0] ?? null; // Must be inside the loop
                                            //    $listing = $product->listings->firstWhere('progress_status', 3);
                                        @endphp
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                @if (!empty($listing) && $listing->progress_status == 3 && !empty($product) && !empty($product->sku))
                                                    <a
                                                        href="{{ route('admin.selling.createSelling', [$listing->uuid, 'from' => 'products']) }}">
                                                        {{ $product->sku }}
                                                    </a>
                                                @else
                                                    {{ !empty($product) && !empty($product->sku) ? $product->sku : '--' }}
                                                @endif

                                            </td>
                                            <td>{{ $product?->asins?->asin1 ?? '--' }}</td>
                                            <td>{{ $product?->asins?->categorisation?->child_short_name ?? '--' }}</td>
                                            {{-- <td class="ellipsis-text" title="{{ $listing->title_amazon ?? '' }}">
                                                {{ $listing->title_amazon ?? '--' }}</td> --}}
                                            <td>{{ $listing?->pricing?->item_price ?? '--' }}</td>
                                            <td>{{ $listing?->translator ?? '--' }}</td>
                                            {{-- <td>{{ $listing?->country ?? '--' }}</td> --}}
                                            <td>{{ $listing?->pricing?->postage ?? '--' }}</td>
                                            <td>
                                                @if ($product->status == 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-warning">In Active</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @can('product.update')
                                                            @if (!empty($listing) && !empty($listing->uuid))
                                                                <li>
                                                                    <a href="{{ route('admin.products.edit', $listing->uuid) }}"
                                                                        class="dropdown-item d-flex align-items-center edit-list"
                                                                        data-edit-id="{{ $listing->uuid }}">
                                                                        <i
                                                                            class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                        Edit
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        @endcan
                                                        @can('product.inactive')
                                                            <li>
                                                                <a href="{{ route('admin.products.inActive', $product->uuid) }}"
                                                                    class="dropdown-item d-flex align-items-center edit-list"
                                                                    onclick="return confirm('Are you sure you want to make {{ $product->status ? 'In-Active' : 'Active' }} this Product?');">
                                                                    <i
                                                                        class="bx {{ $product->status ? 'bx-block text-danger' : 'bx-check-circle text-success' }} font-size-16 me-1"></i>
                                                                    {{ $product->status ? 'In Active' : 'Active' }}
                                                                </a>
                                                            </li>
                                                        @endcan
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
                            {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                        <!-- end table -->
                    </div>
                    <!-- end table responsive -->
                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> Click on the <span class="text-primary">SKU</span>
                        to navigate to the selling
                        dashboard
                        <small>(only if the Product is <strong>completed</strong>).</small>
                    </p>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->

    <div class="modal fade" id="newProductModal" tabindex="-1" aria-labelledby="newProductModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newProductModalLabel">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form autocomplete="off" class="addProductForm" id="addProductForm" novalidate method="POST"
                        action="{{ route('admin.products.createSku') }}">
                        @csrf

                        <div class="row">
                            <div class="col-lg-12">
                                <!-- SKU Field -->
                                <div class="mb-3">
                                    <label for="sku-input" class="form-label">SKU<span
                                            class="text-danger fw-bold">*</span></label>
                                    <input type="text" id="sku-input" name="sku" class="form-control"
                                        placeholder="Enter SKU" required />
                                </div>

                                <!-- Short Title Field -->
                                <div class="mb-3">
                                    <label for="short-title-input" class="form-label">Short Title<span
                                            class="text-danger fw-bold">*</span> </label>
                                    <input type="text" id="short-title-input" name="short_title" class="form-control"
                                        placeholder="Enter short title" required />
                                </div>

                                <!-- Translator Field -->
                                <div class="mb-3">
                                    <label for="translator-input" class="form-label">Listing to copy</label>
                                    <input type="text" id="translator-input" name="listing_to_copy"
                                        class="form-control" placeholder="Enter the name of the listing to copy" />
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="text-end">
                                    <button type="submit" id="addProduct-btn" class="btn btn-success" data-loading-false>Save</button>
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
                <!-- end modal body -->
            </div>
            <!-- end modal-content -->
        </div>
        <!-- end modal-dialog -->
    </div>
@endsection
