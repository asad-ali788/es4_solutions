@if ($productRankingData->count())
    <table class="table table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Country</th>
                <th>Min Rank</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productRankingData as $rank)
                <tr>
                    <td>{{ $loop->iteration + ($productRankingData->currentPage() - 1) * $productRankingData->perPage() }}
                    </td>
                    <td>{{ $rank->country }}</td>
                    <td class="fw-bold">{{ $rank->min_rank }}</td>
                    <td>{{ \Carbon\Carbon::parse($rank->date)->format('d-m-Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-3">
        {{ $productRankingData->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="text-center py-3 text-muted">No data found</div>
@endif
