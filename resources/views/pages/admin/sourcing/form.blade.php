@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">Update Sourcing</h4>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.sourcing.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Sourcing
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
        <input type="hidden" name="id" value="{{ old('id', $sourcing->uuid ?? '') }}">
        <div class="row">
            <div class="col-lg-6">
                <div class="left-panel">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Listing Detail UK</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sku">SKU</label>
                                        <input type="text" class="form-control" id="sku" name="sku"
                                            value="{{ old('sku', $sourcing->sku ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="shortTitle">Short Title</label>
                                        <input type="text" class="form-control" id="short_title" name="short_title"
                                            value="{{ old('short_title', $sourcing->short_title ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ean">EAN</label>
                                        <input type="text" class="form-control" id="ean" name="ean"
                                            value="{{ old('ean', $sourcing->ean ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Amazon URL</h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="fbaBarcode">URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="amazon_url" name="amazon_url"
                                                value="{{ old('amazon_url', $sourcing->amazon_url ?? '') }}" readonly>

                                            @if (!empty($sourcing->amazon_url))
                                                <a href="{{ old('amazon_url', $sourcing->amazon_url) }}" target="_blank"
                                                    class="btn btn-outline-primary ms-2" title="Open in new tab">
                                                    <i class="bx bx-link-external"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fbaBarcode">Amazon Price</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="amz_price" name="amz_price"
                                                value="{{ old('amz_price', $sourcing->amz_price ?? '') }}">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="productLabel">Asin No</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="asin_no" name="asin_no"
                                                value="{{ old('asin_no', $sourcing->asin_no ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Description</h4>
                            <textarea class="form-control" rows="3" id="description" name="description">{{ old('description', $sourcing->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Product Variations</h4>
                            <textarea class="form-control" rows="3" id="pro_variations" name="pro_variations">{{ old('pro_variations', $sourcing->pro_variations ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Image</h4>
                            <div class="row">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="image" name="image">
                                        @if (!empty($sourcing->image))
                                            <div class="">
                                                <a href="{{ asset('storage/' . $sourcing->image) }}" target="_blank"
                                                    class="btn btn-outline-primary">
                                                    View image
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Notes</h4>
                            <textarea class="form-control" rows="5" id="notes" name="notes">{{ old('notes', $sourcing->notes ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Supplier</h4>
                            <select class="form-select" name="supplier_id">
                                <option value="">Select</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id', isset($sourcing) && isset($sourcing->supplier_id) ? $sourcing->supplier_id : '') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                    </div>


                </div>
            </div>
            <div class="col-lg-6">
                <div class="right-panel">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Pricing</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unit_price">Unit Price</label>
                                        <input type="text" class="form-control" id="unit_price" name="unit_price"
                                            value="{{ old('unit_price', $sourcing->unit_price ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_cost">Shipping Cost</label>
                                        <input type="text" class="form-control" id="shipping_cost"
                                            name="shipping_cost"
                                            value="{{ old('shipping_cost', $sourcing->shipping_cost ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="landed_costs_eu">Landed Costs EU</label>
                                        <input type="text" class="form-control" id="landed_costs_eu"
                                            name="landed_costs_eu"
                                            value="{{ old('landed_costs_eu', $sourcing->landed_costs_eu ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="landed_costs_us">Landed Costs US</label>
                                        <input type="text" class="form-control" id="landed_costs_us"
                                            name="landed_costs_us"
                                            value="{{ old('landed_costs_us', $sourcing->landed_costs_us ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="landed_costs_uk">Landed Costs UK</label>
                                        <input type="text" class="form-control" id="landed_costs_uk"
                                            name="landed_costs_uk"
                                            value="{{ old('landed_costs_uk', $sourcing->landed_costs_uk ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="moq">MOQ (Min Order Quantity)</label>
                                        <input type="text" class="form-control" id="moq" name="moq"
                                            value="{{ old('moq', $sourcing->moq ?? '') }}">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Product Information</h4>
                            <div class="row">
                                <!-- Item Size -->
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label for="item_length">Item Size CM (L)</label>
                                        <input type="text" class="form-control" id="item_length" name="item_length"
                                            value="{{ old('item_length', $sourcing->item_length ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label for="item_widht">Item Size CM (W)</label>
                                        <input type="text" class="form-control" id="item_width" name="item_widht"
                                            value="{{ old('item_widht', $sourcing->item_widht ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label for="item_height">Item Size CM (H)</label>
                                        <input type="text" class="form-control" id="item_height" name="item_height"
                                            value="{{ old('item_height', $sourcing->item_height ?? '') }}">
                                    </div>
                                </div>

                                <!-- Carton Size -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="carton_length">CTN Size CM (L)</label>
                                        <input type="text" class="form-control" id="carton_length"
                                            name="carton_length"
                                            value="{{ old('carton_length', $sourcing->carton_length ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="carton_width">CTN Size CM (W)</label>
                                        <input type="text" class="form-control" id="carton_width" name="carton_width"
                                            value="{{ old('carton_width', $sourcing->carton_width ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="carton_height">CTN Size CM (H)</label>
                                        <input type="text" class="form-control" id="carton_height"
                                            name="carton_height"
                                            value="{{ old('carton_height', $sourcing->carton_height ?? '') }}">
                                    </div>
                                </div>

                                <!-- Weights -->
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label for="pro_weight">Weight (KG)</label>
                                        <input type="text" class="form-control" id="pro_weight" name="pro_weight"
                                            value="{{ old('pro_weight', $sourcing->pro_weight ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label for="carton_qty">CTN Quantity</label>
                                        <input type="text" class="form-control" id="carton_qty" name="carton_qty"
                                            value="{{ old('carton_qty', $sourcing->carton_qty ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Price & Quantity</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fbaBarcode">Base Price EU</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="base_price_eu"
                                                name="base_price_eu"
                                                value="{{ old('base_price_eu', $sourcing->base_price_eu ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fbaBarcode">Base Price US</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="base_price_us"
                                                name="base_price_us"
                                                value="{{ old('base_price_us', $sourcing->base_price_us ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fbaBarcode">Base Price UK</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="base_price_uk"
                                                name="base_price_uk"
                                                value="{{ old('base_price_uk', $sourcing->base_price_uk ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="productLabel">Qty to Order</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="qty_to_order"
                                                name="qty_to_order"
                                                value="{{ old('qty_to_order', $sourcing->qty_to_order ?? '') }}">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="total_order_value">Total Order Value</label>
                                        <input type="text" class="form-control" id="total_order_value"
                                            name="total_order_value"
                                            value="{{ old('total_order_value', $sourcing->total_order_value ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Amazon FBA Cost</h4>
                            <div class="row">
                                <!-- US -->
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">US $</span>
                                        <input type="text" class="form-control" id="fba_cost_us" name="fba_cost_us"
                                            value="{{ old('fba_cost_us', $sourcing->fba_cost['US'] ?? '') }}">
                                    </div>
                                </div>
                                <!-- CA -->
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">CA $</span>
                                        <input type="text" class="form-control" id="fba_cost_ca" name="fba_cost_ca"
                                            value="{{ old('fba_cost_ca', $sourcing->fba_cost['CA'] ?? '') }}">
                                    </div>
                                </div>
                                <!-- UK -->
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">UK £</span>
                                        <input type="text" class="form-control" id="fba_cost_uk" name="fba_cost_uk"
                                            value="{{ old('fba_cost_uk', $sourcing->fba_cost['UK'] ?? '') }}">
                                    </div>
                                </div>
                                <!-- DE -->
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">DE €</span>
                                        <input type="text" class="form-control" id="fba_cost_de" name="fba_cost_de"
                                            value="{{ old('fba_cost_de', $sourcing->fba_cost['DE'] ?? '') }}">
                                    </div>
                                </div>
                                <!-- FR -->
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">FR €</span>
                                        <input type="text" class="form-control" id="fba_cost_fr" name="fba_cost_fr"
                                            value="{{ old('fba_cost_fr', $sourcing->fba_cost['FR'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">ES €</span>
                                        <input type="text" class="form-control" id="fba_cost_es" name="fba_cost_es"
                                            value="{{ old('fba_cost_es', $sourcing->fba_cost['ES'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">EU €</span>
                                        <input type="text" class="form-control" id="fba_cost_eu" name="fba_cost_eu"
                                            value="{{ old('fba_cost_eu', $sourcing->fba_cost['EU'] ?? '') }}">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Comments</h4>
                            <!-- Scrollable Comments Container -->
                            <div id="commentsContainer" style="max-height: 180px; overflow-y: auto;">
                                <div id="commentsList">
                                    @foreach ($chats as $chat)
                                        @php
                                            $isSender = isset($chat->sender_id) && $chat->sender_id == auth()->id();
                                            $avatarClass = $isSender
                                                ? 'bg-primary-subtle text-primary'
                                                : 'bg-success-subtle text-success';
                                            $senderName =
                                                isset($chat->sender) && isset($chat->sender->name)
                                                    ? $chat->sender->name
                                                    : 'Unknown Sender';
                                            $senderInitial = strtoupper(substr($senderName, 0, 1));
                                            $diffForHumans = isset($chat->diff_for_humans)
                                                ? $chat->diff_for_humans
                                                : ($chat->created_at
                                                    ? $chat->created_at->diffForHumans()
                                                    : 'some time ago');
                                        @endphp

                                        <div class="d-flex mb-4 align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <span
                                                        class="avatar-title rounded-circle {{ $avatarClass }} font-size-16">
                                                        {{ $senderInitial }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-13 mb-1">{{ $senderName }}</h5>
                                                <p class="text-muted mb-1">{{ $chat->q_a ?? '' }}</p>
                                                @if (isset($chat->attachment))
                                                    <p><a href="{{ asset('storage/' . $chat->attachment) }}"
                                                            target="_blank">View Attachment</a></p>
                                                @endif
                                            </div>
                                            <div class="text-muted ms-3" style="white-space: nowrap; font-size: 12px;">
                                                {{ $diffForHumans }}
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                                <div id="newComments"></div>
                            </div>
                        </div>

                        <!-- Comment Input -->
                        <div class="card-body border-top">
                            <textarea class="form-control mb-2" rows="3" id="q_a" placeholder="Add your comment..." required></textarea>
                            <input type="hidden" id="uuid" value="{{ $sourcing->uuid ?? '' }}">

                            <div class="text-end">
                                <a id="submitCommentBtn" class="btn btn-success w-sm">Submit</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function() {
            const updateUrl = "{{ route('admin.sourcing.updateSourcing', $sourcing->uuid ?? '') }}";
            const csrfToken = '{{ csrf_token() }}';

            function debounce(func, wait = 500) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            function sendAjax(data, isFile = false) {
                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: data,
                    processData: !isFile,
                    contentType: isFile ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                    success: (response) => {
                        console.log('Auto-saved:', data);
                        if (response.success) {
                            if (response.shipping_cost !== undefined) {
                                $('#shipping_cost').val(response.shipping_cost);
                            }
                            if (response.landed_costs_eu !== undefined) {
                                $('#landed_costs_eu').val(response.landed_costs_eu);
                            }
                            if (response.landed_costs_us !== undefined) {
                                $('#landed_costs_us').val(response.landed_costs_us);
                            }
                            if (response.landed_costs_uk !== undefined) {
                                $('#landed_costs_uk').val(response.landed_costs_uk);
                            }
                            if (response.total_order_value !== undefined) {
                                $('#total_order_value').val(response.total_order_value);
                            }
                            if (response.base_price_us !== undefined) {
                                $('#base_price_us').val(response.base_price_us);
                            }
                            if (response.base_price_uk !== undefined) {
                                $('#base_price_uk').val(response.base_price_uk);
                            }
                            if (response.base_price_eu !== undefined) {
                                $('#base_price_eu').val(response.base_price_eu);
                            }
                            showToast('success', response.message || 'Product updated successfully.');
                        } else {
                            showToast('error', response.message || 'An error occurred.');
                        }
                    },
                    error: (xhr) => {
                        console.error('Failed to save:', data);
                        const errMsg = xhr.responseJSON?.message || 'Failed to save data.';
                        showToast('error', errMsg);
                    }
                });
            }

            $('input, textarea, select').on('input', debounce(function() {
                const $input = $(this);
                const fieldName = $input.attr('name');

                if (!fieldName) return;

                if ($input.is('[type="file"]')) {
                    const fileInput = this;
                    if (!fileInput.files.length) return;

                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('_method', 'PUT');
                    formData.append(fieldName, fileInput.files[0]);

                    sendAjax(formData, true);
                } else {
                    const fieldValue = $input.val();
                    sendAjax({
                        _token: csrfToken,
                        _method: 'PUT',
                        [fieldName]: fieldValue
                    });
                }
            }, 500));
            
            let container = $('#commentsContainer');
            container.scrollTop(container[0].scrollHeight);

            $('#submitCommentBtn').on('click', function(e) {
                e.preventDefault();

                let q_a = $('#q_a').val().trim();
                let uuid = $('#uuid').val();

                if (!q_a) {
                    alert('Please enter a comment');
                    return;
                }

                const url = "{{ route('admin.sourcing.chat.save') }}";

                $.ajax({
                    url: url,
                    method: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        q_a: q_a,
                        uuid: uuid,
                        receiver: 'supplier'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#q_a').val('');
                            $('#newComments').append(`
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar-xs">
                                    <span class="avatar-title rounded-circle bg-success-subtle text-success font-size-16">
                                        ${response.chat.sender_name ? response.chat.sender_name.charAt(0).toUpperCase() : 'U'}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="font-size-13 mb-1">${response.chat.sender_name || 'You'}</h5>
                                <p class="text-muted mb-1">${response.chat.q_a}</p>
                            </div>
                        </div>
                    `);
                            // Scroll to bottom
                            let container = $('#commentsContainer');
                            container.scrollTop(container[0].scrollHeight);

                            showToast('success', response.message ||
                                'Comment updated successfully.');
                        } else {
                            alert(response.error || 'Failed to submit comment.');
                            console.log(url);
                        }
                    },
                    error: function(xhr) {
                        let err = xhr.responseJSON?.message;
                        // alert(err);
                        showToast('error', err);
                    }
                });
            });
        });
    </script>
@endsection
