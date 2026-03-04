<div class="position-relative">
    <div wire:ignore>
        <select id="skuSelect2" name="sku[]" class="form-select {{ $error ? 'is-invalid' : '' }}"
            {{ !$asin ? 'disabled' : '' }} multiple>
            @if (!$asin)
                <option value="">Select ASIN first</option>
            @elseif (empty($skuOptions))
                <option value="">No SKU found</option>
            @else
                @foreach ($skuOptions as $sku)
                    <option value="{{ $sku }}" @selected(in_array($sku, $selectedSkus, true))>
                        {{ $sku }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    @if ($error)
        <small class="text-danger position-absolute" style="bottom:-18px; left:0;">
            {{ $error }}
        </small>
    @endif

    <div wire:loading.flex wire:target="asin" class="text-muted small mt-1">
        Loading SKUs...
    </div>

    @once
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
            function initSkuSelect2(component) {
                const el = document.getElementById('skuSelect2');
                if (!el) return;

                if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
                    console.error('Select2: jQuery/select2 not loaded');
                    return;
                }

                const $el = jQuery(el);

                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.off('change.skuSelect2');
                    $el.select2('destroy');
                }

                $el.select2({
                    width: '100%',
                    placeholder: 'Select SKU(s)',
                    closeOnSelect: false,
                    allowClear: true
                });

                $el.on('change.skuSelect2', function() {
                    const val = $el.val() || [];
                    component.call('setSelectedSkus', val);
                });
            }

            document.addEventListener('livewire:init', () => {
                Livewire.hook('message.processed', (message, component) => {
                    if (component.el && component.el.querySelector && component.el.querySelector(
                            '#skuSelect2')) {
                        initSkuSelect2(component);
                    }
                });

                Livewire.on('sku-select2-refresh', () => {
                    const root = document.getElementById('skuSelect2')?.closest('[wire\\:id]');
                    if (!root) return;

                    const id = root.getAttribute('wire:id');
                    if (!id) return;

                    const component = Livewire.find(id);
                    if (!component) return;

                    initSkuSelect2(component);
                });
            });
        </script>
    @endonce
</div>
