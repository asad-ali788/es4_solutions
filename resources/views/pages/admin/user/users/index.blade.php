@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Users</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.user.nav')
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                            </form>
                        </div>
                        <div class="col-12 col-lg-auto d-flex flex-wrap flex-lg-nowrap ms-lg-auto gap-2">
                            @can('user.create')
                                <div class="flex-fill flex-lg-none" style="min-width: 0;">
                                    <a href="{{ route('admin.users.create') }}">
                                        <button
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 addCustomers-modal">
                                            <i class="mdi mdi-plus me-1"></i> New User</button>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Reporting To</th>
                                    <th>Role</th>
                                    <th>User Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($users) && count($users) > 0)
                                    @foreach ($users as $index => $user)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td style="padding: 0">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="{{ $user->profile ? asset('storage/' . $user->profile) : $user->profile_photo_url }}"
                                                        alt="{{ $user->name }}" class="rounded-circle header-profile-user"
                                                        style="width:45px;height:45px;object-fit:cover;"
                                                        data-bs-toggle="tooltip" title="{{ $user->name }}">

                                                    <span class="fw-semibold text-nowrap">
                                                        {{ $user->name ?? 'N/A' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>{{ $user->email ?? 'N/A' }}</td>
                                            <td>{{ $user->reportingManager->name ?? 'N/A' }}</td>
                                            <td><span
                                                    class="badge badge-soft-info">{{ $user->roles->pluck('name')->first() ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-start">
                                                    @if (!$user->status)
                                                        <span class="badge badge-soft-danger">
                                                            Disabled
                                                        </span>
                                                    @elseif ($user->email_verified_at)
                                                        <span class="badge badge-soft-success">
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="badge badge-soft-warning" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            title="Pending email verification (initial login)">
                                                            Unverified
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dropdown" style="position: relative;">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if (auth()->user()?->canImpersonate() && auth()->id() !== $user->id)
                                                            <li>
                                                                <a href="{{ route('admin.users.impersonate', $user->id) }}"
                                                                    class="dropdown-item"
                                                                    onclick="return confirm('Impersonate this user?');">
                                                                    <i
                                                                        class="mdi mdi-account-switch font-size-16 text-warning me-1"></i>
                                                                    Impersonate
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @can('user.update')
                                                            <li>
                                                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                                                    class="dropdown-item">
                                                                    <i
                                                                        class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                                    Edit
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        @can('user.disable')
                                                            <li>
                                                                <a href="{{ route('admin.users.changeStatus', $user->id) }}"
                                                                    class="dropdown-item"
                                                                    onclick="return confirm('Are you sure you want to {{ $user->status ? 'disable' : 'enable' }} this user?');">
                                                                    @if (!$user->status)
                                                                        <i
                                                                            class="mdi mdi-check-decagram font-size-16 text-success me-1"></i>
                                                                    @else
                                                                        <i
                                                                            class="mdi mdi-close-thick font-size-16 text-danger me-1"></i>
                                                                    @endif
                                                                    {{ $user->status ? 'Disable' : 'Enable' }}
                                                                </a>
                                                            </li>
                                                        @endcan
                                                        @can('user.delete')
                                                            <li>
                                                                <form action="{{ route('admin.users.destroy', $user->id) }}"
                                                                    method="POST" onsubmit="return confirm('Are you sure?');">
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
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="mt-2">
                            {{ $users->links('pagination::bootstrap-5') }}
                        </div>
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
