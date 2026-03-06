<header id="page-topbar">
    <div class="navbar-header
     {{ app()->isLocal() ? 'border-bottom border-warning border-3' : '' }}">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box waves-effect">
                <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        {{-- <img src="{{ asset('assets/images/logo-sm.png') }}" alt="itrend" height="70" width="100"
                            style="margin-left: -14px;"> --}}
                    </span>
                    <span class="logo-lg">
                        {{-- <img src="{{ asset('assets/images/logo-light.png') }}" alt="itrend" height="80"
                            width="130"> --}}
                    </span>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        {{-- <img src="{{ asset('assets/images/logo-sm.png') }}" alt="itrend" height="70" width="110"
                            style="margin-left: -14px;"> --}}
                    </span>
                    <span class="logo-lg">
                        {{-- <img src="{{ asset('assets/images/logo-light.png') }}" alt="itrend" height="75"
                            width="130"> --}}
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn"
                onclick="toggleSidebar()">
                <i class="bx bx-menu-alt-left fs-2"></i>
            </button>

            <livewire:network-banner />

            <!-- App Search-->
            <form class="app-search d-none d-lg-block ms-3">
                @livewire('header-command-search')
            </form>

        </div>

        <div class="d-flex">
            <a href="{{ route('admin.ai.chat') }}">
                <button type="button" class="btn header-item noti-icon waves-effect ai-btn" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" title="AI Assistant">
                    <img src="{{ asset('assets/images/ai.svg') }}" alt="Ai" width="25" class="ai-img">
                </button>
            </a>


            {{-- <a href="https://itrendsolutions.sharepoint.com/sites/ITrendInternalDocs" target="_blank">
                <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" title="Project Documentation">
                    <i class="mdi mdi-folder-information-outline"></i>
                </button>
            </a> --}}
            <div class="dropdown d-sm-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" id="theme-toggle-btn">
                    <i class="bx bx-moon" id="theme-icon"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <i class="bx bx-bell bx-tada"></i>
                    @if ($unreadCount ?? 0 > 0)
                        <span class="badge bg-danger rounded-pill">{{ $unreadCount ?? 0 }}</span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0">Notifications</h6>
                            </div>
                        </div>
                    </div>
                    <div data-simplebar style="max-height: 230px;">
                        @forelse($unreadNotifications ?? [] as $note)
                            <a href="{{ route('admin.notification.index', ['id' => $note->id, 'status' => 'unread']) }}"
                                class="text-reset notification-item">
                                <div class="d-flex">
                                    <div class="avatar-xs me-2">
                                        <span class="avatar-title bg-primary rounded-circle font-size-16">
                                            <i class="mdi mdi-email-outline"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ Str::limit($note->title, 30) }}</h6>
                                        <div class="font-size-12 text-muted">
                                            <p class="mb-0">
                                                <i class="mdi mdi-clock-outline"></i>
                                                {{ \Carbon\Carbon::parse($note->created_date)->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-center p-2 text-muted">No new notifications</p>
                        @endforelse
                    </div>
                    <div class="p-2 border-top d-grid">
                        <a class="btn btn-sm btn-link font-size-14 text-center"
                            href="{{ route('admin.notification.index') }}">
                            <i class="mdi mdi-arrow-right-circle me-1"></i> View All
                        </a>
                    </div>
                </div>
            </div>
            @php
                $user = Auth::user();
            @endphp
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect d-flex align-items-center gap-2"
                    id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{-- profile image --}}
                    <img class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;"
                        src="{{ $user->profile ? asset('storage/' . $user->profile) : $user->profile_photo_url }}"
                        alt="">
                    <span class="d-none d-xl-inline-block" key="t-henry">{{ $user->name ?? 'User' }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end mt-0 p-1 pb-3" style="min-width: 220px;">
                    <div class="dropdown-item text-center text-muted py-1">
                        <div class="pst-clock" style="font-size: 14px;"></div>
                        <div class="pst-date" style="font-size: 12px;"></div>
                    </div>

                    <div class="dropdown-divider my-1"></div>

                    <a class="dropdown-item d-flex align-items-center py-2"
                        href="{{ route('admin.profile.index') }}">
                        <i class="bx bx-user fs-5 me-2 text-primary"></i>
                        <span>Profile</span>
                    </a>
                    <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('logout') }}">
                        <i class="bx bx-power-off fs-5 me-2 text-danger"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
