@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">Update Product</h4>
                    @if (isset($otherCountrys) && $otherCountrys->isNotEmpty())
                        @php
                            // Mapping country code to flag image and full name
                            $flagMap = config('flagmap');

                            $currentCountryCode = strtoupper($product->country ?? 'US');
                            $currentFlag = $flagMap[$currentCountryCode]['file'] ?? 'us.jpg';
                            $currentCountryName = $flagMap[$currentCountryCode]['name'] ?? 'United States';

                            $statusClasses = [
                                'title' => $product->title_change_status ? 'border-info' : 'border-success',
                                'bullets' => $product->bullets_change_status ? 'border-info' : 'border-success',
                                'description' => $product->description_change_status ? 'border-info' : 'border-success',
                            ];

                        @endphp

                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn header-item waves-effect d-flex align-items-center"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img id="header-lang-img" src="{{ asset('assets/images/flags/' . $currentFlag) }}"
                                    alt="Selected Country" height="16" class="me-1">
                                <span class="d-none d-sm-inline">{{ $currentCountryName }}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($otherCountrys as $country)
                                    @php
                                        $code = strtoupper($country->country ?? 'US');
                                        $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                        $name = $flagMap[$code]['name'] ?? $code;
                                        $route = route('admin.products.edit', $country->uuid);
                                    @endphp
                                    <a href="{{ $route }}" class="dropdown-item notify-item language">
                                        <img src="{{ asset('assets/images/flags/' . $flag) }}" alt="{{ $name }}"
                                            class="me-1" height="12">
                                        <span class="align-middle">{{ $name }}</span>
                                        @if ($product->uuid === $country->uuid)
                                            <i class="mdi mdi-check text-success ms-2"></i>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @can('product.sync')
                        <a href="{{ route('admin.products.syncProductDetails', $product->id) }}"
                            onclick="return confirm('Are you sure you want to Update the Title Bullet points and description to live?');">
                            <button type="button" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light"><i
                                    class="mdi mdi-cloud-sync label-icon font-size-14 align-middle me-2"></i>Sync</button>
                        </a>
                    @endcan
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.products.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Products
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form id="updateProductForm" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ old('id', $product->id ?? '') }}">
        <div class="row">
            <div class="col-lg-6">
                <div class="left-panel">
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4 d-flex justify-content-between align-items-center">
                                <span>Listing Detail UK</span>
                                @php
                                    $titleFilled = !empty($product->title_amazon);
                                    $bulletPoints = [
                                        $product->bullet_point_1 ?? null,
                                        $product->bullet_point_2 ?? null,
                                        $product->bullet_point_3 ?? null,
                                        $product->bullet_point_4 ?? null,
                                        $product->bullet_point_5 ?? null,
                                    ];
                                    $bulletFilledCount = collect($bulletPoints)->filter()->count();

                                    $images = [
                                        $product->additionalDetail->image1 ?? null,
                                        $product->additionalDetail->image2 ?? null,
                                        $product->additionalDetail->image3 ?? null,
                                        $product->additionalDetail->image4 ?? null,
                                        $product->additionalDetail->image5 ?? null,
                                        $product->additionalDetail->image6 ?? null,
                                    ];
                                    $imageFilledCount = collect($images)->filter()->count();

                                    if ($titleFilled && $bulletFilledCount >= 3 && $imageFilledCount >= 3) {
                                        $badgeText = 'Completed';
                                        $badgeClass = 'badge-soft-success';
                                    } elseif ($titleFilled || $bulletFilledCount > 0 || $imageFilledCount > 0) {
                                        $totalFilled =
                                            $titleFilled + min($bulletFilledCount, 3) + min($imageFilledCount, 3);
                                        $totalRequired = 1 + 3 + 3;
                                        $badgeText = "In Progress ({$totalFilled}/{$totalRequired})";
                                        $badgeClass = 'badge-soft-warning';
                                    } else {
                                        $badgeText = 'Not Started';
                                        $badgeClass = 'badge-soft-danger';
                                    }
                                @endphp

                                <span class="badge rounded-pill font-size-12 {{ $badgeClass }}" id="progressBadge">
                                    {{ $badgeText }}
                                </span>
                            </h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sku">SKU</label>
                                        <input type="text" class="form-control" id="sku"
                                            value="{{ old('sku', $product->product->sku ?? '') }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="shortTitle">Short Title</label>
                                        <input type="text" class="form-control" id="short_title" name="short_title"
                                            value="{{ old('short_title', $product->product->short_title ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="translator">Translator</label>
                                        <input type="text" class="form-control" id="translator" name="translator"
                                            value="{{ old('translator', $product->translator ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-2 {{ $statusClasses['title'] }}">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Title Amazon</h4>
                            <div class="form-group">
                                <textarea class="form-control" id="title_amazon" name="title_amazon" rows="4">{{ old('title_amazon', $product->title_amazon ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card border-2 {{ $statusClasses['bullets'] }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h4 class="card-title mb-4">Bullet Points</h4>
                            </div>

                            <div class="form-group mb-3">
                                <label for="bulletPoint1">Bullet Point 1</label>
                                <textarea class="form-control" rows="3" id="bullet_point_1" name="bullet_point_1">{{ old('bullet_point_1', $product->bullet_point_1 ?? '') }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="bulletPoint2">Bullet Point 2</label>
                                <textarea class="form-control" rows="3" id="bullet_point_2" name="bullet_point_2">{{ old('bullet_point_2', $product->bullet_point_2 ?? '') }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="bulletPoint3">Bullet Point 3</label>
                                <textarea class="form-control" rows="3" id="bullet_point_3" name="bullet_point_3">{{ old('bullet_point_3', $product->bullet_point_3 ?? '') }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="bulletPoint4">Bullet Point 4</label>
                                <textarea class="form-control" rows="3" id="bullet_point_4" name="bullet_point_4">{{ old('bullet_point_4', $product->bullet_point_4 ?? '') }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="bulletPoint5">Bullet Point 5</label>
                                <textarea class="form-control" rows="3" id="bullet_point_5" name="bullet_point_5">{{ old('bullet_point_5', $product->bullet_point_5 ?? '') }}</textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card border-2 {{ $statusClasses['description'] }}">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Description</h4>
                            <textarea class="form-control" rows="3" id="description" name="description">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Search Terms</h4>
                            <textarea class="form-control" rows="3" id="search_terms" name="search_terms">{{ old('search_terms', $product->search_terms ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Advertising Keywords</h4>
                            <textarea class="form-control" rows="3" id="advertising_keywords" name="advertising_keywords">{{ old('advertising_keywords', $product->advertising_keywords ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Packaging and Certificates</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="instructionsFile">Instructions</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="instructions_file"
                                                name="instructions_file">
                                            @if (!empty($product->instructions_file))
                                                <a href="{{ asset('storage/' . $product->instructions_file) }}"
                                                    target="_blank" class="ms-2 mt-2" title="View File">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            @endif
                                        </div>

                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="productCategory">Product Category</label>
                                        <input type="text" class="form-control" id="product_category"
                                            name="product_category"
                                            value="{{ old('product_category', $product->product_category ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="right-panel">
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Pricing Information UK</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="item_price">Item Price</label>
                                        <input type="text" class="form-control" id="item_price" name="item_price"
                                            value="{{ old('item_price', $product->pricing->item_price ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="postage">Postage</label>
                                        <input type="text" class="form-control" id="postage" name="postage"
                                            value="{{ old('postage', $product->pricing->postage ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="base_price">Base Price</label>
                                        <input type="text" class="form-control" id="base_price" name="base_price"
                                            value="{{ old('base_price', $product->pricing->base_price ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fba_fee">FBA Fee</label>
                                        <input type="text" class="form-control" id="fba_fee" name="fba_fee"
                                            value="{{ old('fba_fee', $product->pricing->fba_fee ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="duty">Duty</label>
                                        <input type="text" class="form-control" id="duty" name="duty"
                                            value="{{ old('duty', $product->pricing->duty ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="air_ship">Air Ship</label>
                                        <input type="text" class="form-control" id="air_ship" name="air_ship"
                                            value="{{ old('air_ship', $product->pricing->air_ship ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Container Information UK</h4>
                            <div class="row">
                                <!-- Commercial Invoice & HS Code -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="commercial_invoice_title">Commercial Invoice Title</label>
                                        <input type="text" class="form-control" id="commercial_invoice_title"
                                            name="commercial_invoice_title"
                                            value="{{ old('commercial_invoice_title', $product->containerInfo->commercial_invoice_title ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hs_code">HS Code</label>
                                        <input type="text" class="form-control" id="hs_code" name="hs_code"
                                            value="{{ old('hs_code', $product->containerInfo->hs_code ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="hs_code_percentage">HS Code Percentage</label>
                                        <input type="text" class="form-control" id="hs_code_percentage"
                                            name="hs_code_percentage"
                                            value="{{ old('hs_code_percentage', $product->containerInfo->hs_code_percentage ?? '') }}">
                                    </div>
                                </div>

                                <!-- Item Size -->
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label for="item_size_length_cm">Item Size CM (L)</label>
                                        <input type="text" class="form-control" id="item_size_length_cm"
                                            name="item_size_length_cm"
                                            value="{{ old('item_size_length_cm', $product->containerInfo->item_size_length_cm ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label for="item_size_width_cm">Item Size CM (W)</label>
                                        <input type="text" class="form-control" id="item_size_width_cm"
                                            name="item_size_width_cm"
                                            value="{{ old('item_size_width_cm', $product->containerInfo->item_size_width_cm ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label for="item_size_height_cm">Item Size CM (H)</label>
                                        <input type="text" class="form-control" id="item_size_height_cm"
                                            name="item_size_height_cm"
                                            value="{{ old('item_size_height_cm', $product->containerInfo->item_size_height_cm ?? '') }}">
                                    </div>
                                </div>

                                <!-- Carton Size -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ctn_size_length_cm">CTN Size CM (L)</label>
                                        <input type="text" class="form-control" id="ctn_size_length_cm"
                                            name="ctn_size_length_cm"
                                            value="{{ old('ctn_size_length_cm', $product->containerInfo->ctn_size_length_cm ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ctn_size_width_cm">CTN Size CM (W)</label>
                                        <input type="text" class="form-control" id="ctn_size_width_cm"
                                            name="ctn_size_width_cm"
                                            value="{{ old('ctn_size_width_cm', $product->containerInfo->ctn_size_width_cm ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ctn_size_height_cm">CTN Size CM (H)</label>
                                        <input type="text" class="form-control" id="ctn_size_height_cm"
                                            name="ctn_size_height_cm"
                                            value="{{ old('ctn_size_height_cm', $product->containerInfo->ctn_size_height_cm ?? '') }}">
                                    </div>
                                </div>

                                <!-- Weights -->
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label for="item_weight_kg">Item Weight (KG)</label>
                                        <input type="text" class="form-control" id="item_weight_kg"
                                            name="item_weight_kg"
                                            value="{{ old('item_weight_kg', $product->containerInfo->item_weight_kg ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label for="carton_weight_kg">Carton Weight (KG)</label>
                                        <input type="text" class="form-control" id="carton_weight_kg"
                                            name="carton_weight_kg"
                                            value="{{ old('carton_weight_kg', $product->containerInfo->carton_weight_kg ?? '') }}">
                                    </div>
                                </div>

                                <!-- Quantity, Volume, MOQ -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quantity_per_carton">Quantity Per Carton</label>
                                        <input type="text" class="form-control" id="quantity_per_carton"
                                            name="quantity_per_carton"
                                            value="{{ old('quantity_per_carton', $product->containerInfo->quantity_per_carton ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="carton_cbm">Carton CBM</label>
                                        <input type="text" class="form-control" id="carton_cbm" name="carton_cbm"
                                            value="{{ old('carton_cbm', $product->containerInfo->carton_cbm ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="moq">MOQ</label>
                                        <input type="text" class="form-control" id="moq" name="moq"
                                            value="{{ old('moq', $product->containerInfo->moq ?? '') }}">
                                    </div>
                                </div>

                                <!-- Product Material & Lead Time -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_material">Product Material</label>
                                        <input type="text" class="form-control" id="product_material"
                                            name="product_material"
                                            value="{{ old('product_material', $product->containerInfo->product_material ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="order_lead_time_weeks">Order Lead Time (Weeks)</label>
                                        <select class="form-control" id="order_lead_time_weeks"
                                            name="order_lead_time_weeks">
                                            <option value="">Select weeks</option>
                                            @for ($i = 1; $i <= 8; $i++)
                                                <option value="{{ $i }}"
                                                    {{ old('order_lead_time_weeks', $product->containerInfo->order_lead_time_weeks ?? '') == $i ? 'selected' : '' }}>
                                                    {{ $i }} - weeks
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Barcode Information UK</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fbaBarcode">FBA Barcode</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="fba_barcode_file"
                                                name="fba_barcode_file">
                                            @if (!empty($product->additionalDetail?->fba_barcode_file))
                                                <div class="">
                                                    <a href="{{ asset('storage/' . $product->additionalDetail->fba_barcode_file) }}"
                                                        target="_blank" class="btn btn-outline-primary">
                                                        View File
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="productLabel">Product Label</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="product_label_file"
                                                name="product_label_file">
                                            @if (!empty($product->additionalDetail?->product_label_file))
                                                <div class="">
                                                    <a href="{{ asset('storage/' . $product->additionalDetail->product_label_file) }}"
                                                        target="_blank" class="btn btn-outline-primary">
                                                        View File
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mt-2">
                                        <label for="instructions">Instructions</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="instructions_file_2"
                                                name="instructions_file_2">
                                            @if (!empty($product->additionalDetail?->instructions_file_2))
                                                <div class="">
                                                    <a href="{{ asset('storage/' . $product->additionalDetail->instructions_file_2) }}"
                                                        target="_blank" class="btn btn-outline-primary">
                                                        View File
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Warnings</h4>
                            <textarea class="form-control" rows="5" id="warnings" name="warnings">{{ old('warnings', $product->additionalDetail->warnings ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Listing to Copy</h4>
                            <input type="text" class="form-control" id="listing_to_copy" name="listing_to_copy"
                                value="{{ old('listing_to_copy', $product->additionalDetail->listing_to_copy ?? '') }}">
                        </div>
                    </div>
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-2">Listing Research</h4>
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="listing_research_file"
                                            name="listing_research_file">
                                        @if (!empty($product->additionalDetail?->listing_research_file))
                                            <div class="">
                                                <a href="{{ asset('storage/' . $product->additionalDetail->listing_research_file) }}"
                                                    target="_blank" class="btn btn-outline-primary">
                                                    View File
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-2">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4 class="card-title mb-0">
                                    Product Season
                                </h4>
                                {{-- Season badges (visual only) --}}
                                <div class="d-flex gap-1">
                                    <span class="badge bg-secondary cursor-pointer">All Season</span>
                                    <span class="badge bg-info cursor-pointer">Winter</span>
                                    <span class="badge bg-warning text-dark cursor-pointer">Summer</span>
                                </div>
                            </div>
                            <select class="form-select" id="seasonal_type" name="seasonal_type">
                                <option value="">Select season</option>
                                <option value="All Season" @selected(old('seasonal_type', $product->product?->asins?->categorisation?->seasonal_type ?? '') == 'All Season')>
                                    All Season
                                </option>
                                <option value="Winter" @selected(old('seasonal_type', $product->product?->asins?->categorisation?->seasonal_type ?? '') == 'Winter')>
                                    Winter
                                </option>
                                <option value="Summer" @selected(old('seasonal_type', $product->product?->asins?->categorisation?->seasonal_type ?? '') == 'Summer')>
                                    Summer
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card border-2">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Images</h4>
                        <div class="form-group row">
                            <div class="row">
                                @for ($i = 1; $i <= 6; $i++)
                                    @php
                                        $field = 'image' . $i;
                                        $image = $product->additionalDetail?->$field;
                                        $isAmazonImage = Str::startsWith($image, 'https://m.media-amazon.com');
                                        $imageUrl = $isAmazonImage ? $image : asset('storage/' . $image);
                                    @endphp

                                    <div class="col-sm-4 mb-3">
                                        <label for="{{ $field }}" class="form-label">Image
                                            {{ $i }}</label>
                                        <input type="file" class="form-control" id="{{ $field }}"
                                            name="{{ $field }}">

                                        @if (!empty($image))
                                            <a href="{{ $imageUrl }}" target="_blank">
                                                <img class="rounded me-2 m-2" width="100" src="{{ $imageUrl }}"
                                                    onerror="this.onerror=null; this.src='{{ asset('assets/images/broken_image.png') }}';"
                                                    alt="Image Preview">
                                            </a>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script>
        $(document).ready(function() {
            const productId = {{ old('id', $product->id ?? '') }};
            const updateUrl =
                "{{ route('admin.products.updateProducts', ['id' => old('product', $product->id ?? '')]) }}";
            const csrfToken = '{{ csrf_token() }}';

            const validator = $("#updateProductForm").validate();

            function debounce(func, wait = 1000) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            const sendAjax = (data, isFile = false) => {
                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: data,
                    processData: !isFile,
                    contentType: isFile ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                    // success: (response) => console.log('Auto-saved:', data),
                    success: (response) => {
                        if (response.success) {
                            if (response.postage !== undefined) {
                                $('#postage').val(response.postage);
                            }
                            if (response.base_price !== undefined) {
                                $('#base_price').val(response.base_price);
                            }
                            if (response.duty !== undefined) {
                                $('#duty').val(response.duty);
                            }
                            //  console.log(response.success);
                            showToast('success', response.message ||
                                'Product updated successfully.');
                        } else {
                            showToast('error', response.message || 'An error occurred.');
                        }
                    },
                    // error: (xhr) => console.error('Failed to save:', data)
                    error: (xhr) => {
                        console.error('Failed to save:', data);
                        let errMsg = 'Failed to save data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        showToast('error', errMsg);
                    }
                });
            };

            $('input, textarea, select').on('input', debounce(function() {
                const $input = $(this);
                const fieldName = $input.attr('name');
                if (!validator.element($input)) {
                    console.warn(`Validation failed for: ${fieldName}`);
                    return;
                }
                if ($input.attr('type') === 'file') {
                    const fileInput = this;
                    if (!fileInput.files.length) return;

                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append(fieldName, fileInput.files[0]);

                    sendAjax(formData, true);
                } else {
                    const fieldValue = $input.val();
                    sendAjax({
                        _token: csrfToken,
                        [fieldName]: fieldValue
                    });
                }
            }, 1000)); // 300ms debounce


        });
    </script>
@endsection
