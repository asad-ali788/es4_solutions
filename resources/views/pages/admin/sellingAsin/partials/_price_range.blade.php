@if ($soldPriceData->count())
    @php
        $marketplaceMap = array_flip(config('marketplaces.marketplace_ids'));
    @endphp

    <table class="table table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Marketplace</th>
                <th>Min Price</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($soldPriceData as $price)
                <tr>
                    <td>{{ $loop->iteration + ($soldPriceData->currentPage() - 1) * $soldPriceData->perPage() }}</td>

                    <td>{{ $marketplaceMap[$price->marketplace_id] ?? $price->marketplace_id }}</td>

                    <td>${{ number_format($price->min_price, 2) }}</td>

                    <td>{{ \Carbon\Carbon::parse($price->date)->format('d-m-Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-3">
        {{ $soldPriceData->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="text-center py-3 text-muted">No data found</div>
@endif
