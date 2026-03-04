@can('selling.notes')
    <div class="card border-2 border-secondary">

        <div class="card-body">
            <h4 class="card-title mb-4">Notes</h4>

            {{-- Notes List --}}
            <div id="notes-list" style="max-height: 180px; overflow-y: auto;" class="mb-3">
                @forelse ($listing->productNotes as $note)
                    <div class="d-flex mb-4 align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-xs">
                                <span class="avatar-title rounded-circle bg-info text-white font-size-16">
                                    {{ strtoupper(substr($note->note, 0, 1)) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex-grow-1">
                            <h5 class="font-size-13 mb-1">Note #{{ $note->id }}</h5>
                            <p class="text-muted mb-1">{{ $note->note }}</p>
                            <span class="badge bg-secondary text-capitalize">Priority: {{ $note->priority }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No notes yet.</p>
                @endforelse
            </div>

            {{-- Add Note --}}
            <div class="mb-3">
                <textarea wire:model.defer="noteText" class="form-control" rows="3" required></textarea>

                <input type="hidden" wire:model="priority" value="medium">
            </div>

            <button wire:click="addNote" wire:loading.attr="disabled" class="btn btn-primary mt-2">

                <span wire:loading.remove>Add Note</span>
                <span wire:loading>Saving...</span>

            </button>


        </div>

    </div>
@endcan
