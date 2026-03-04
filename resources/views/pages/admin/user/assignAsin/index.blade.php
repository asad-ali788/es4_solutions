@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Assign ASIN</h4>
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
                            <form method="GET" action="{{ route('admin.assignAsin.index') }}" class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                                <div class="col-12 col-md-auto">
                                    <div class="form-floating">
                                        <select name="filter" class="form-select custom-dropdown-small"
                                            onchange="this.form.submit()">
                                            <option value="">All Users</option>
                                            <option value="reporting"
                                                {{ request('filter') === 'reporting' ? 'selected' : '' }}>
                                                Reporting to Me
                                            </option>
                                        </select>
                                        <label for="updateFilter">Filter</label>
                                    </div>
                                </div>
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
                                                        <a href="{{ route('admin.assignAssin.create', $user->id) }}">
                                                            {{ $user->name ?? 'N/A' }}
                                                        </a>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>{{ $user->email ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-soft-info">
                                                    {{ $user->roles->pluck('name')->first() ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown" style="position: relative;">
                                                    <a href="#" class="dropdown-toggle card-drop"
                                                        data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="{{ route('admin.assignAssin.create', $user->id) }}"
                                                                class="dropdown-item">
                                                                <i class="bx bx-cart font-size-18 text-success me-1"></i>
                                                                Assing ASIN
                                                            </a>
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
                            {{ $users->links('pagination::bootstrap-5') }}
                        </div>
                        <p class="text-muted mt-3">
                            <span class="badge badge-soft-info">Note:</span>
                            If a user is not assigned any ASINs, they will not be able to view any products. Only
                            higher-level users can view all products, and reporting managers can also view the ASINs
                            assigned to the users who report to them.
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
