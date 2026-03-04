<div class="card border-2">
    <div class="card-body">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="h4 card-title mb-0">Campaigns</div>

            <div class="d-flex gap-2 mb-2">

                <!-- Campaign Type -->
                <select wire:model.live="campaign_type" class="form-select form-select-sm">
                    <option value="sp">Sponsored Products (SP)</option>
                    <option value="sb">Sponsored Brands (SB)</option>
                </select>

                <!-- Country Filter -->
                <select wire:model.live="country" class="form-select form-select-sm">
                    <option value="all">All Countries</option>

                    @foreach ($uniqueCountries as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>

            </div>
        </div>

        <!-- Data Table -->
        <div class="table-responsive">

            @if ($campaignData->count())
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Campaign Name</th>
                            <th>State</th>
                            <th>Budget</th>
                            <th>Targeting</th>
                            <th>Country</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaignData as $index => $c)
                            <tr>
                                <td>{{ $campaignData->firstItem() + $index }}</td>
                                <td class="sticky-column-1 ellipsis-text fw-bold" title="{{ $c->campaign_name ?? '' }}">
                                    {{ $c->campaign_name ?? '--' }}
                                </td>
                                <td>{{ $c->campaign_state }}</td>
                                <td>${{ number_format($c->daily_budget, 2) }}</td>
                                <td>{{ $c->targeting_type }}</td>
                                <td>{{ $c->country }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $campaignData->onEachSide(1)->links(data: ['scrollTo' => false]) }}

                </div>
            @else
                <div class="text-center py-3 text-muted">No campaigns found</div>
            @endif

        </div>

    </div>
</div>
