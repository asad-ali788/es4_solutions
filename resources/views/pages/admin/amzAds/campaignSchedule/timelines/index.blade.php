@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Amazon Ads - Campaign Schedules</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.ads.schedule.activeCampaigns') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaign Under Schedule
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                        </div>
                        @can('amazon-ads.campaign-schedule.add')
                            <div class="col-sm-4">
                                <div class="text-sm-end">
                                    <a href="{{ route('admin.ads.schedule.create') }}">
                                        <button class="btn btn-success btn-rounded waves-effect waves-light mb-2 me-2">
                                            <i class="mdi mdi-plus me-1"></i> Add Schedules</button>
                                    </a>
                                </div>
                            </div>
                        @endcan

                        <!-- end col-->
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Day Of Week</th>
                                    <th>Country</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours On</th>
                                    <th>Hours Off</th>
                                    @can('amazon-ads.campaign-schedule.update')
                                        <th>Action</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @if ($campaigns->count() > 0)
                                    @foreach ($campaigns as $index => $campaigns)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $campaigns->day_of_week ?? 'N/A' }}</td>
                                            <td>{{ $campaigns->country ?? 'N/A' }}</td>
                                            <td>{{ $campaigns->start_time ?? 'N/A' }}</td>
                                            <td>{{ $campaigns->end_time ?? 'N/A' }}</td>
                                            <td>
                                                {{ $campaigns->hours_on ? sprintf('%02d:%02d', floor($campaigns->hours_on), round(($campaigns->hours_on - floor($campaigns->hours_on)) * 60)) : 'N/A' }}
                                            </td>
                                            <td>
                                                {{ $campaigns->hours_off ? sprintf('%02d:%02d', floor($campaigns->hours_off), round(($campaigns->hours_off - floor($campaigns->hours_off)) * 60)) : 'N/A' }}
                                            </td>
                                            @can('amazon-ads.campaign-schedule.update')
                                                <td>
                                                    <div class="dropdown" style="position: relative;">
                                                        <a href="{{ route('admin.ads.schedule.edit', $campaigns->id) }}"
                                                            class="dropdown-item">
                                                            <i class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                                            Edit
                                                        </a>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <!-- end table -->
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
