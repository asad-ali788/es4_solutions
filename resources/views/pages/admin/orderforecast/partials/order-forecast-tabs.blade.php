<ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
    @can('order_forecast.asin')
        <li class="nav-item">
            <a href="{{ route('admin.orderforecastasin.show', $forecast->id) }}"
                class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.orderforecastasin.show') ? 'active' : '' }}">
                ASIN Metrics
            </a>
        </li>
    @endcan

    <hr class="d-sm-none my-0">

    @can('order_forecast.sku')
        <li class="nav-item">
            <a href="{{ route('admin.orderforecast.show', $forecast->id) }}"
                class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.orderforecast.show') ? 'active' : '' }}">
                SKU Metrics
            </a>
        </li>
    @endcan
</ul>
