<div class="card border-2">
    <div class="card-body">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="h4 card-title mb-0">Product Ranking</div>

            <div class="d-flex gap-2 mb-2">

                <!-- Ranking Days Filter -->
                <select wire:model.live="ranking_days" class="form-select form-select-sm">
                    <option value="7">Last 7 Days</option>
                    <option value="15">Last 15 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>

                <!-- Ranking Country Filter -->
                <select wire:model.live="ranking_country" class="form-select form-select-sm">
                    <option value="all">All Countries</option>

                    @foreach ($uniqueCountries as $country)
                        <option value="{{ $country }}">{{ $country }}</option>
                    @endforeach
                </select>

            </div>
        </div>

        <!-- Ranking Table -->
        <div class="table-responsive">

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
                        @foreach ($productRankingData as $index => $rank)
                            <tr>
                                <td>{{ $productRankingData->firstItem() + $index }}</td>
                                <td>{{ $rank->country }}</td>
                                <td class="fw-bold">{{ $rank->min_rank }}</td>
                                <td>{{ \Carbon\Carbon::parse($rank->date)->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $productRankingData->onEachSide(1)->links(data: ['scrollTo' => false]) }}
                </div>
            @else
                <div class="text-center py-3 text-muted">No data found</div>
            @endif

        </div>

    </div>
</div>
