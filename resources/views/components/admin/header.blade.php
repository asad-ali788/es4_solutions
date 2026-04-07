<header id="page-topbar">
    @if(app()->isLocal())
        <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px; z-index: 1003;"
            title="Local Environment"></div>
    @endif
    @if(\Mirror\Facades\Mirror::isImpersonating())
        <div class="position-absolute top-0 start-0 w-100 bg-danger shadow-sm pulse-danger"
            style="height: 4px; z-index: 1003; --pulse-size: 4px;" title="Impersonation Mode"></div>
    @endif

    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box waves-effect position-relative"
                style="overflow: visible !important; z-index: 1005;">
                <div class="curve-top">
                    <div class="concave-top"></div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/es4-logo.png') }}" alt="itrend" height="70" width="100"
                            style="margin-left: -14px;">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/es4-logo.png') }}" alt="itrend" height="80" width="130">
                    </span>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/es4-logo.png') }}" alt="itrend" height="70" width="110"
                            style="margin-left: -14px;">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/es4-logo.png') }}" alt="itrend" height="75" width="130">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn"
                onclick="toggleSidebar()">
                <i class="bx bx-menu-alt-left fs-2 align-middle"></i>
            </button>

            <livewire:network-banner />

            <!-- App Search-->
            <form class="app-search d-none d-lg-block ms-3">
                @livewire('header-command-search')
            </form>

        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.ai.chat') }}" class="d-flex align-items-center">
                <button type="button" class="btn header-item noti-icon waves-effect ai-btn rounded-circle"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="AI Assistant"
                    style="width: 50px; height: 50px; display: inline-flex; justify-content: center; align-items: center; margin-top: 12px;">
                    <img src="{{ asset('assets/images/ai.svg') }}" alt="Ai" width="28" class="ai-img">
                </button>
            </a>

            <div class="dropdown d-sm-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect rounded-circle"
                    id="theme-toggle-btn"
                    style="width: 50px; height: 50px; display: inline-flex; justify-content: center; align-items: center; margin-top: 12px;">
                    <i class="bx bx-moon fs-3" id="theme-icon"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block position-relative">
                <button type="button" class="btn header-item noti-icon waves-effect rounded-circle"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false"
                    style="width: 50px; height: 50px; display: inline-flex; justify-content: center; align-items: center; margin-top: 12px;">
                    <i class="bx bx-bell bx-tada fs-3"></i>
                </button>
                @if ($unreadCount ?? 0 > 0)
                    <!-- Placed outside the button so waves-effect (overflow: hidden) never clips the bubble! -->
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white"
                        style="margin-top: 16px; margin-left: -18px; z-index: 5;">
                        {{ $unreadCount ?? 0 }}
                    </span>
                @endif

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 shadow-lg border-0 rounded-4"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3 bg-light rounded-top-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0 font-weight-semibold">Notifications</h6>
                            </div>
                        </div>
                    </div>
                    <div data-simplebar style="max-height: 230px;">
                        @forelse($unreadNotifications ?? [] as $note)
                            <a href="{{ route('admin.notification.index', ['id' => $note->id, 'status' => 'unread']) }}"
                                class="text-reset notification-item d-block px-3 py-2 border-bottom hover-card">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs me-2">
                                        <span
                                            class="avatar-title bg-primary-subtle text-primary rounded-circle overflow-hidden d-flex justify-content-center align-items-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="mdi mdi-email-outline fs-5"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-dark font-size-13">{{ Str::limit($note->title, 35) }}</h6>
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
                            <p class="text-center p-3 text-muted mb-0">No new notifications <i
                                    class="bx bx-check-double text-success ms-1"></i></p>
                        @endforelse
                    </div>
                    <div class="p-2 border-top d-grid">
                        <a class="btn btn-sm btn-link font-size-14 text-center rounded-bottom-4 fw-medium"
                            href="{{ route('admin.notification.index') }}">
                            <i class="mdi mdi-arrow-right-circle me-1"></i> View All
                        </a>
                    </div>
                </div>
            </div>

            @php
                $user = Auth::user();
            @endphp
            <div class="dropdown d-inline-block ms-2">
                <button type="button" class="btn header-item waves-effect d-flex align-items-center gap-2"
                    id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    style="padding: 0.4rem 0.8rem; height: 100%;">

                    <div class="d-none d-xl-flex flex-column align-items-end text-end me-1" style="line-height: 1.2;">
                        <span class="fw-semibold font-size-14 text-body"
                            key="t-henry">{{ $user->name ?? 'User' }}</span>
                        <span class="text-muted font-size-12">Administrator</span>
                    </div>

                    <img class="rounded-circle border border-2 border-primary-subtle shadow-sm p-1"
                        style="width: 42px; height: 42px; object-fit: cover;"
                        src="{{ $user->profile ? asset('storage/' . $user->profile) : $user->profile_photo_url }}"
                        alt="">
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block text-muted"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end mt-2 p-2 shadow-lg border-0 rounded-4"
                    style="min-width: 250px;">
                    <div class="dropdown-item text-center text-muted py-2 bg-light rounded-3 mb-2">
                        <div class="pst-clock fw-medium text-dark" style="font-size: 14px;"></div>
                        <div class="pst-date" style="font-size: 12px;"></div>
                    </div>

                    <a class="dropdown-item d-flex align-items-center py-2 px-3 rounded-2 hover-card"
                        href="{{ route('admin.profile.index') }}">
                        <i class="bx bx-user fs-5 me-3 text-primary"></i>
                        <span class="fw-medium">My Profile</span>
                    </a>

                    <!-- <a class="dropdown-item d-flex align-items-center py-2 px-3 rounded-2 hover-card"
                        href="https://itrendsolutions.sharepoint.com/sites/ITrendInternalDocs" target="_blank">
                        <i class="mdi mdi-folder-information-outline fs-5 me-3 text-info"></i>
                        <span class="fw-medium">Project Documentation</span>
                    </a> -->

                    <div class="dropdown-divider my-2"></div>

                    @if (\Mirror\Facades\Mirror::isImpersonating())
                        <a class="dropdown-item d-flex align-items-center py-2 px-3 rounded-2 hover-card bg-warning-subtle text-warning"
                            href="{{ route('admin.users.leave') }}">
                            <i class="mdi mdi-account-arrow-left fs-5 me-3"></i>
                            <span class="fw-bold">Leave Impersonation</span>
                        </a>
                    @endif

                    <a class="dropdown-item d-flex align-items-center py-2 px-3 rounded-2 hover-card text-danger"
                        href="{{ route('logout') }}">
                        <i class="bx bx-power-off fs-5 me-3"></i>
                        <span class="fw-medium">Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>