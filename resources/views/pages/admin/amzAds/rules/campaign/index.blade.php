@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Budget Recommendation Rules</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.ads.performance.capaigns.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaigns
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
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Min ACOS</th>
                                    <th>Max ACOS</th>
                                    <th>Condition</th>
                                    <th>Recommendation</th>
                                    <th>Adjustment Type</th>
                                    <th>Adjustment Value (%)</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($rules) && count($rules) > 0)
                                    @foreach ($rules as $rule)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $rule->min_acos ?? 'N/A' }}</td>
                                            <td>{{ $rule->max_acos ?? 'N/A' }}</td>
                                            <td>{{ ucfirst($rule->spend_condition) }}</td>
                                            <td>{{ $rule->action_label }}</td>
                                            <td>{{ ucfirst($rule->adjustment_type) }}</td>
                                            <td>{{ $rule->adjustment_value ?? '-' }}</td>
                                            <td>{{ $rule->priority }}</td>
                                            <td>
                                                @if ($rule->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.ads.performance.rules.edit', $rule->id) }}"><i
                                                        class="mdi mdi-pencil font-size-16 text-primary me-1"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center">No rules available</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="mt-2">
                            {{-- Add pagination here if using paginate --}}
                        </div>
                    </div>
                    <!-- end table responsive -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
    </div>
@endsection
