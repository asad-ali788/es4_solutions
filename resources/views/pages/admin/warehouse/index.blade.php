@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Warehouse</h4>
            </div>
        </div>
    </div>
    @php
        $openIds = collect(explode(',', request('open') ?? ''))
            ->map(fn($i) => trim($i))
            ->filter()
            ->map(fn($i) => is_numeric($i) ? (int) $i : $i)
            ->unique()
            ->values()
            ->toArray();
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Warehouse Name</th>
                                    <th>Location</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($warehouses as $warehouse)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td
                                            class="{{ $warehouse->inventories->count() > 0 ? 'text-success fw-bold' : '' }}">
                                            <a data-bs-toggle="collapse" href="#collapse-{{ $warehouse->id }}"
                                                role="button"
                                                aria-expanded="{{ in_array($warehouse->id, $openIds) ? 'true' : 'false' }}"
                                                aria-controls="collapse-{{ $warehouse->id }}">
                                                {{ $warehouse->warehouse_name }}
                                                @if ($warehouse->inventories->count() > 0)
                                                    <i class="mdi mdi-book-open-page-variant-outline text-warning"
                                                        title="Inventory available"></i>
                                                @endif
                                            </a>
                                        </td>
                                        <td>{{ $warehouse->location }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#" class="dropdown-toggle card-drop"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="mdi mdi-dots-horizontal font-size-18 text-success"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('admin.warehouse.edit', $warehouse->uuid) }}"
                                                            class="dropdown-item">
                                                            <i
                                                                class="mdi mdi-pencil font-size-16 text-primary me-1"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('admin.warehouse.quantities', $warehouse->uuid) }}"
                                                            class="dropdown-item">
                                                            <i
                                                                class="mdi mdi-cube-outline font-size-16 text-primary me-1"></i>Inventory
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Collapsible Inventory Table --}}
                                    <tr class="collapse {{ in_array($warehouse->id, $openIds) ? 'show' : '' }}"
                                        id="collapse-{{ $warehouse->id }}">
                                        <td colspan="4">
                                            @if ($warehouse->pagedInventories->count() > 0)
                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Sku</th>
                                                            <th>Available Quantity</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($warehouse->pagedInventories as $inventory)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $inventory->product->sku ?? 'N/A' }}</td>
                                                                <td>{{ $inventory->available_quantity ?? 0 }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                <div class="mt-2">
                                                    {{ $warehouse->pagedInventories->appends(request()->query())->links('pagination::bootstrap-5') }}
                                                </div>
                                            @else
                                                <div class="text-danger text-center fw-bold">No inventory items available.
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-danger text-center fw-bold">No warehouses available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Notes Section -->
                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> Click on the <span class="text-primary">Warehouse name</span> to view
                        inventory details,
                        <em>provided the warehouse has inventory available</em>
                        <small>(indicated by the <strong><i class="mdi mdi-book-open-page-variant-outline text-warning"
                                    title="Inventory available"></i></strong> icon).</small>
                    </p>
                    <p class="text-muted">
                        <span class="badge badge-soft-info">Note :</span> The Warehouse name <span class="text-primary">ShipOut</span> and
                        <span class="text-primary">Tactical</span> are reserved.
                    </p>

                    <div class="mt-2">
                        @if (method_exists($warehouses, 'links'))
                            {{ $warehouses->appends(request()->query())->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const PARAM = 'open';

                function readOpenFromUrl() {
                    const p = new URLSearchParams(window.location.search).get(PARAM);
                    if (!p) return [];
                    return p.split(',').map(s => s.trim()).filter(Boolean);
                }

                function writeOpenToUrl(arr) {
                    const params = new URLSearchParams(window.location.search);
                    if (!arr || arr.length === 0) {
                        params.delete(PARAM);
                    } else {
                        params.set(PARAM, arr.join(','));
                    }
                    const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
                    history.replaceState(null, '', newUrl);
                    appendOpenToPagination(arr);
                }

                function appendOpenToPagination(arr) {
                    document.querySelectorAll('.pagination a').forEach(a => {
                        const url = new URL(a.href, window.location.origin);
                        if (!arr || arr.length === 0) {
                            url.searchParams.delete(PARAM);
                        } else {
                            url.searchParams.set(PARAM, arr.join(','));
                        }
                        a.href = url.toString();
                    });
                }

                function addIfNot(arr, v) {
                    if (!arr.includes(v)) arr.push(v);
                }

                function removeIfExists(arr, v) {
                    const i = arr.indexOf(v);
                    if (i !== -1) arr.splice(i, 1);
                }

                const initial = readOpenFromUrl();
                appendOpenToPagination(initial);

                document.querySelectorAll('tr.collapse[id^="collapse-"]').forEach(el => {
                    const id = el.id.replace('collapse-', '');

                    el.addEventListener('show.bs.collapse', () => {
                        const arr = readOpenFromUrl();
                        addIfNot(arr, id);
                        writeOpenToUrl(arr);
                    });

                    el.addEventListener('hide.bs.collapse', () => {
                        const arr = readOpenFromUrl();
                        removeIfExists(arr, id);
                        writeOpenToUrl(arr);
                    });
                });
            });
        </script>
    @endpush
@endsection
