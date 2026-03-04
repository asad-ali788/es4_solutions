@extends('layouts.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $permission ? 'Edit Permission' : 'Create Permission' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.roles.permissions.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Permissions
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ $permission ? 'Edit Permission' : 'Create Permission' }}</h4>
                    <p class="card-title-desc">
                        Add a machine-readable <b>name</b>, an optional human-friendly <b>label</b>, and select the
                        <b>guard</b>.
                    </p>

                    <form
                        action="{{ $permission
                            ? route('admin.roles.permissions.update', $permission->id)
                            : route('admin.roles.permissions.store') }}"
                        method="POST">
                        @csrf
                        @if ($permission)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-lg-6">
                                <!-- Name -->
                                <div class="mb-3">
                                    <label class="form-label">Permission <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="e.g. users.view" value="{{ old('name', $permission->name ?? '') }}"
                                        required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <!-- Label -->
                                <div class="mb-3">
                                    <label class="form-label">Label</label>
                                    <input type="text" name="label"
                                        class="form-control @error('label') is-invalid @enderror"
                                        placeholder="e.g. View Users" value="{{ old('label', $permission->label ?? '') }}">
                                    @error('label')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="text-end mb-3">
                                <button class="btn btn-success waves-effect waves-light btn-rounded" type="submit">
                                    {{ $permission ? 'Update Permission' : 'Create Permission' }}
                                </button>
                            </div>
                        </div>
                    </form>
                    <p class="text-muted small mb-0">
                        <strong>⚠️ Important:</strong> All newly created permissions are <b>automatically assigned</b> to
                        both
                        <b>Administrator</b> and <b>Developer</b> roles.<br>
                        After creating or editing a permission, you <b>must also update the corresponding Enum constants</b>
                        to ensure system consistency and prevent access or authorization errors.
                        Failure to do so may cause <b>unexpected permission issues</b> or break existing role logic.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
