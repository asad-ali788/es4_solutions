@extends('layouts.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    Amazon Ads - Brand Analytics
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">
                    {{-- Tabs --}}
                    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'brand_analytics' ? 'active' : '' }}" 
                               href="{{ route('admin.ads.brandAnalytics.index', request()->query()) }}">
                                Brand Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'competitor_rank' ? 'active' : '' }}" 
                               href="{{ route('admin.ads.brandAnalytics.competitorRank', request()->query()) }}">
                                Competitor Ranking
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'brand_analytics_2024' ? 'active' : '' }}" 
                               href="{{ route('admin.ads.brandAnalytics.analytics2024', request()->query()) }}">
                                Brand Analytics 2024
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'brand_analytics_weekly' ? 'active' : '' }}" 
                               href="{{ route('admin.ads.brandAnalytics.weeklyAnalytics', request()->query()) }}">
                                Brand Analytics Weekly
                            </a>
                        </li>
                    </ul>

                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-12 col-lg-9">
                            <form method="GET" action="{{ url()->current() }}" class="row g-2"
                                id="filterForm">
                                
                                @if($type == 'brand_analytics')
                                    {{-- Brand Search --}}
                                    <x-elements.search-box name="brand" placeholder="Search Brand" :value="$brand" />
                                @endif
                                
                                {{-- ASIN Search --}}
                                <x-elements.search-box name="asin" placeholder="Search ASIN" />

                                {{-- Keyword Search --}}
                                <x-elements.search-box name="keyword" placeholder="Search Keyword" />

                                <div class="col-12 col-md-auto">
                                    <button type="submit" class="btn btn-primary py-2 px-3 h-100">
                                        <i class="bx bx-filter-alt"></i> Apply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    @if ($type == 'competitor_rank')
                                        <th>ASIN</th>
                                        <th>Keyword</th>
                                        <th>Rank Value</th>
                                        <th>Report Date</th>
                                    @elseif($type == 'brand_analytics_2024')
                                        <th>ASIN</th>
                                        <th>Product Name</th>
                                        <th>Search Query</th>
                                        <th>Score</th>
                                        <th>Volume</th>
                                        <th>Imp. Total</th>
                                        <th>Imp. ASIN</th>
                                        <th>Clicks Total</th>
                                        <th>Clicks ASIN</th>
                                        <th>Purch. Total</th>
                                        <th>Purch. ASIN</th>
                                        <th>Cart Total</th>
                                        <th>Cart ASIN</th>
                                        <th>Price Total</th>
                                        <th>Price ASIN</th>
                                        <th>Ship. SameDay</th>
                                        <th>Ship. 1D</th>
                                        <th>Ship. 2D</th>
                                        <th>Date</th>
                                    @elseif($type == 'brand_analytics_weekly')
                                        <th>ASIN</th>
                                        <th>Week Number</th>
                                        <th>Week Date</th>
                                        <th>Impressions</th>
                                        <th>Clicks</th>
                                        <th>Orders</th>
                                    @else
                                        {{-- Brand Analytics --}}
                                        <th>Keyword</th>
                                        <th>ASIN</th>
                                        <th>Category</th>
                                        <th>Top 1 Brand/ASIN</th>
                                        <th>Top 2 Brand/ASIN</th>
                                        <th>Top 3 Brand/ASIN</th>
                                        <th>Match Type</th>
                                        <th>Search Volume</th>
                                        <th>Report Date</th>
                                        <th>Rank</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $index => $row)
                                    <tr>
                                        <td>{{ $results->firstItem() + $index }}</td>
                                        
                                        @if ($type == 'competitor_rank')
                                            <td><span class="text-primary">{{ $row->asin }}</span></td>
                                            <td>{{ $row->keyword }}</td>
                                            <td>
                                                @if($row->rank_value)
                                                    <span class="badge {{ $row->rank_value <= 50 ? 'bg-success' : 'bg-light text-dark' }}">
                                                        {{ $row->rank_value }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $row->report_date->format('Y-m-d') }}</td>

                                        @elseif($type == 'brand_analytics_2024')
                                            <td>{{ $row->asin }}</td>
                                            <td class="ellipsis-text" title="{{ $row->name }}" style="max-width: 150px;">{{ $row->name }}</td>
                                            <td>{{ $row->search_query }}</td>
                                            <td>{{ $row->search_query_score }}</td>
                                            <td>{{ number_format($row->search_query_volume) }}</td>
                                            <td>{{ number_format($row->impressions_total_count) }}</td>
                                            <td>{{ number_format($row->impressions_asin_count) }}</td>
                                            <td>{{ number_format($row->clicks_total_count) }}</td>
                                            <td>{{ number_format($row->clicks_asin_count) }}</td>
                                            <td>{{ number_format($row->purchases_total_count) }}</td>
                                            <td>{{ number_format($row->purchases_asin_count) }}</td>
                                            <td>{{ number_format($row->cart_adds_total_count) }}</td>
                                            <td>{{ number_format($row->cart_adds_asin_count) }}</td>
                                            <td>{{ $row->clicks_price_median }}</td>
                                            <td>{{ $row->clicks_asin_price_median }}</td>
                                            <td>{{ $row->clicks_shipping_same_day }}</td>
                                            <td>{{ $row->clicks_shipping_1d }}</td>
                                            <td>{{ $row->clicks_shipping_2d }}</td>
                                            <td>{{ $row->reporting_date->format('Y-m-d') }}</td>
                                        @elseif($type == 'brand_analytics_weekly')
                                            <td><span class="text-primary fw-medium">{{ $row->asin }}</span></td>
                                            <td><span class="badge bg-soft-info text-info">{{ $row->week_number }}</span></td>
                                            <td>{{ $row->week_date }}</td>
                                            <td class="fw-bold">{{ number_format($row->impressions) }}</td>
                                            <td class="text-info">{{ number_format($row->clicks) }}</td>
                                            <td class="text-success">{{ number_format($row->orders) }}</td>
                                        @else
                                            {{-- Brand Analytics --}}
                                            <td class="ellipsis-text" title="{{ $row->keyword }}">
                                                <span class="fw-bold">{{ $row->keyword }}</span>
                                            </td>
                                            <td>
                                                <span class="text-primary fw-medium">{{ $row->asin }}</span>
                                            </td>
                                            <td>{{ $row->target_category ?: 'N/A' }}</td>
                                            
                                            {{-- Top 1 --}}
                                            <td @class(['bg-light' => $row->target_slot == 1])>
                                                <div @class(['fw-bold text-success' => $row->top_clicked_brand_1 == $brand])>
                                                    {{ $row->top_clicked_brand_1 }}
                                                </div>
                                                <span @class(['small d-block', 'fw-bold' => $row->top_clicked_product_1_asin == $row->asin])>
                                                    {{ $row->top_clicked_product_1_asin }}
                                                </span>
                                            </td>

                                            {{-- Top 2 --}}
                                            <td @class(['bg-light' => $row->target_slot == 2])>
                                                <div @class(['fw-bold text-success' => $row->top_clicked_brand_2 == $brand])>
                                                    {{ $row->top_clicked_brand_2 }}
                                                </div>
                                                <span @class(['small d-block', 'fw-bold' => $row->top_clicked_product_2_asin == $row->asin])>
                                                    {{ $row->top_clicked_product_2_asin }}
                                                </span>
                                            </td>

                                            {{-- Top 3 --}}
                                            <td @class(['bg-light' => $row->target_slot == 3])>
                                                <div @class(['fw-bold text-success' => $row->top_clicked_brand_3 == $brand])>
                                                    {{ $row->top_clicked_brand_3 }}
                                                </div>
                                                <span @class(['small d-block', 'fw-bold' => $row->top_clicked_product_3_asin == $row->asin])>
                                                    {{ $row->top_clicked_product_3_asin }}
                                                </span>
                                            </td>

                                            <td>{{ ucfirst($row->match_type) }}</td>
                                            <td>{{ number_format($row->search_volume) }}</td>
                                            <td>{{ $row->report_date->format('Y-m-d') }}</td>
                                            <td>
                                                @if($row->rank_value)
                                                    <span class="badge {{ $row->rank_value <= 10 ? 'bg-success' : ($row->rank_value <= 50 ? 'bg-warning' : 'bg-light text-dark') }} font-size-12">
                                                        {{ $row->rank_value }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="25" class="text-center text-danger py-4">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $results->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
