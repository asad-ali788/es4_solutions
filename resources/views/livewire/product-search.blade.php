<div class="position-relative">
    <input type="text" class="form-control pe-5" placeholder="Search Product..." name="{{ $name }}"
        wire:model.live.debounce.200ms="search" wire:keydown.enter.prevent="submitTypedProduct">

    @if ($selectedProduct || $search)
        <button type="button" class="product-clear-btn position-absolute top-50 end-0 translate-middle-y"
            wire:click="clearProduct">
            <i class="bx bx-x-circle"></i>
        </button>
    @endif

    @if (!empty($results))
        <div class="bg-white border rounded shadow-sm position-absolute w-100 mt-1"
            style="max-height: 220px; overflow-y: auto; z-index: 99999;">
            @foreach ($results as $name)
                <div class="px-2 py-1 hover-bg-light" style="cursor:pointer;"
                    wire:click="selectProduct('{{ addslashes($name) }}')">
                    {{ $name }}
                </div>
            @endforeach
        </div>
    @endif

    <style>
        .hover-bg-light:hover {
            background-color: #e7f1ff !important;
            color: #0d6efd !important;
        }

        .product-clear-btn {
            background: transparent;
            border: none;
            padding: 0;
            margin-right: 10px;
            font-size: 16px;
            color: #6c757d;
            cursor: pointer;
        }

        .product-clear-btn:hover {
            color: #dc3545;
            transform: scale(1.1);
            transition: 0.15s ease;
        }
    </style>
</div>
