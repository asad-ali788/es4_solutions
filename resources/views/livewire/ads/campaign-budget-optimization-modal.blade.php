<div>
    @if ($show)
        {{-- Backdrop --}}
        <div class="modal-backdrop fade show" wire:click="close"></div>
        {{-- Modal --}}
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.2);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content livewire-modal-anim">

                    <div class="modal-header">
                        <h5 class="modal-title">Budget Optimization Recommendations</h5>
                        <button type="button" class="btn-close" wire:click="close"></button>
                    </div>

                    {{-- Lazy load rules only after modal shows --}}
                    <div class="modal-body" wire:init="loadRules">

                        <div class="alert alert-warning mt-3" role="alert">
                            <strong>⚠️ Disclaimer:</strong> AI-generated recommendations may not always be accurate or
                            suitable
                            for every case. Please review carefully before applying changes.
                        </div>

                        <div class="alert alert-info" role="alert">
                            <strong>✨ Tip:</strong> Click <b>✨ Ai Generate</b> and wait a few seconds to receive a
                            recommendation.
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Rule based Recommendation</h5>
                            @can('amazon-ads.campaign-cron-rules.update')
                                <a href="{{ route('admin.ads.performance.rules.index') }}">
                                    <button type="button"
                                        class="btn btn-success btn-sm btn-rounded waves-effect waves-light">
                                        <i class="mdi mdi-pencil label-icon"></i> Edit Rules
                                    </button>
                                </a>
                            @endcan
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50%;">Condition</th>
                                        <th>Recommendation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!$loaded)
                                        <tr>
                                            <td colspan="2">
                                                <div class="text-center py-5">
                                                    <div class="spinner-border text-primary"></div>
                                                    <div class="mt-2">Loading Rules...</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @elseif(empty($rules))
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No rules defined yet.</td>
                                        </tr>
                                    @else
                                        @foreach ($rules as $rule)
                                            <tr>
                                                <td>{{ $rule['condition'] }}</td>
                                                <td>{{ $rule['recommendation'] }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- same CSS anim you used --}}
        <style>
            .livewire-modal-anim {
                animation: popIn .18s ease-out;
                transform-origin: center;
            }

            @keyframes popIn {
                from {
                    opacity: 0;
                    transform: scale(.96) translateY(6px);
                }

                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }
        </style>
    @endif
</div>
