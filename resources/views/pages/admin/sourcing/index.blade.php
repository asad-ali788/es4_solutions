@extends('layouts.app')
@section('content')
    @php
        $showAction = $sourcing->contains(fn($item) => $item->add_to_pl != 1);
    @endphp
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Sourcing</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        {{-- LEFT: Dropdowns --}}
                        <div class="col-12 col-lg-6">
                            <div class="row g-2">
                                {{-- Container dropdown --}}
                                <div class="col-12 col-sm-6 col-md-auto">
                                    <form method="GET" id="containerSelectForm" class="w-100">
                                        @if ($containers && $containers->count())
                                            <select class="form-select" name="uuid"
                                                onchange="document.getElementById('containerSelectForm').submit()"
                                                style="min-width: 210px;">
                                                @foreach ($containers as $container)
                                                    <option value="{{ $container->uuid }}"
                                                        {{ $activeContainerUuid == $container->uuid ? 'selected' : '' }}>
                                                        {{ $container->container_id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select class="form-select" disabled style="min-width: 210px;">
                                                <option disabled selected>No Containers Found</option>
                                            </select>
                                        @endif
                                    </form>
                                </div>

                                {{-- Completed/Pending dropdown --}}
                                <div class="col-12 col-sm-6 col-md-auto">
                                    <form method="GET" id="completedPending" class="w-100">
                                        <select class="form-select" name="completed"
                                            onchange="document.getElementById('completedPending').submit()"
                                            style="min-width: 230px;">
                                            <option value="1" {{ request('completed') == '1' ? 'selected' : '' }}>
                                                Completed Products
                                            </option>
                                            <option value="0" {{ request('completed') !== '1' ? 'selected' : '' }}>
                                                Pending Products
                                            </option>
                                        </select>
                                        <input type="hidden" name="uuid" value="{{ $activeContainerUuid }}">
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT: Buttons --}}
                        <div class="col-12 col-lg-auto ms-lg-auto">
                            <div class="row g-2 justify-content-lg-end">
                                @can('sourcing.export')
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <a href="{{ route('admin.sourcing.exportExcel') }}" onclick="return confirmExport()"
                                            class="w-100">
                                            <button type="button"
                                                class="btn btn-success btn-rounded waves-effect waves-light w-100 text-nowrap">
                                                <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                                            </button>
                                        </a>
                                    </div>
                                @endcan

                                @can('sourcing.create')
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#newSourcing"
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 text-nowrap">
                                            <i class="mdi mdi-plus me-1"></i> Add Sourcing
                                        </button>
                                    </div>
                                @endcan

                                @can('sourcing.add-items')
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#newContainerItem"
                                            class="btn btn-primary btn-rounded waves-effect waves-light w-100 text-nowrap
                                            {{ isset($containers) && $containers->count() > 0 ? '' : 'disabled' }}"
                                            {{ isset($containers) && $containers->count() > 0 ? '' : 'disabled' }}>
                                            <i class="mdi mdi-plus me-1"></i> Add Items
                                        </button>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover" id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    @if (request()->has('completed') && request('completed') == 1)
                                        <th>Container</th>
                                    @endif
                                    <th>SKU</th>
                                    <th>EAN</th>
                                    <th>Amazon URL</th>
                                    <th>Image</th>
                                    <th>Asin No</th>
                                    <th>Description</th>
                                    <th>Short Title</th>
                                    <th>Sourcing Queries</th>
                                    <th>Product Variations</th>
                                    <th>Amazon Pricing</th>
                                    <th>Price</th>
                                    <th>Qty to Order</th>
                                    <th>Listing Notes</th>
                                    @if ($showAction)
                                        <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($sourcing) && count($sourcing) > 0)
                                    @foreach ($sourcing as $items)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            @if (request()->has('completed') && request('completed') == 1)
                                                <td>{{ $items->sourcingContainer->container_id ?? '--' }}</td>
                                            @endif
                                            <td>{{ $items->sku ?? '--' }}</td>
                                            <td>{{ $items->ean ?? '--' }}</td>
                                            <td><a href="{{ $items->amazon_url ?? '#' }}" target="_blank">Amazon Link</a>
                                            </td>
                                            <td><img src="{{ $items->image ? asset('storage/' . $items->image) : asset('assets/images/broken_image.png') }}"
                                                    alt="items Image" width="40"
                                                    onerror="this.onerror=null; this.src='{{ asset('assets/images/broken_image.png') }}';"
                                                    data-holder-rendered="true">
                                            </td>
                                            <td>{{ $items->asin_no ?? '--' }}</td>

                                            <td class="ellipsis-text" title="{{ $items->description ?? '' }}">
                                                {{ $items->description ?? '--' }}</td>

                                            <td class="ellipsis-text" title="{{ $items->short_title ?? '' }}">
                                                {{ $items->short_title ?? '--' }}
                                            </td>

                                            <td class="ellipsis-text">
                                                @if ($items->latestMessage)
                                                    {{ $items->latestMessage->sender->name ?? 'Unknown' }} :
                                                    {{ ucfirst($items->latestMessage->q_a) }}
                                                @else
                                                    --
                                                @endif
                                            </td>

                                            <td class="ellipsis-text" title="{{ $items->pro_variations ?? '' }}">
                                                {{ $items->pro_variations ?? '--' }}
                                            </td>
                                            <td>${{ $items->amz_price ?? '00' }}</td>
                                            <td>${{ $items->price ?? '00' }}</td>
                                            <td>{{ $items->qty_to_order ?? '--' }}</td>
                                            <td class="ellipsis-text" title="{{ $items->notes ?? '' }}">
                                                {{ $items->notes ?? '--' }}</td>
                                            @if ($items->add_to_pl != 1)
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="dropdown-toggle card-drop"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i
                                                                class="mdi mdi-dots-horizontal font-size-18 text-success"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a href="{{ route('admin.sourcing.edit', $items->uuid) }}"
                                                                    class="dropdown-item edit-list"
                                                                    data-edit-id="{{ $items->uuid }}">
                                                                    <i
                                                                        class="mdi mdi-pencil font-size-16 text-primary me-1"></i>Edit</a>
                                                            </li>
                                                            <li>
                                                                <form
                                                                    action="{{ route('admin.sourcing.archive', $items->uuid) }}"
                                                                    method="POST" style="display:inline;"
                                                                    onsubmit="return confirm('Are you sure you want to move this item to Product?');">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item edit-list">
                                                                        <i class="bx bx-save font-size-16 text-success"></i>
                                                                        Move to Product
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            @if ($containers && $containers->count() > 1)
                                                                <li>
                                                                    <a href="#" class="dropdown-item"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#moveToContainerModal_moveContainer"
                                                                        data-item-uuid="{{ $items->uuid }}">
                                                                        <i
                                                                            class="bx bx-package font-size-16 text-success"></i>
                                                                        Move to Container
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        @if (method_exists($sourcing, 'links'))
                            {{ $sourcing->appends(request()->query())->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Add Sourcing Modal -->
    <div class="modal fade" id="newSourcing" tabindex="-1" aria-labelledby="newSourcingLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newSourcingLabel">Add Sourcing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form autocomplete="off" id="addSourcingForm" novalidate method="POST"
                        action="{{ route('admin.sourcing.createContainer') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="container_id" class="form-label">List Name<span
                                    class="text-danger fw-bold">*</span></label>
                            <input type="text" id="container_id" name="container_id" class="form-control"
                                placeholder="Enter List Name" required />
                        </div>
                        <div class="mb-3">
                            <label for="desciptions" class="form-label">Notes</label>
                            <textarea id="desciptions" name="desciptions" class="form-control" rows="3">{{ old('desciptions', $product->desciptions ?? '') }}</textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Save</button>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Container Item Modal -->
    <div class="modal fade" id="newContainerItem" tabindex="-1" aria-labelledby="newContainerItemLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newContainerItemLabel">Add New Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form autocomplete="off" id="addContainerItemForm" novalidate method="POST"
                        action="{{ route('admin.sourcing.createItemList') }}">
                        @csrf

                        <input type="text" name="uuid" value="{{ $activeContainerUuid ?? null }}" hidden />

                        <div class="mb-3">
                            <label for="amazon_url" class="form-label">Amazon URL<span
                                    class="text-danger fw-bold">*</span></label>
                            <input type="text" id="amazon_url" name="amazon_url" class="form-control"
                                placeholder="Enter Amazon Url" required />
                        </div>
                        <div class="mb-3">
                            <label for="amz_price" class="form-label">Amazon Price</label>
                            <input type="text" class="form-control" id="amz_price" name="amz_price"
                                placeholder="Enter Amazon Price">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Save</button>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moveToContainerModal_moveContainer" tabindex="-1"
        aria-labelledby="moveToContainerModalLabel_moveContainer" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveToContainerModalLabel_moveContainer">Move to Container</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form autocomplete="off" id="moveContainerItemForm_moveContainer" novalidate method="POST"
                        action="{{ route('admin.sourcing.moveContainer') }}">

                        @csrf
                        <input type="hidden" name="item_uuid" id="item_uuid_field_moveContainer" value="">

                        <div class="mb-3">
                            <label for="container_uuid_moveContainer" class="form-label">Select Container</label>
                            <select class="form-select w-100" name="container_uuid" id="container_uuid_moveContainer">

                                @foreach ($containers as $container)
                                    <option value="{{ $container->uuid }}">
                                        {{ $container->container_id }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Move</button>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Sync container change and preserve 'completed' value
            $('#containerSelectForm').on('submit', function() {
                const completed = $('#completedPending select').val();
                $(this).append(`<input type="hidden" name="completed" value="${completed}">`);
            });

            // Sync completed change and preserve 'uuid' value
            $('#completedPending').on('submit', function() {
                const uuid = $('#containerSelectForm select').val();
                $(this).append(`<input type="hidden" name="uuid" value="${uuid}">`);
            });
        });

        function confirmExport() {
            return confirm('Are you sure you want to export the data to Excel?');
        }

        $(document).ready(function() {
            $('[data-item-uuid]').click(function() {
                let itemUuid = $(this).data('item-uuid');
                $('#item_uuid_field_moveContainer').val(itemUuid);
            });

            $('#moveContainerItemForm_moveContainer').submit(function(e) {
                e.preventDefault();

                let form = $(this);
                let url = form.attr('action');
                let formData = form.serialize();

                $.ajax({
                    url: url,
                    method: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.status === 'success') {

                            alert(response.message);
                            $('#moveToContainerModal_moveContainer').modal('hide');
                            location.reload();
                        } else if (response.status === 'info') {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>

@endsection
