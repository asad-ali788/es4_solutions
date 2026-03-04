@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Amazon ADS - Targets SD</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">

                {{-- Nav Bar import --}}
                @include('pages.admin.amzAds.data_nav')

                <div class="card-body pt-2">
                    <div class="d-flex align-items-center gap-2">
                        @php
                            $action = route('admin.ads.targetsSd');
                        @endphp

                        <!-- Filters -->
                        <form method="GET" action="{{ $action }}"
                            class="d-flex flex-wrap mb-2 align-items-center gap-2" id="filterForm">
                            <!-- Search -->
                            <x-elements.search-box />
                            <!--Country Select-->
                            <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Target ID</th>
                                    <th>Campaign ID</th>
                                    <th>Ad Group ID</th>
                                    <th>State</th>
                                    {{-- <th>Bid</th> --}}
                                    <th>Expression Type</th>
                                    <th>Expression</th>
                                    {{-- <th>Resolved Expression</th> --}}
                                    <th>Region</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($targets as $target)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $target->target_id ?? 'N/A' }}</td>
                                        <td>{{ $target->campaign_id ?? 'N/A' }}</td>
                                        <td>{{ $target->ad_group_id ?? 'N/A' }}</td>
                                        <td>
                                            @if (strtoupper($target->state) === 'ENABLED')
                                                <span class="badge bg-success">{{ strtoupper($target->state) }}</span>
                                            @elseif (strtoupper($target->state) === 'PAUSED')
                                                <span class="badge bg-warning">{{ strtoupper($target->state) }}</span>
                                            @elseif (strtoupper($target->state) === 'ARCHIVED')
                                                <span class="badge bg-danger">{{ strtoupper($target->state) }}</span>
                                            @else
                                                {{ $target->state ?? 'N/A' }}
                                            @endif
                                        </td>
                                        {{-- <td>{{ $target->bid ?? 'N/A' }}</td> --}}
                                        <td>{{ $target->expression_type ?? 'N/A' }}</td>
                                        <td>
                                            @if (is_array($target->expression))
                                                @foreach ($target->expression as $expr)
                                                    <div>
                                                        Type: <strong>{{ $expr['type'] ?? 'N/A' }}</strong>,
                                                        Value: <strong>{{ $expr['value'] ?? 'N/A' }}</strong>
                                                    </div>
                                                @endforeach
                                            @else
                                                {{ $target->expression ?? 'N/A' }}
                                            @endif
                                        </td>

                                        {{-- <td>{{ is_array($target->resolved_expression) ? json_encode($target->resolved_expression) : $target->resolved_expression }}</td> --}}
                                        <td>{{ strtoupper($target->region ?? 'N/A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No target data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        {{ $targets->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
