@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Permissions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.roles.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Roles & Permissions
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.roles.permissions.index') }}" class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                            </form>
                        </div>
                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2">
                            @can('user.create')
                                <div class="flex-fill flex-lg-none" style="min-width: 0;">
                                    <a href="{{ route('admin.roles.permissions.create') }}">
                                        <button
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 addCustomers-modal">
                                            <i class="mdi mdi-plus me-1"></i> New Permission
                                        </button>
                                    </a>
                                </div>
                            @endcan
                        </div><!-- end col-->
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Label</th>
                                    <th>Permission</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($permissions) && count($permissions) > 0)
                                    @foreach ($permissions as $index => $permission)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $permission->label ?? 'N/A' }}</td>
                                            <td><span
                                                    class="badge badge-soft-primary me-1">{{ $permission->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <div class="dropdown" style="position: relative;">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @can('user.permissions.update')
                                                            <li>
                                                                <a href="{{ route('admin.roles.permissions.edit', $permission->id) }}"
                                                                    class="dropdown-item">
                                                                    <i
                                                                        class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                    Edit
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        <li>
                                                            <form
                                                                action="{{ route('admin.roles.permissions.destroy', $permission->id) }}"
                                                                method="POST"
                                                                onsubmit="return confirm('Deleting this permission will remove it from all roles and may cause access or authorization issues for affected users. Are you sure you want to proceed?');"
                                                                style="display:inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item">
                                                                    <i
                                                                        class="mdi mdi-trash-can font-size-16 text-danger me-1"></i>
                                                                    Delete
                                                                </button>
                                                            </form>
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
                            {{ $permissions->links('pagination::bootstrap-5') }}
                        </div>
                        <p class="text-muted mt-3">
                            <span class="badge badge-soft-info">Note:</span>
                            Always check the permission name before creating it to avoid duplication, and ensure it works as
                            intended after adding.
                        </p>
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
