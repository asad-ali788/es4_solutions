<div>
    @if ($show)
        {{-- Backdrop --}}
        <div class="modal-backdrop fade show" wire:click="close"></div>

        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.15)">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header bg-light">
                        <h5 class="modal-title">
                            Permissions for {{ $userName }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="close"></button>
                    </div>

                    <div class="modal-body">
                        @if (!$loaded)
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                                <div class="mt-2">Loading permissions...</div>
                            </div>
                        @elseif (empty($groupedPermissions))
                            <p class="text-center text-muted mb-0">
                                No permissions assigned.
                            </p>
                        @else
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Module</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupedPermissions as $module)
                                        <tr>
                                            <td><strong>{{ $module['label'] }}</strong></td>
                                            <td>
                                                @foreach ($module['permissions'] as $perm)
                                                    <span class="badge bg-success me-1 mb-1">
                                                        {{ $perm }}
                                                    </span>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
