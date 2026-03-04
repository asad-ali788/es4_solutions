<div class="border-bottom">
    <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">

        @can('amazon-ads.asin-performance')
            {{-- ASIN Performance --}}
            <li class="nav-item">
                <a href="{{ route('admin.ads.performance.asins.index') }}"
                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.performance.asins*') ? 'active' : '' }}">
                    ASIN Performance
                </a>
            </li>
            <hr class="d-sm-none my-0 border-light-subtle">
        @endcan

        @can('amazon-ads.campaign-performance')
            {{-- Campaign Performance --}}
            <li class="nav-item">
                <a href="{{ route('admin.ads.performance.capaigns.index') }}"
                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.performance.capaigns*') ? 'active' : '' }}">
                    Campaign Performance
                </a>
            </li>
            <hr class="d-sm-none my-0 border-light-subtle">
        @endcan

        @can('amazon-ads.keyword-performance')
            {{-- Keyword Performance --}}
            <li class="nav-item">
                <a href="{{ route('admin.ads.performance.keywords.index') }}"
                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.performance.keywords*') ? 'active' : '' }}">
                    Keyword Performance
                </a>
            </li>
            <hr class="d-sm-none my-0 border-light-subtle">
        @endcan

        @can('amazon-ads.target-performance')
            {{-- Target Performance --}}
            <li class="nav-item">
                <a href="{{ route('admin.ads.performance.targets.index') }}"
                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.performance.targets*') ? 'active' : '' }}">
                    Target Performance
                </a>
            </li>
            <hr class="d-sm-none my-0 border-light-subtle">
        @endcan

        @can('amazon-ads.search-terms.export')
            <li class="nav-item">
                <a href="{{ route('admin.searchterms.index') }}"
                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.searchterms.index*') ? 'active' : '' }}">
                    Search Terms (SP)
                </a>
            </li>
        @endcan

        <hr class="d-sm-none my-0 border-light-subtle">

        @can('amazon-ads.view-live-performance')
            {{-- View Live --}}
            <li class="nav-item">
                <a href="{{ route('admin.viewLive.campaign') }}"
                    class="nav-link w-100 w-sm-auto {{ Request::is('admin/viewLive*') ? 'active' : '' }}">
                    View Live
                </a>
            </li>
            {{-- Last item → no <hr> --}}
        @endcan

    </ul>
</div>
