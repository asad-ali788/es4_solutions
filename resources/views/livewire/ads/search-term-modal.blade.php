<div>
    @if ($show)
        {{-- Backdrop --}}
        <div class="modal-backdrop fade show" wire:click="close"></div>

        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.15)">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content livewire-modal-anim">

                    <div class="modal-header">
                        <h5 class="modal-title">SP Search Terms</h5>
                        <button type="button" class="btn-close" wire:click="close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- <div class="mb-2">
                            <span class="badge bg-primary">Campaign: {{ $campaignId }}</span>
                            <span class="badge bg-dark">Days: {{ $days }}</span>
                        </div> --}}

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-striped table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Keyword</th>
                                        <th>Search Term</th>
                                        <th>Impr</th>
                                        <th>Clicks</th>
                                        <th>CPC</th>
                                        <th>Cost</th>
                                        <th>Buy</th>
                                        <th>Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!$loaded)
                                        <tr>
                                            <td colspan="9">
                                                <div class="text-center py-5">
                                                    <div class="spinner-border text-primary"></div>
                                                    <div class="mt-2">Fetching Search Terms...</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @elseif(empty($rows))
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No data found</td>
                                        </tr>
                                    @else
                                        @foreach ($rows as $row)
                                            <tr>
                                                <td>{{ $row['date'] }}</td>
                                                <td>{{ $row['keyword'] }}</td>
                                                <td>{{ $row['search_term'] }}</td>
                                                <td>{{ $row['impressions'] }}</td>
                                                <td>{{ $row['clicks'] }}</td>
                                                <td>{{ $row['cost_per_click'] }}</td>
                                                <td>{{ $row['cost'] }}</td>
                                                <td>{{ $row['purchases_7d'] }}</td>
                                                <td>{{ $row['sales_7d'] }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- <style>
            .livewire-modal-anim {
                animation: zoomIn .22s ease-out;
                transform-origin: center;
            }

            @keyframes zoomIn {
                from {
                    opacity: 0;
                    transform: scale(.95) translateY(5px);
                }

                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }
        </style> --}}
    @endif
</div>
