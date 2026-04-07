@props(['name', 'placeholder', 'value' => null])

{{-- Search --}}
<div class="col-12 col-md-auto">
    <div class="search-box d-flex align-items-center position-relative bg-light px-2 py-2"
         style="min-width: 200px;">

        <i class="bx bx-search-alt search-icon"></i>

        <input type="text"
               name="{{ $name }}"
               id="search-{{ $name }}"
               class="form-control border-0 bg-light flex-grow-1"
               placeholder="{{ $placeholder }}"
               value="{{ $value ?? request($name) }}"
               oninput="toggleClearButton('{{ $name }}')">

        <button type="button"
                class="btn-clear bg-light border-0 position-absolute end-0 me-2"
                id="clear-{{ $name }}"
                onclick="clearSearch('{{ $name }}')"
                style="display: none;">
            <i class="bx bx-x fs-5"></i>
        </button>

        @if(request()->has('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif

    </div>
</div>
