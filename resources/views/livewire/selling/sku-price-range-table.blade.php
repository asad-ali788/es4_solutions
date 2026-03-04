<div class="card border-2">
    <div class="card-body">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="h4 card-title mb-0">Product Price Range</div>

            <div class="d-flex gap-2 mb-2">

                <!-- Days Filter -->
                <select wire:model.live="price_days" class="form-select form-select-sm">
                    <option value="7">Last 7 Days</option>
                    <option value="15">Last 15 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>

                <!-- Country Filter -->
                <select wire:model.live="price_country" class="form-select form-select-sm">
                    <option value="all">All Countries</option>

                    @foreach ($uniqueCountries as $country)
                        <option value="{{ $country }}">{{ $country }}</option>
                    @endforeach
                </select>

            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">

            @if ($priceRangeData->count())
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Country</th>
                            <th>Min Price</th>
                            <th>Max Price</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($priceRangeData as $index => $row)
                            <tr>
                                <td>{{ $priceRangeData->firstItem() + $index }}</td>
                                <td>{{ $row->country }}</td>
                                <td class="fw-bold">{{ number_format($row->min_price, 2) }}</td>
                                {{-- <td>{{ number_format($row->max_price, 2) }}</td> --}}
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $priceRangeData->onEachSide(1)->links(data: ['scrollTo' => false]) }}
                </div>
            @else
                <div class="text-center py-3 text-muted">No data found</div>
            @endif

        </div>

    </div>
</div>
