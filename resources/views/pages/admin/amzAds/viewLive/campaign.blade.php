@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0 font-size-18">Amazon Ads - View Campaign Live</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.amzAds.performance_nav')
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Sidebar Categories --}}
                        <div class="col-lg-3">
                            <div class="card h-100 shadow-none border">
                                <div class="card-body p-3">
                                    <p class="text-muted mb-2 fw-semibold" style="font-size: 14px;">API Endpoints</p>
                                    <ul class="list-unstyled fw-medium mb-0" style="font-size: 14px;">
                                        <li class="mb-2 d-flex align-items-center gap-1">
                                            <i
                                                class="mdi mdi-bullseye-arrow {{ request()->routeIs('admin.viewLive.campaign*') ? 'text-primary' : 'text-muted' }}"></i>
                                            <a href="{{ route('admin.viewLive.campaign') }}"
                                                class="{{ request()->routeIs('admin.viewLive.campaign*') ? 'text-primary fw-bold' : 'text-dark' }}">
                                                Campaigns
                                            </a>
                                        </li>
                                        <li class="mb-2 d-flex align-items-center gap-1">
                                            <i
                                                class="mdi mdi-format-letter-matches {{ request()->routeIs('admin.viewLive.keyword') ? 'text-primary' : 'text-muted' }}"></i>
                                            <a href="{{ route('admin.viewLive.keyword') }}"
                                                class="{{ request()->routeIs('admin.viewLive.keyword') ? 'text-primary fw-bold' : 'text-dark' }}">
                                                Keywords
                                            </a>
                                        </li>
                                        <li class="mb-2 d-flex align-items-center gap-1">
                                            <i
                                                class="mdi mdi-account-group-outline {{ request()->routeIs('admin.viewLive.keywordForAdGroup*') ? 'text-primary' : 'text-muted' }}"></i>
                                            <a href="{{ route('admin.viewLive.keywordForAdGroup') }}"
                                                class="{{ request()->routeIs('admin.viewLive.keywordForAdGroup*') ? 'text-primary fw-bold' : 'text-dark' }}">
                                                Keyword for Ad Group
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Main Content --}}
                        <div class="col-lg-9">
                            {{-- Single aligned filter line --}}
                            <form method="GET" action="{{ route('admin.viewLive.campaign') }}"
                                class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <x-elements.country-select :countries="['US' => 'US', 'CA' => 'CA']" :value="request('country')" />
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB', 'SD' => 'SD']" :value="request('campaign')" />

                                <input type="text" name="campaign_id" class="form-control" placeholder="Campaign ID"
                                    value="{{ request('campaign_id') }}" style="max-width:200px;" required />
                                <button type="submit" class="btn btn-success waves-effect waves-light btn-rounded">Fetch</button>
                            </form>

                            {{-- Show ALL validation errors below the form (no layout break) --}}
                            @if ($errors->any())
                                <div class="alert alert-danger mt-2 mb-0">
                                    <ul class="mb-0 small">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            {{-- Only response below --}}
                            @if ($error)
                                <div class="alert alert-danger">{{ $error }}</div>
                            @endif

                            @if ($result)
                                <div class="mt-3">
                                    <pre class="bg-light p-3 rounded mb-0"><code>{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div> {{-- End Primary Card --}}
        </div>
    </div>
@endsection
