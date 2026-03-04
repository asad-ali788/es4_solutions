@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">
                    {{ $isCreateMode ? 'Add' : 'Update' }}
                    Permissions - <span class="text-primary fs-6">{{ $user->name ?? '' }}</span>
                </h4>
            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <a href="{{ route('admin.user.permissions.index') }}">
                        <li class="breadcrumb-item active">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back to Permissions
                        </li>
                    </a>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.user.permissions.update', $user->id) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_id" value="{{ $user->id }}">

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

                                {{-- Select All toggle --}}
                                <div>
                                    <input type="checkbox" class="form-check-input select-all"
                                        id="select_all_{{ $moduleSlug }}">
                                    <label for="select_all_{{ $moduleSlug }}" class="form-check-label small text-muted">
                                        Select All
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                @php
                                    // Detect and group numbered labels (1-, 2-, etc.)
                                    $groups = collect($moduleGroup['permissions'])
                                        ->mapToGroups(function ($label, $name) {
                                            return [
                                                preg_match('/^(\d+)\s*-\s*(.+)$/', $label, $m)
                                                    ? (int) $m[1]
                                                    : 'normal' => [
                                                    'name' => $name,
                                                    'label' => $m[2] ?? $label,
                                                ],
                                            ];
                                        })
                                        ->sortKeys();
                                @endphp
                                <div class="{{ isset($groups['normal']) && $groups->count() === 1 ? '' : 'p-3 mb-3' }}">
                                    @foreach ($groups as $group => $perms)
                                        @if ($group !== 'normal')
                                            <h6 class="fw-bold text-primary mb-2">Group {{ $group }}</h6>
                                        @endif

                                        <div class="row mb-2">
                                            @foreach ($perms as $perm)
                                                @php $hasPermission = $user->hasPermissionTo($perm['name']); @endphp
                                                <div class="col-md-4 col-sm-6 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $perm['name'] }}"
                                                            id="permission_{{ $perm['name'] }}"
                                                            data-group="{{ $moduleSlug }}"
                                                            {{ $hasPermission ? 'checked' : '' }}
                                                            {{ $isCreateMode && $hasPermission ? 'disabled' : '' }}>
                                                        <label class="form-check-label"
                                                            for="permission_{{ $perm['name'] }}">
                                                            {{ $perm['label'] }}
                                                        </label>
                                                        @if ($isCreateMode && $hasPermission)
                                                            <input type="hidden" name="permissions[]"
                                                                value="{{ $perm['name'] }}">
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success waves-effect waves-light btn-rounded">
                        {{ $isCreateMode ? 'Add' : 'Update' }}
                    </button>
                    <a href="{{ route('admin.user.permissions.index') }}"
                        class="btn btn-light waves-effect waves-light btn-rounded">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.select-all').each(function() {
                const $toggle = $(this);
                const group = $toggle.attr('id').replace('select_all_', '');
                const $checkboxes = $(`.permission-checkbox[data-group="${group}"]`);
                const $label = $(`label[for="${$toggle.attr('id')}"]`);

                // Initialize state
                const allChecked = $checkboxes.length && $checkboxes.filter(':checked').length ===
                    $checkboxes.length;
                $toggle.prop('checked', allChecked);
                $label.text(allChecked ? 'Uncheck All' : 'Select All');

                // When "Select All" changes
                $toggle.on('change', function() {
                    const checked = $(this).is(':checked');
                    $checkboxes.prop('checked', checked);
                    $label.text(checked ? 'Uncheck All' : 'Select All');
                });

                // When any checkbox in the group changes
                $checkboxes.on('change', function() {
                    const allCheckedNow = $checkboxes.length && $checkboxes.filter(':checked')
                        .length === $checkboxes.length;
                    $toggle.prop('checked', allCheckedNow);
                    $label.text(allCheckedNow ? 'Uncheck All' : 'Select All');
                });
            });
        });
    </script>
@endsection
