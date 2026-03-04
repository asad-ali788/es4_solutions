<div class="table-responsive">
    @if (!empty($campaignsSb) && count($campaignsSb))
        <table id="campaignSbTable" class="table table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Campaign ID</th>
                    <th>Campaign Name</th>
                    <th>Daily Budget</th>
                    <th>Country</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($campaignsSb as $campaign)
                    <tr>
                        <td>{{ $campaign['campaign_id'] }}</td>
                        <td class="sticky-column-1 ellipsis-text" title="{{ $campaign['campaign_name'] ?? '' }}">
                            {{ $campaign['campaign_name'] ?? '--' }}
                        </td>
                        <td>${{ number_format($campaign['daily_budget'], 2) }}</td>
                        <td>{{ $campaign['country'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-3 text-muted">No SB campaigns found</div>
    @endif
</div>

<div class="mt-2">
    {{ $campaignsSb->appends(['type' => 'sb'])->onEachSide(1)->links('pagination::bootstrap-5') }}
</div>
