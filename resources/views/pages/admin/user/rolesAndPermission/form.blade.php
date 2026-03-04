@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="mb-0">
                {{ $isCreateMode ? 'Create Role' : 'Edit Role' }}
            </h4>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST"
                action="{{ $isCreateMode ? route('admin.roles.store') : route('admin.roles.update', $role->id) }}">
                @csrf
                @if (!$isCreateMode)
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Role Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                placeholder="Enter role name" value="{{ old('name', $role->name ?? '') }}" required>
                            @error('name')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold fs-5">Permissions by Module</label>

                    @foreach ($permissionLabels as $moduleGroup)
                        @if (!isset($moduleGroup['permissions']) || !is_array($moduleGroup['permissions']))
                            @continue
                        @endif
                        @php
                            $moduleSlug = \Illuminate\Support\Str::slug($moduleGroup['label']);
                        @endphp

                        <div class="mb-4 border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold text-primary mb-0">{{ $moduleGroup['label'] }}</h5>
                                <div>
                                    <input type="checkbox" class="form-check-input select-all"
                                        id="select_all_{{ $moduleSlug }}">
                                    <label for="select_all_{{ $moduleSlug }}" class="form-check-label small text-muted">
                                        Select All
                                    </label>
                                </div>
                            </div>

                            @php
                                // Group permissions by leading number (like "1-", "2-", etc.)
                                $grouped = [];
                                foreach ($moduleGroup['permissions'] as $name => $label) {
                                    if (preg_match('/^(\d+)\s*-\s*(.+)$/', $label, $m)) {
                                        $grouped[(int) $m[1]][] = ['name' => $name, 'label' => trim($m[2])];
                                    } else {
                                        $grouped['normal'][] = ['name' => $name, 'label' => $label];
                                    }
                                }
                                ksort($grouped);
                            @endphp

                            <div class="{{ isset($grouped['normal']) && count($grouped) === 1 ? '' : 'p-3 mb-3' }}">
                                @foreach ($grouped as $num => $perms)
                                    @if ($num !== 'normal')
                                        <h6 class="fw-bold text-primary mb-2">Group {{ $num }}</h6>
                                    @endif
                                    <div class="row">
                                        @foreach ($perms as $perm)
                                            @php
                                                $checked = in_array($perm['name'], $selected ?? [], true);
                                            @endphp
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox" type="checkbox"
                                                        name="permissions[]" value="{{ $perm['name'] }}"
                                                        id="permission_{{ $perm['name'] }}"
                                                        data-group="{{ $moduleSlug }}" {{ $checked ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="permission_{{ $perm['name'] }}">
                                                        {{ $perm['label'] }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success waves-effect waves-light btn-rounded">
                        {{ $isCreateMode ? 'Create' : 'Update' }}
                    </button>
                    <a href="{{ route('admin.roles.index') }}"
                        class="btn btn-light waves-effect waves-light btn-rounded">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Select All JS --}}
    <script>
        $(document).ready(function() {
            $('.select-all').each(function() {
                const $toggle = $(this);
                const group = $toggle.attr('id').replace('select_all_', '');
                const $boxes = $(`.permission-checkbox[data-group="${group}"]`);
                const $label = $(`label[for="${$toggle.attr('id')}"]`);

                const allChecked = $boxes.length && $boxes.filter(':checked').length === $boxes.length;
                $toggle.prop('checked', allChecked);
                $label.text(allChecked ? 'Uncheck All' : 'Select All');

                $toggle.on('change', function() {
                    const checked = $(this).is(':checked');
                    $boxes.prop('checked', checked);
                    $label.text(checked ? 'Uncheck All' : 'Select All');
                });

                $boxes.on('change', function() {
                    const allNow = $boxes.length && $boxes.filter(':checked').length === $boxes
                        .length;
                    $toggle.prop('checked', allNow);
                    $label.text(allNow ? 'Uncheck All' : 'Select All');
                });
            });
        });
    </script>
@endsection
