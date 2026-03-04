<ul role="tablist" class="nav-tabs nav-tabs-custom pt-2 nav">
    @can('amazon-ads.budgets-usage')
        <li class="nav-item">
            <a href="{{ route('admin.ads.budget.index') }}"
                class="{{ Request::is('admin/ads/budget') ? 'active' : '' }} nav-link">
                Budget Usages
            </a>
        </li>
    @endcan
    @can('amazon-ads.budget-recommendation')
        <li class="nav-item">
            <a href="{{ route('admin.ads.budget.recommendations') }}"
                class="{{ Request::is('admin/ads/budget/recommendations') ? 'active' : '' }} nav-link">
                Budget Recommendations
            </a>
        </li>
    @endcan
</ul>
