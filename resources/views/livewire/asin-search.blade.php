<div class="position-relative">
    <input type="text" class="form-control pe-5" placeholder="Search ASIN..." name="{{ $name }}"
        wire:model.live.debounce.200ms="search">

    @if ($selectedAsin || $search)
        <button type="button" class="asin-clear-btn position-absolute top-50 end-0 translate-middle-y"
            wire:click="clearAsin">
            <i class="bx bx-x-circle"></i>
        </button>
    @endif

    @if (!empty($results))
        <div class="bg-white border rounded shadow-sm position-absolute w-100 mt-1"
            style="max-height: 200px; overflow-y: auto; z-index: 99999;">
            @foreach ($results as $asin)
                <div class="px-2 py-1 hover-bg-light" style="cursor:pointer;"
                    wire:click="selectAsin('{{ $asin }}')">
                    {{ $asin }}
                </div>
            @endforeach
        </div>
    @endif
    <style>
        .hover-bg-light:hover {
            background-color: #e7f1ff !important;
            color: #0d6efd !important;
        }

        .asin-clear-btn {
            background: transparent;
            border: none;
            padding: 0;
            margin-right: 10px;
            font-size: 16px;
            color: #6c757d;
            cursor: pointer;
        }

        .asin-clear-btn:hover {
            color: #dc3545;
            transform: scale(1.1);
            transition: 0.15s ease;
        }
    </style>
</div>
