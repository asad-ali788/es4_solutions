@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Amazon ADS - Asin Keywords</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">

                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <!-- Filters -->
                            <form method="GET" action="{{ route('admin.ads.allKeywordsAsin', ['asin' => $asin]) }}"
                                class="row g-2" id="filterForm">

                                <div class="col-auto">
                                    <x-elements.search-box />
                                </div>
                                <div class="col-auto">
                                    <input type="text" id="asinInput" class="form-control" placeholder="Enter ASIN"
                                        value="{{ $asin }}">
                                </div>
                                <div class="col-auto">
                                    <select name="match" class="form-select" onchange="this.form.submit()">
                                        <option value="all" {{ request('match', 'all') === 'all' ? 'selected' : '' }}>All
                                        </option>
                                        <option value="match" {{ request('match') === 'match' ? 'selected' : '' }}>Matching
                                            Only</option>
                                        <option value="not_match" {{ request('match') === 'not_match' ? 'selected' : '' }}>
                                            Not
                                            Matching</option>
                                        <option value="reco_not_existing"
                                            {{ request('match') === 'reco_not_existing' ? 'selected' : '' }}>
                                            Recommended Not Existing
                                        </option>
                                    </select>

                                </div>

                            </form>

                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                                @if ($table)
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Keyword ID</th>
                                        <th>Keyword</th>
                                        <th>Match Type</th>
                                        <th>Bid</th>
                                        <th>State</th>
                                        <th>Campaign ID</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($keywords as $keyword)
                                        <tr
                                            class="{{ !empty($keyword->is_recommended) ? 'table-info' : (!empty($keyword->has_reco) ? 'table-warning' : '') }}">
                                            <td>{{ $keywords->firstItem() + $loop->index }}</td>

                                            <td>{{ $keyword->keyword_id ?? 'N/A' }}</td>
                                            <td>{{ $keyword->keyword_text ??  $keyword->keyword }}</td>
                                            <td>{{ $keyword->match_type ?? 'N/A' }}</td>
                                            <td>
                                                @if (isset($keyword->bid) && $keyword->bid !== null)
                                                    {{ number_format((float) $keyword->bid, 2) }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $keyword->keyword_state ?? ($keyword->state ?? 'N/A') }}</td>
                                            <td>{{ $keyword->campaign_id ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No keyword data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @else
                                    
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Keyword</th>
                                        <th>Match Type</th>
                                        <th>Bid</th>
                                        <th>Campaign ID</th>
                                        <th>Ad Group ID</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($keywords as $keyword)
                                        <tr>
                                            <td>{{ $keywords->firstItem() + $loop->index }}</td>

                                            <td>{{ $keyword->keyword_text ??  $keyword->keyword }}</td>
                                            <td>{{ $keyword->match_type ?? 'N/A' }}</td>
                                            <td>
                                                @if (isset($keyword->bid) && $keyword->bid !== null)
                                                    {{ number_format((float) $keyword->bid, 2) }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $keyword->campaign_id ?? 'N/A' }}</td>
                                            <td>{{ $keyword->ad_group_id ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No keyword data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="mt-2">
                        {{ $keywords->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.getElementById('asinInput').addEventListener('change', function() {
                const asin = this.value.trim();
                if (!asin) return;

                const params = new URLSearchParams(window.location.search);

                // keep keyword search & match filter
                const search = params.get('search');
                const match = params.get('match');

                let url = "{{ route('admin.ads.allKeywordsAsin', ['asin' => '__ASIN__']) }}";
                url = url.replace('__ASIN__', asin);

                const newParams = new URLSearchParams();
                if (search) newParams.set('search', search);
                if (match) newParams.set('match', match);

                if ([...newParams].length) {
                    url += '?' + newParams.toString();
                }

                window.location.href = url;
            });
        </script>
    @endpush
@endsection
