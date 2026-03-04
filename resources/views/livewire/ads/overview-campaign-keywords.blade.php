<div wire:key="campaign-overview-modal-{{ $campaignId }}">
    @can('amazon-ads.campaign-overview-keyword')
        {{-- Campaign id clickable text --}}
        <button type="button" class="btn btn-link p-0 align-baseline text-decoration-none" wire:click="open">
            {{ $campaignId }}
        </button>
    @else
        <span> {{ $campaignId }}</span>
    @endcan

    @if ($show)
        {{-- Backdrop --}}
        <div class="modal-backdrop fade show" wire:click="close"></div>

        {{-- Modal --}}
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.2);">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw;">
                <div class="modal-content livewire-modal-anim">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            Campaign Overview
                            <span class="text-muted small ms-2">#{{ $campaignId }}</span>
                        </h5>
                        <button type="button" class="btn-close" wire:click="close"></button>
                    </div>

                    {{-- Tabs / Menu --}}
                    <div class="modal-body py-0">
                        <ul role="tablist"
                            class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row border-bottom">

                            <li class="nav-item">
                                <a href="javascript:void(0)" role="tab"
                                    class="nav-link w-100 w-sm-auto {{ $activeTab === 'keywords' ? 'active' : '' }}"
                                    wire:click="switchTab('keywords')">
                                    Keywords
                                </a>
                            </li>

                            <hr class="d-sm-none my-0">
                            @can('amazon-ads.campaign-overview-keyword-recommendation')
                                <li class="nav-item">
                                    <a href="javascript:void(0)" role="tab"
                                        class="nav-link w-100 w-sm-auto {{ $activeTab === 'new_table' ? 'active' : '' }}"
                                        wire:click="switchTab('new_table')">
                                        Keywords Recommendations
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>

                    {{-- Content (load both once) --}}
                    <div class="modal-body" wire:init="initModalData">

                        {{-- TAB 1 --}}
                        <div class="{{ $activeTab === 'keywords' ? '' : 'd-none' }}">
                            @if (!$loaded)
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span class="text-muted">Loading keywords…</span>
                                </div>
                            @else
                                <div class="table-responsive" style="max-height: 70vh;">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Keyword Name</th>
                                                <th>Campaign Id</th>
                                                <th>ASIN</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                                <th>Country</th>

                                                <th>Click 1d</th>
                                                <th>Impressions 1d</th>

                                                <th class="table-success">Click 7d</th>
                                                <th class="table-success">Impressions 7d</th>

                                                <th>Spend 1d</th>
                                                <th>Sales 1d</th>
                                                <th>Purchase 1d</th>

                                                <th class="table-info">Spend 7d</th>
                                                <th class="table-info">Sales 7d</th>
                                                <th class="table-info">Purchase 7d</th>
                                                <th class="table-info">ACoS 7d</th>

                                                <th>Spend 14d</th>
                                                <th>Sales 14d</th>
                                                <th>Purchase 14d</th>
                                                <th>Bid Start</th>
                                                <th>Bid Suggestion</th>
                                                <th>Bid End</th>
                                                <th>Bid</th>
                                                <th class="wide-col">
                                                    <span class="ai-gradient-text">AI Suggested Bid ✨</span>
                                                </th>
                                                <th>Suggested Bid</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($rules as $index => $keyword)
                                                @php
                                                    $relatedAsins = $keyword['related_asin'] ?? null;
                                                @endphp

                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $keyword['keyword'] ?? 'N/A' }}</td>
                                                    <td>{{ $keyword['campaign_id'] ?? 'N/A' }}</td>

                                                    <td style="white-space: pre-line;">
                                                        @if (!empty($relatedAsins))
                                                            {{ implode("\n", (array) $relatedAsins) }}
                                                        @else
                                                            {{ $keyword['asin'] ?? 'N/A' }}
                                                        @endif
                                                    </td>

                                                    <td>{{ $keyword['campaign_types'] ?? 'N/A' }}</td>
                                                    <td>{{ $keyword['date'] ?? 'N/A' }}</td>
                                                    <td>{{ $keyword['country'] ?? 'N/A' }}</td>

                                                    <td>{{ $keyword['clicks'] ?? 'N/A' }}</td>
                                                    <td>{{ $keyword['impressions'] ?? 'N/A' }}</td>

                                                    <td class="table-success">{{ $keyword['clicks_7d'] ?? 'N/A' }}</td>
                                                    <td class="table-success">{{ $keyword['impressions_7d'] ?? 'N/A' }}
                                                    </td>

                                                    <td>${{ isset($keyword['total_spend']) ? number_format($keyword['total_spend'], 2) : '0.00' }}
                                                    </td>
                                                    <td>${{ isset($keyword['total_sales']) ? number_format($keyword['total_sales'], 2) : '0.00' }}
                                                    </td>
                                                    <td>{{ $keyword['purchases1d'] ?? 'N/A' }}</td>

                                                    <td class="table-info">
                                                        ${{ isset($keyword['total_spend_7d']) ? number_format($keyword['total_spend_7d'], 2) : '0.00' }}
                                                    </td>
                                                    <td class="table-info">
                                                        ${{ isset($keyword['total_sales_7d']) ? number_format($keyword['total_sales_7d'], 2) : '0.00' }}
                                                    </td>
                                                    <td class="table-info">{{ $keyword['purchases1d_7d'] ?? 'N/A' }}
                                                    </td>
                                                    <td class="table-info">{{ $keyword['acos_7d'] ?? 'N/A' }}</td>

                                                    <td>${{ isset($keyword['total_spend_14d']) ? number_format($keyword['total_spend_14d'], 2) : '0.00' }}
                                                    </td>
                                                    <td>${{ isset($keyword['total_sales_14d']) ? number_format($keyword['total_sales_14d'], 2) : '0.00' }}
                                                    </td>
                                                    <td>{{ $keyword['purchases1d_14d'] ?? 'N/A' }}</td>

                                                    <td
                                                        class="{{ ($keyword['targeting_type'] ?? '') === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                                        @if (($keyword['targeting_type'] ?? '') === 'MANUAL')
                                                            ${{ number_format($keyword['manual_bid_start'] ?? 0, 2) }}
                                                        @else
                                                            ${{ number_format($keyword['auto_bid_start'] ?? 0, 2) }}
                                                        @endif
                                                    </td>

                                                    <td
                                                        class="{{ ($keyword['targeting_type'] ?? '') === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                                        @if (($keyword['targeting_type'] ?? '') === 'MANUAL')
                                                            ${{ number_format($keyword['manual_bid_suggestion'] ?? 0, 2) }}
                                                        @else
                                                            ${{ number_format($keyword['auto_bid_median'] ?? 0, 2) }}
                                                        @endif
                                                    </td>

                                                    <td
                                                        class="{{ ($keyword['targeting_type'] ?? '') === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                                        @if (($keyword['targeting_type'] ?? '') === 'MANUAL')
                                                            ${{ number_format($keyword['manual_bid_end'] ?? 0, 2) }}
                                                        @else
                                                            ${{ number_format($keyword['auto_bid_end'] ?? 0, 2) }}
                                                        @endif
                                                    </td>

                                                    <td class="table-success">
                                                        @php
                                                            $bid = $keyword['bid'] ?? 0;
                                                            $oldBid = $keyword['old_bid'] ?? null;
                                                        @endphp

                                                        @if (!is_null($oldBid) && $bid != $oldBid)
                                                            <span class="text-decoration-line-through text-danger">
                                                                ${{ number_format($oldBid, 2) }}
                                                            </span>
                                                            <span class="fw-bold text-success">
                                                                ${{ number_format($bid, 2) }}
                                                            </span>
                                                        @else
                                                            ${{ number_format($bid, 2) }}
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if (is_numeric($keyword['ai_suggested_bid'] ?? null))
                                                            ${{ number_format($keyword['ai_suggested_bid'], 2) }}
                                                        @else
                                                            {{ $keyword['ai_suggested_bid'] ?? '--' }}
                                                        @endif
                                                    </td>

                                                    <td class="td-break-col">{{ $keyword['suggested_bid'] ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="26" class="text-center text-muted">
                                                        No keywords found for this campaign.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- TAB 2 --}}
                        <div class="{{ $activeTab === 'new_table' ? '' : 'd-none' }}">
                            @if (!$loadedReco)
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span class="text-muted">Loading recommendations…</span>
                                </div>
                            @else
                                <div class="table-responsive" style="max-height: 70vh;">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Keyword</th>
                                                <th>Match</th>
                                                <th>Bid</th>
                                                <th>Start</th>
                                                <th>Suggestion</th>
                                                <th>End</th>
                                                <th>Country</th>
                                                <th>Updated</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($recommended as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td>{{ $row['keyword'] ?? '—' }}</td>
                                                    <td>
                                                        <span class="badge badge-soft-primary">
                                                            {{ $row['match_type'] ?? '—' }}
                                                        </span>
                                                    </td>
                                                    <td>${{ isset($row['bid']) ? number_format($row['bid'], 2) : '0.00' }}
                                                    </td>
                                                    <td>${{ isset($row['bid_start']) ? number_format($row['bid_start'], 2) : '0.00' }}
                                                    </td>
                                                    <td>${{ isset($row['bid_suggestion']) ? number_format($row['bid_suggestion'], 2) : '0.00' }}
                                                    </td>
                                                    <td>${{ isset($row['bid_end']) ? number_format($row['bid_end'], 2) : '0.00' }}
                                                    </td>
                                                    <td>{{ $row['country'] ?? '—' }}</td>
                                                    <td>
                                                        @if (!empty($row['updated_at']))
                                                            {{ \Carbon\Carbon::parse($row['updated_at'])->timezone(config('timezone.market'))->format('Y-m-d H:i') }}
                                                            PST
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        No recommendations found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
