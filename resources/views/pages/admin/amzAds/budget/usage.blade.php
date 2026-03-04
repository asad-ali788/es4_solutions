@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Amazon Ads - Budget Usages</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.amzAds.budget.nav')

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <form method="GET" action="{{ route('admin.ads.budget.index') }}" class="row g-2">
                                <x-elements.search-box />
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>ASIN</th>
                                    <th>Campaign ID</th>
                                    <th>Campaign Type</th>
                                    <th>Budget</th>
                                    <th>Budget Usage %</th>
                                    <th>Usage Updated At</th>
                                    <th>Last Synced</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($budgetUsages as $row)
                                    <tr>
                                        <td>{{ $budgetUsages->firstItem() + $loop->index }}</td>
                                        <td>{{ $row->product_name ?? 'N/A' }}</td>
                                        <td>{{ $row->asin ?? 'N/A' }}</td>
                                        <td>{{ $row->campaign_id ?? 'N/A' }}</td>
                                        <td>{{ $row->campaign_type ?? 'N/A' }}</td>
                                        <td>{{ is_null($row->budget) ? 'N/A' : number_format((float) $row->budget, 2) }}</td>
                                        <td>{{ is_null($row->budget_usage_percent) ? 'N/A' : number_format((float) $row->budget_usage_percent, 2) . '%' }}</td>
                                        <td>{{ $row->usage_updated_at ?? 'N/A' }}</td>
                                        <td>{{ $row->updated_at ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        {{ $budgetUsages->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
