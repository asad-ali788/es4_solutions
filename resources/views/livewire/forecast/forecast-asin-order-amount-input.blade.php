<input type="number" class="form-control form-control-sm" maxlength="5" step="0.01"
    wire:model.live.debounce.500ms="order_amount">
@error('order_amount')
    <div class="text-danger small">{{ $message }}</div>
@enderror
