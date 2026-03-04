@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18"> {{ $user ? 'Edit User' : 'Create User' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.users.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Users
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Create User</h4>
                    <p class="card-title-desc">The Default Password Will be <span class="fw-bold">"Password@123"</span></p>
                    <form action="{{ $user ? route('admin.users.update', $user->id) : route('admin.users.store') }}"
                        method="POST" id="userAddForm">
                        @csrf
                        @if ($user)
                            @method('PUT')
                        @endif
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror" placeholder="User Name"
                                        value="{{ old('name', $user->name ?? '') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">User Role</label>
                                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror"
                                        required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ (int) old('role_id', optional($user?->roles?->first())->id) === $role->id ? 'selected' : '' }}>
                                                {{ strtoupper($role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">E-Mail</label>
                                    <input type="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="Enter a valid e-mail" value="{{ old('email', $user->email ?? '') }}"
                                        required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reporting To</label>
                                    <select name="reporting_to"
                                        class="form-select @error('reporting_to') is-invalid @enderror">
                                        <option value="">-- Select --</option>
                                        @foreach ($reporting_to as $reporting)
                                            <option value="{{ $reporting->id }}"
                                                {{ old('reporting_to', $user?->reporting_to ?? '') == $reporting->id ? 'selected' : '' }}>
                                                {{ $reporting->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('reporting_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="text-end mb-3">
                                <button class="btn btn-success waves-effect waves-light btn-rounded" type="submit" >
                                    {{ $user ? 'Update User' : 'Create User' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- end select2 -->
        </div>
    </div>
@endsection
