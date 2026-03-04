<div>
    <input type="number" class="form-control form-control-sm" style="width:100px" min="0" max="50"
        step="0.01" wire:model.live.debounce.500ms="manual_budget"></button>
    @error('manual_budget')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
