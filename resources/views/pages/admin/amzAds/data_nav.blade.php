<div class="border-bottom">
  <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
    @can('amazon-ads.data.campaigns')
      {{-- Campaigns SP --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.campaigns') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.campaigns') ? 'active' : '' }}">
          Campaigns SP
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">

      {{-- Campaigns SB --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.campaignsSb') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.campaignsSb') ? 'active' : '' }}">
          Campaigns SB
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">

      {{-- Campaigns SD --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.campaignsSd') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.campaignsSd') ? 'active' : '' }}">
          Campaigns SD
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">
    @endcan

    @can('amazon-ads.keywords')
      {{-- Keywords SP --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.keywords') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.keywords') ? 'active' : '' }}">
          Keywords SP
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">

      {{-- Keywords SB --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.keywordsSb') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.keywordsSb') ? 'active' : '' }}">
          Keywords SB
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">
    @endcan

    @can('amazon-ads.data.campaigns')
      {{-- ASIN Campaigns SP --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.keyword.campaignAsinsSp') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.keyword.campaignAsinsSp') ? 'active' : '' }}">
          ASIN Campaigns SP
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">

      {{-- ASIN Campaigns SB --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.keyword.campaignAsinsSb') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.keyword.campaignAsinsSb') ? 'active' : '' }}">
          ASIN Campaigns SB
        </a>
      </li>
      <hr class="d-sm-none my-0 border-light-subtle">
    @endcan

    @can('amazon-ads.targets')
      {{-- Targets SD --}}
      <li class="nav-item">
        <a href="{{ route('admin.ads.targetsSd') }}"
           class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.targetsSd') ? 'active' : '' }}">
          Targets SD
        </a>
      </li>
      {{-- last item: no hr needed, but you can add one if you like --}}
    @endcan
  </ul>
</div>
