@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Amazon ADS - ASIN Campaigns SP</h4>
            </div>
        </div>
    </div>

    @php
        $openIds = collect(explode(',', request('open') ?? ''))
            ->map(fn($i) => trim($i))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Nav Bar import --}}
                @include('pages.admin.amzAds.data_nav')
                <div class="card-body pt-2">

                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            {{-- Search Form --}}
                            <form method="GET" action="{{ route('admin.ads.keyword.campaignAsinsSp') }}" id="filterForm"
                                class="row g-2">
                                <!-- Search -->
                                <x-elements.search-box />
                                <!--Country Select-->
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                            </form>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ASIN</th>
                                    <th>Country</th>
                                    <th>State</th>
                                    <th>All keywods</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($asins as $asinRow)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a data-bs-toggle="collapse"
                                                href="#collapse-{{ md5($asinRow->asin . $asinRow->country) }}"
                                                role="button"
                                                aria-expanded="{{ in_array(md5($asinRow->asin . $asinRow->country), $openIds) ? 'true' : 'false' }}"
                                                aria-controls="collapse-{{ md5($asinRow->asin . $asinRow->country) }}">
                                                {{ $asinRow->asin }}
                                                @if ($asinRow->campaigns->count() > 0)
                                                    <i class="mdi mdi-book-open-page-variant-outline text-warning"
                                                        title="Campaigns available"></i>
                                                @endif
                                            </a>
                                        </td>
                                        <td>{{ $asinRow->country }}</td>
                                        <td>
                                            @if (strtoupper($asinRow->state) === 'ENABLED')
                                                <span class="badge bg-success">{{ strtoupper($asinRow->state) }}</span>
                                            @elseif (strtoupper($asinRow->state) === 'PAUSED')
                                                <span class="badge bg-warning">{{ strtoupper($asinRow->state) }}</span>
                                            @elseif (strtoupper($asinRow->state) === 'ARCHIVED')
                                                <span class="badge bg-danger">{{ strtoupper($asinRow->state) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.ads.allKeywordsAsin',$asinRow->asin) }}">
                                                <i
                                                    class="mdi mdi-alpha-k-circle-outline font-size-16 text-success me-1"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    {{-- Collapsible Campaigns --}}
                                    <tr class="collapse {{ in_array(md5($asinRow->asin . $asinRow->country), $openIds) ? 'show' : '' }}"
                                        id="collapse-{{ md5($asinRow->asin . $asinRow->country) }}">
                                        <td colspan="4">
                                            @if ($asinRow->campaigns->count() > 0)
                                                <table class="table table-sm mb-0 table-nowrap dt-responsive nowrap w-100">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Campaign ID</th>
                                                            <th>Campaign Name</th>
                                                            <th>Targeting Type</th>
                                                            <th>Budget</th>
                                                            <th>State</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($asinRow->campaigns as $campaign)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $campaign->campaign_id }}</td>
                                                                <td>{{ $campaign->campaign_name }}</td>
                                                                <td>{{ $campaign->targeting_type }}</td>
                                                                <td>{{ $campaign->daily_budget }}</td>
                                                                <td>
                                                                    <span
                                                                        class="badge bg-success">{{ $campaign->campaign_state }}</span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="text-danger text-center fw-bold">
                                                    No enabled campaigns linked.
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-danger text-center fw-bold">No ASINs available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        @if (method_exists($asins, 'links'))
                            {{ $asins->appends(request()->query())->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const PARAM = 'open';

                function readOpenFromUrl() {
                    const p = new URLSearchParams(window.location.search).get(PARAM);
                    if (!p) return [];
                    return p.split(',').map(s => s.trim()).filter(Boolean);
                }

                function writeOpenToUrl(arr) {
                    const params = new URLSearchParams(window.location.search);
                    if (!arr || arr.length === 0) {
                        params.delete(PARAM);
                    } else {
                        params.set(PARAM, arr.join(','));
                    }
                    const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
                    history.replaceState(null, '', newUrl);
                    appendOpenToPagination(arr);
                }

                function appendOpenToPagination(arr) {
                    document.querySelectorAll('.pagination a').forEach(a => {
                        const url = new URL(a.href, window.location.origin);
                        if (!arr || arr.length === 0) {
                            url.searchParams.delete(PARAM);
                        } else {
                            url.searchParams.set(PARAM, arr.join(','));
                        }
                        a.href = url.toString();
                    });
                }

                function addIfNot(arr, v) {
                    if (!arr.includes(v)) arr.push(v);
                }

                function removeIfExists(arr, v) {
                    const i = arr.indexOf(v);
                    if (i !== -1) arr.splice(i, 1);
                }

                const initial = readOpenFromUrl();
                appendOpenToPagination(initial);

                document.querySelectorAll('tr.collapse[id^="collapse-"]').forEach(el => {
                    const id = el.id.replace('collapse-', '');

                    el.addEventListener('show.bs.collapse', () => {
                        const arr = readOpenFromUrl();
                        addIfNot(arr, id);
                        writeOpenToUrl(arr);
                    });

                    el.addEventListener('hide.bs.collapse', () => {
                        const arr = readOpenFromUrl();
                        removeIfExists(arr, id);
                        writeOpenToUrl(arr);
                    });
                });
            });
        </script>
    @endpush
@endsection
