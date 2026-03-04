<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">Menu</li>

                <!-- Dashboard -->
                <li @class(['mm-active' => Request::is('admin/dashboard*')])>
                    <a href="{{ route('admin.dashboard') }}" class="waves-effect">
                        <i class="bx bx-home-circle"></i>
                        <span key="t-chat">Dashboard</span>
                    </a>
                </li>

                @can('product')
                    <!-- Products -->
                    <li @class(['mm-active' => Request::is('admin/products*')])>
                        <a href="{{ route('admin.products.index') }}" class="waves-effect">
                            <i class="bx bx-shopping-bag"></i>
                            <span key="t-chat">Products</span>
                        </a>
                    </li>
                @endcan

                @can('selling')
                    <!-- Selling -->
                    <li @class([
                        'mm-active' =>
                            Request::is('admin/selling*') || Request::is('admin/asin-selling*'),
                    ])>
                        <a href="{{ route('admin.selling.index') }}" class="waves-effect">
                            <i class="bx bx-store"></i>
                            <span key="t-chat">Selling</span>
                        </a>
                    </li>
                @endcan

                @can('sourcing')
                    <!-- Sourcing -->
                    <li @class(['mm-active' => Request::is('admin/sourcing*')])>
                        <a href="{{ route('admin.sourcing.index') }}" class="waves-effect">
                            <i class="bx bx-box"></i>
                            <span key="t-chat">Sourcing</span>
                        </a>
                    </li>
                @endcan

                @can('order_forecast')
                    <!-- Forecasts -->
                    <li @class([
                        'mm-active' =>
                            Request::is('admin/orderforecast*') ||
                            Request::is('admin/forecastperformance*'),
                    ])>
                        <a href="#" class="waves-effect has-arrow">
                            <i class="bx bx-line-chart"></i>
                            <span key="t-forecast">Forecast</span>
                        </a>

                        <ul class="sub-menu mm-collapse">
                            <li @class(['mm-active' => Request::is('admin/orderforecast*')])>
                                <a href="{{ route('admin.orderforecast.index') }}">
                                    Order Forecast
                                </a>
                            </li>

                            <li @class(['mm-active' => Request::is('admin/forecastperformance*')])>
                                <a href="{{ route('admin.forecastperformance.index') }}">
                                    Forecast Performance
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                @can('stocks')
                    <!-- Stocks -->
                    <li @class(['mm-active' => Request::is('admin/stocks*')])>
                        <a href="{{ route('admin.stocks.skuStocks') }}" class="waves-effect">
                            <i class="bx bx-package"></i>
                            <span key="t-forecast">Stocks</span>
                        </a>
                    </li>
                @endcan

                @can('warehouse')
                    <!-- Warehouse -->
                    <li @class(['mm-active' => Request::is('admin/warehouse*')])>
                        <a href="#" class="has-arrow waves-effect" aria-expanded="false">
                            <i class="bx bx-building-house"></i>
                            <span key="t-Warehouse">Warehouse</span>
                        </a>
                        <ul class="sub-menu">
                            @can('warehouse.list')
                                <li @class([
                                    'mm-active' =>
                                        Request::is('admin/warehouse*') &&
                                        !Request::is('admin/warehouse/allWarehouseInventory'),
                                ])>
                                    <a href="{{ route('admin.warehouse.index') }}">Warehouse</a>
                                </li>
                            @endcan
                            @can('warehouse.all-stock')
                                <li @class([
                                    'mm-active' => Request::is('admin/warehouse/allWarehouseInventory'),
                                ])>
                                    <a href="{{ route('admin.warehouse.allWarehouseInventory') }}">All
                                        Warehouse Stocks</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('shipment')
                    <!-- Shipments -->
                    <li @class(['mm-active' => Request::is('admin/shipments*')])>
                        <a href="#" class="has-arrow waves-effect" aria-expanded="false">
                            <i class="bx bxs-ship"></i>
                            <span key="t-dashboards">Shipments</span>
                        </a>
                        <ul class="sub-menu">
                            <li @class(['mm-active' => Request::is('admin/shipments*')])>
                                <a href="{{ route('admin.shipments.index') }}">Shipments</a>
                            </li>
                            @can('shipment.all-list')
                                <li @class(['mm-active' => Request::is('admin/shipmentLists*')])>
                                    <a href="{{ route('admin.shipments.shipmentLists') }}">
                                        All Shipments List
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('purchase-order')
                    <!-- Purchase Order -->
                    <li @class(['mm-active' => Request::is('admin/purchaseOrder*')])>
                        <a href="#" class="has-arrow waves-effect" aria-expanded="false">
                            <i class="bx bx-cart-alt"></i>
                            <span key="t-dashboards">Purchase Order</span>
                        </a>
                        <ul class="sub-menu">
                            <li @class(['mm-active' => Request::is('admin/purchaseOrder*')])>
                                <a href="{{ route('admin.purchaseOrder.index') }}">Purchase Order</a>
                            </li>
                            <li @class(['mm-active' => Request::is('admin/allPurchaseOrderList*')])>
                                <a href="{{ route('admin.purchaseOrder.allPurchaseOrders') }}">
                                    All Purchase Order List
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                @can('amazon-ads')
                    <!-- Amazon Ads -->
                    <li @class(['mm-active' => Request::is('admin/ads*')])>
                        <a href="#" class="waves-effect has-arrow">
                            <i class="bx bxl-amazon"></i>
                            <span key="t-chat">Amazon Ads</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            @can('amazon-ads.campaign-overview-dashboard')
                                <li @class([
                                    'mm-active' => Request::is('admin/ads/overview*'),
                                ])>
                                    <a href="{{ route('admin.ads.overview.index') }}">
                                        Ads Overview
                                    </a>
                                </li>
                            @endcan
                            @can('amazon-ads.data')
                                <li @class([
                                    'mm-active' =>
                                        Request::is('admin/ads*') &&
                                        !Request::is('admin/ads/performance*') &&
                                        !Request::is('admin/ads/schedule*') &&
                                        !Request::is('admin/ads/budget*') &&
                                        !Request::is('admin/ads/overview*'),
                                ])>
                                    <a href="{{ route('admin.ads.campaigns') }}">Ads Data</a>
                                </li>
                            @endcan
                            @can('amazon-ads.performance')
                                <li @class([
                                    'mm-active' =>
                                        Request::is('admin/ads/performance*') ||
                                        Request::is('admin/viewLive*') ||
                                        Request::is('admin/searchterms'),
                                ])>
                                    <a href="{{ route('admin.ads.performance.asins.index') }}">
                                        Ads Performance
                                    </a>
                                </li>
                            @endcan
                            @can('amazon-ads.budgets-usage')
                                <li @class(['mm-active' => Request::is('admin/ads/budget*')])>
                                    <a href="{{ route('admin.ads.budget.index') }}">
                                        Ads Budget
                                    </a>
                                </li>
                            @endcan
                            @can('amazon-ads.campaign-schedule')
                                <li @class(['mm-active' => Request::is('admin/ads/schedule*')])>
                                    <a href="{{ route('admin.ads.schedule.activeCampaigns') }}">
                                        Campaign Schedules
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('notification')
                    <!-- Notifications -->
                    <li @class(['mm-active' => Request::is('admin/notification*')])>
                        <a href="{{ route('admin.notification.index') }}" class="waves-effect">
                            <i class="bx bx-bell"></i>
                            <span key="t-chat">Notifications</span>
                        </a>
                    </li>
                @endcan

                @can('user')
                    <!-- Users -->
                    <li @class([
                        'mm-active' =>
                            Request::is('admin/users*') || Request::is('admin/assignAsin*'),
                    ])>
                        <a href="{{ route('admin.users.index') }}" class="waves-effect">
                            <i class="bx bx-user-plus"></i>
                            <span key="t-chat">Users</span>
                        </a>
                    </li>
                @endcan

                @can('data')
                    <li @class([
                        'mm-active' =>
                            Request::is('admin/data*') || Request::is('admin/currencies*'),
                    ])>
                        <a href="{{ route('admin.data.index') }}" class="waves-effect">
                            <i class="bx bx-data"></i>
                            <span key="t-chat">Data Reports & Exchange</span>
                        </a>
                    </li>
                @endcan
                @can('developer.dev-tools')
                    <li @class([
                        'mm-active' => Request::is('dev/*'),
                    ])>
                        <a href="{{ route('dev.jobs.index') }}" class="waves-effect">
                            <i class="bx bx-code-alt"></i>
                            <span key="t-chat">Dev Tools</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>
    </div>

    <div class="sidebar-footer text-center py-2"
        style="position: absolute; bottom: 0; width: 100%; background-color: #2a3042;">
        <small class="text-white-50">v.12.48.1</small>
    </div>
</div>
