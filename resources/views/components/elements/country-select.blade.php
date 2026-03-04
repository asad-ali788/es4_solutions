<div class="col-6 col-md-auto">
    <div class="form-floating">
        <select class="form-select custom-dropdown-small" name="country"
            onchange="document.getElementById('filterForm').submit()">
            <option value="all" {{ request('country') == 'all' ? 'selected' : '' }}>
                All</option>

            @foreach ($countries as $code => $name)
                <option value="{{ $code }}" {{ request('country') == $code ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
        <label for="country">Country</label>
    </div>
</div>
