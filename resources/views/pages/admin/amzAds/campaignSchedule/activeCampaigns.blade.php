@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Amazon Ads - Campaign Under Schedules</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- <ul role="tablist" class="nav-tabs nav-tabs-custom pt-2 nav">
                    <li class="nav-item">
                        <a href="{{ route('admin.ads.schedule.activeCampaigns') }}"
                            class="{{ Request::is('admin/ads/schedule/underSchedule') ? 'active' : '' }} nav-link">
                            Campaign Under Schedules
                        </a>
                    </li>
                </ul> --}}
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-8">
                            <form method="GET" action="{{ route('admin.ads.schedule.activeCampaigns') }}"
                                class="d-flex align-items-center gap-2 mb-2" id="filterForm">
                                <!-- Search -->
                                <x-elements.search-box />
                                <!--Country Select-->
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                <!-- Campaign Select-->
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB', 'SD' => 'SD']" />
                            </form>
                        </div>
                        @can('amazon-ads.campaign-schedule')
                            <div class="col-sm-4">
                                <div class="text-sm-end">
                                    <a href="{{ route('admin.ads.schedule.index') }}">
                                        <button class="btn btn-primary btn-rounded waves-effect waves-light mb-2 me-2">
                                            <i class="mdi mdi-camera-timer me-1"></i> View Schedules</button>
                                    </a>
                                </div>
                            </div>
                        @endcan
                        <!-- end col-->
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover" id="customerList-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Campaign Id</th>
                                    <th>Country</th>
                                    <th>Campaign Type</th>
                                    <th>Campaign Run Status</th>
                                    <th>Campaign Enable</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($campaigns) && $campaigns->count() > 0)
                                    @foreach ($campaigns as $campaign)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            {{-- Campaign ID clickable --}}
                                            <td>
                                                {{ $campaign->campaign_id ?? 'N/A' }}
                                            </td>
                                            {{-- Country --}}
                                            <td>{{ $campaign->country ?? 'N/A' }}</td>
                                            {{-- Campaign Type --}}
                                            <td>{{ $campaign->campaign_type ?? 'N/A' }}</td>
                                            <td>
                                                @if ($campaign->run_status)
                                                    <span class="badge bg-success">Running</span>
                                                @else
                                                    <span class="badge bg-warning">Paused</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('admin.ads.schedule.runStatus') }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="campaign_id"
                                                        value="{{ $campaign->campaign_id }}">
                                                    <input type="hidden" name="status"
                                                        value="{{ $campaign->run_status ? 0 : 1 }}" class="status-input">

                                                    <div class="form-check form-check-info">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="customCheckcolor{{ $loop->iteration }}"
                                                            {{ $campaign->run_status ? 'checked' : '' }}
                                                            onchange="this.form.submit(); this.form.querySelector('.status-input').value = this.checked ? 1 : 0;">
                                                        <label class="form-check-label"
                                                            for="customCheckcolor{{ $loop->iteration }}"></label>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $campaigns->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
