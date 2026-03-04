@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">User Permissions</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.user.nav')

                <div class="card-body pt-2">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <form method="GET" action="{{ route('admin.user.permissions.index') }}"
                                class="d-inline-block">
                                <x-elements.search-box />
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Permissions</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    @php
                                        // Get all permissions the user can get (direct + via roles)
                                        $allPermissions = $user->getAllPermissions()->pluck('name');
                                        // Get all denied permission names
                                        $denied = $user->revokedPermissions()->pluck('name');
                                        // Filter out denied ones
                                        $allowedPermissions = $allPermissions->diff($denied);
                                        $count = $allowedPermissions->count();
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td style="padding: 0">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $user->profile ? asset('storage/' . $user->profile) : $user->profile_photo_url }}"
                                                    alt="{{ $user->name }}" class="rounded-circle header-profile-user"
                                                    style="width:45px;height:45px;object-fit:cover;"
                                                    data-bs-toggle="tooltip" title="{{ $user->name }}">

                                                <span class="fw-semibold text-nowrap">
                                                    <a href="javascript:void(0)"
                                                        onclick="Livewire.dispatch('openPermissionsModal', { userId: {{ $user->id }} })"
                                                        class="text-primary fw-bold">
                                                        {{ $user->name ?? 'N/A' }}
                                                    </a>
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $user->email ?? 'N/A' }}</td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="badge badge-soft-info">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">No role</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if ($count > 0)
                                                <span class="badge badge-soft-primary">{{ $count }}</span>
                                            @else
                                                <span class="text-muted">No permissions</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.user.permissions.edit', $user->id) }}"
                                                class="text-success" data-bs-toggle="tooltip" title="Edit Permissions">
                                                <i class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No users found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-3">
                            {{ $users->links('pagination::bootstrap-5') }}
                        </div>

                        <p class="text-muted mt-3">
                            <span class="badge badge-soft-info">Note :</span> Click on the <span
                                class="text-primary fw-bold">Name</span> to view
                            assigned permissions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Modal -->
    <livewire:users.user-permissions-modal />
@endsection
