<div class="col-6 col-md-auto">
    <div class="form-floating">
        <select class="form-select custom-dropdown-small" name="campaign"
            onchange="document.getElementById('filterForm').submit()">
            <option value="all" {{ request('campaign') == 'all' ? 'selected' : '' }}>
                All</option>

            @foreach ($campaigns as $code => $label)
                <option value="{{ $code }}" {{ request('campaign') == $code ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <label for="campaign">Campaign Type</label>
    </div>
</div>
