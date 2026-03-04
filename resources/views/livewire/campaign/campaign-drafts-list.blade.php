<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Campaign Drafts</h5>

        <select class="form-select form-select-sm" style="width:160px" wire:model.live="status">
            <option value="all">All</option>
            <option value="draft">Draft</option>
            <option value="failed">Failed</option>
            <option value="submitted">Submitted</option>
        </select>
    </div>

    <div class="accordion" id="draftsAccordion">
        @forelse($drafts as $d)
            @php
                $badge = match ($d->status) {
                    'submitted' => 'bg-success-subtle text-success',
                    'failed' => 'bg-danger-subtle text-danger',
                    default => 'bg-warning-subtle text-warning',
                };
            @endphp

            <div class="accordion-item" wire:key="draft-{{ $d->id }}">
                <h2 class="accordion-header" id="draft-heading-{{ $d->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#draft-collapse-{{ $d->id }}" aria-expanded="false"
                        aria-controls="draft-collapse-{{ $d->id }}">

                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">
                                    ASIN: {{ $d->asin ?? '—' }}
                                </div>

                                <div class="text-muted small">
                                    {{ strtoupper($d->campaign_type ?? '-') }}
                                    • {{ strtoupper($d->targeting_type ?? '-') }}
                                    • Updated: {{ $d->updated_at?->format('d M, H:i') }}
                                </div>

                                @if ($d->error)
                                    <div class="text-danger small mt-1">{!! nl2br(e($d->error)) !!}</div>
                                @endif
                            </div>

                            <span class="badge rounded-pill {{ $badge }}">
                                {{ strtoupper($d->status) }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div id="draft-collapse-{{ $d->id }}" class="accordion-collapse collapse" wire:ignore.self
                    aria-labelledby="draft-heading-{{ $d->id }}" data-bs-parent="#draftsAccordion">

                    <div class="accordion-body">
                        <livewire:campaign.campaign-drafts-editor :draft-id="$d->id"
                            wire:key="draft-editor-{{ $d->id }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="text-muted small">No drafts found.</div>
        @endforelse
    </div>
</div>
