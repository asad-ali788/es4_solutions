@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Keyword Bid Recommendation Rules</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.ads.performance.keywords.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Keywords
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
                    <div class="d-flex justify-content-end align-items-center mb-3">
                        {{-- <a href="{{ route('admin.ads.performance.rules.keyword.create') }}" class="btn btn-success">
                            + Add New Rule
                        </a> --}}
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Condition</th>
                                    <th>Recommendation</th>
                                    <th>Bid Adjustment</th>
                                    <th>Sales</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rules as $rule)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>

                                        {{-- Display human-readable conditions from accessor --}}
                                        <td>{{ $rule->condition_text }}</td>

                                        {{-- Recommendation --}}
                                        <td>{{ $rule->action_label }}</td>
                                        
                                        {{-- Bid Adjustment --}}
                                        <td>{{ $rule->bid_adjustment ?? '-' }}</td>
                                        <td>{{ $rule->sales_condition }}</td>


                                        {{-- Status --}}
                                        <td>
                                            @if ($rule->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>

                                        {{-- Edit Action --}}
                                        <td>
                                            <a href="{{ route('admin.ads.performance.rules.keyword.edit', $rule->id) }}">
                                                <i class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No rules available</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>

                        <div class="mt-2">
                            {{ $rules->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
