@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">Roles & Permissions</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.user.nav')
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.roles.index') }}" class="row g-2">
                                <x-elements.search-box />
                            </form>
                        </div>
                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2">
                            @can('developer')
                                <div class="flex-fill flex-lg-none" style="min-width: 0;">
                                    <a href="{{ route('admin.roles.permissions.index') }}">
                                        <button
                                            class="btn btn-primary btn-rounded w-100 waves-effect waves-light addCustomers-modal">
                                            <i class="mdi mdi-account-check-outline me-1"></i>Permissions</button>
                                    </a>
                                </div>
                            @endcan
                            @can('user.roles.create')
                                <div class="flex-fill flex-lg-none" style="min-width: 0;">
                                    <a href="{{ route('admin.roles.create') }}">
                                        <button
                                            class="btn btn-success btn-rounded w-100 waves-effect waves-light addCustomers-modal">
                                            <i class="mdi mdi-plus me-1"></i> New Role</button>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr>
                                        @php
                                            // Get all permissions the user can get (direct + via roles)
                                            $allPermissions = $role->getAllPermissions()->pluck('name');

                                            $count = $allPermissions->count();
                                        @endphp
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $role->name ?? 'N/A' }}</td>
                                        <td>
                                            @if ($count > 0)
                                                <span class="badge badge-soft-primary">{{ $count ?? 'N/A' }}</span>
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#" class="dropdown-toggle text-muted"
                                                    data-bs-toggle="dropdown">
                                                    <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    @can('user.roles.update')
                                                        <li>
                                                            <a href="{{ route('admin.roles.edit', $role->id) }}"
                                                                class="dropdown-item">
                                                                <i class="mdi mdi-pencil text-primary me-1"></i> Edit
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('user.roles.delete')
                                                        <li>
                                                            <form method="POST"
                                                                action="{{ route('admin.roles.update', $role->id) }}"
                                                                onsubmit="return confirm('Delete this role?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i
                                                                        class="mdi mdi-trash-can font-size-16 text-danger me-1"></i>
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No roles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $roles->links('pagination::bootstrap-5') }}
                    </div>
                    <p class="text-muted mt-3">
                        <span class="badge badge-soft-info">Note:</span>
                        The Guest role will be automatically assigned if any role is deleted. It acts as a fallback role for
                        affected users.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
