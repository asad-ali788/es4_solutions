<ul role="tablist"
    class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
    @can('user')
        <li class="nav-item">
            <a href="{{ route('admin.users.index') }}"
               class="nav-link w-100 w-sm-auto {{ Request::is('admin/users') ? 'active' : '' }}">
                Users
            </a>
        </li>
    @endcan
    <hr class="d-sm-none my-0">

    @can('user.assign-asin')
        <li class="nav-item">
            <a href="{{ route('admin.assignAsin.index') }}"
               class="nav-link w-100 w-sm-auto {{ Request::routeIs('admin.assignAsin.*') ? 'active' : '' }}">
                Assign ASIN
            </a>
        </li>
    @endcan
    <hr class="d-sm-none my-0">

    @can('user.permissions')
        <li class="nav-item">
            <a href="{{ route('admin.user.permissions.index') }}"
               class="nav-link w-100 w-sm-auto {{ Request::is('admin/users/permissions*') ? 'active' : '' }}">
                User Permissions
            </a>
        </li>
    @endcan
    <hr class="d-sm-none my-0">

    @can('user.roles')
        <li class="nav-item">
            <a href="{{ route('admin.roles.index') }}"
               class="nav-link w-100 w-sm-auto {{ Request::is('admin/users/roles*') ? 'active' : '' }}">
                Roles & Permissions
            </a>
        </li>
    @endcan
    <hr class="d-sm-none my-0">
</ul>
