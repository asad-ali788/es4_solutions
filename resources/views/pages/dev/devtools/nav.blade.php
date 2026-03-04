{{-- Bottom menu (jobs, failed jobs, db backup, cron schedules, etc.) --}}

<ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row system-tools-tabs">
    @can('developer.jobs')
        <li class="nav-item">
            <a href="{{ route('dev.jobs.index') }}"
                class="nav-link w-100 w-sm-auto {{ Request::routeIs('dev.jobs.index') ? 'active' : '' }}">
                Jobs
            </a>
        </li>
        <hr class="d-sm-none my-0">
        <li class="nav-item">
            <a href="{{ route('dev.jobs.failed') }}"
                class="nav-link w-100 w-sm-auto {{ Request::routeIs('dev.jobs.failed') ? 'active' : '' }}">
                Failed Jobs
            </a>
        </li>
    @endcan
    <hr class="d-sm-none my-0">
    @can('developer.database-backup')
        <li class="nav-item">
            <a href="{{ route('dev.backups.index') }}"
                class="nav-link w-100 w-sm-auto {{ Request::routeIs('dev.backups.index') ? 'active' : '' }}">
                Database Backups
            </a>
        </li>
    @endcan
    <hr class="d-sm-none my-0">
    @can('developer.schedule-list')
        <li class="nav-item">
            <a href="{{ route('dev.schedule.index') }}"
                class="nav-link w-100 w-sm-auto {{ Request::routeIs('dev.schedule.index') ? 'active' : '' }}">
                Cron Schedules
            </a>
        </li>
    @endcan
    @can('developer')
        <li class="nav-item">
            <a href="{{ route('dev.login-history.index') }}"
                class="nav-link w-100 w-sm-auto {{ Request::routeIs('dev.login-history.index') ? 'active' : '' }}">
                Login Logs
            </a>
        </li>
    @endcan
</ul>
