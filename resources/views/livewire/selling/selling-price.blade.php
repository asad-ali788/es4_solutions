<div>
    <div class="card border-2 border-success">
        <div class="card-body">

            {{-- SELLING PRICE --}}
            <div class="form-group mb-3">
                <label class="form-label mb-2">Selling Price</label>
                <input type="text" class="form-control" wire:model.debounce.500ms="selling_price">
            </div>

            {{-- PROFIT --}}
            <div class="form-group mb-3">
                <label class="form-label mb-2">Profit</label>
                <input type="text" class="form-control" readonly value="{{ $profit }}">
            </div>

            {{-- SET AMAZON PRICE --}}
            @can('selling.set-price')
                <div class="form-group mb-3">
                    <label class="form-label mb-2">Set Amazon Price</label>
                    <div class="input-group">
                        <input type="text" class="form-control" maxlength="4" wire:model="new_price"
                            id="new_price_input"
                            oninput="
                            this.value = this.value
                                .replace(/[^0-9.]/g,'')
                                .replace(/(\..*)\./g,'$1')
                                .replace(/^(\d+)(\.\d{0,2}).*$/,'$1$2');
                            document.getElementById('set_price_button').disabled = !this.value;
                        ">

                        <button type="button" class="btn btn-success ms-2" wire:click="openModal" id="set_price_button"
                            disabled>
                            Set Price
                        </button>

                    </div>
                </div>
            @endcan
        </div>
    </div>

    {{-- MODAL --}}
    <div wire:ignore.self class="modal fade" id="setPriceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form method="POST" action="{{ route('admin.selling.setAmazonPrice') }}">
                    @csrf

                    <input type="hidden" name="uuid" value="{{ $uuid }}">
                    <input type="hidden" name="new_price" id="modal_price">

                    <div class="modal-header">
                        <h5 class="modal-title">Confirm New Amazon Price</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select class="form-select" name="price_update_reason_id" required>
                                <option value="" disabled selected>-- Select Reason --</option>
                                @foreach ($reasons as $reason)
                                    <option value="{{ $reason->id }}">{{ $reason->reason_detail }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference (optional)</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-success">Confirm & Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

{{-- JS: Open Modal --}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('openSetPriceModal', data => {
            document.getElementById('modal_price').value = data.newPrice;
            new bootstrap.Modal(document.getElementById('setPriceModal')).show();
        });
    });
</script>
