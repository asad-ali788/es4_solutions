@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Developer Tools</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            @include('pages.dev.devtools.index')
            <div class="card">
                @include('pages.dev.devtools.nav')
                <div class="card-body">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div>
                            {{-- Timezone selector --}}
                            <form method="GET" action="{{ route('dev.login-history.index') }}" class="row mb-3">
                                <!-- Search -->
                                <x-elements.search-box />
                            </form>
                            @php
                                function browserIcon($browser)
                                {
                                    if (!$browser) {
                                        return null;
                                    }
                                    return match (strtolower($browser)) {
                                        'chrome' => 'chrome.svg',
                                        'firefox' => 'firefox.svg',
                                        'edge' => 'edge.svg',
                                        'safari' => 'safari.svg',
                                        'opera' => 'opera.svg',
                                        default => null,
                                    };
                                }

                                function platformIcon($platform)
                                {
                                    if (!$platform) {
                                        return null;
                                    }

                                    return match (strtolower($platform)) {
                                        'windows' => 'windows.svg',
                                        'macos', 'mac os', 'os x' => 'mac.svg',
                                        'linux' => 'linux.png',
                                        'ubuntu' => 'ubuntu.png',
                                        'android', 'androidos' => 'android.png',
                                        'ios', 'iphone', 'ipad' => 'ios.svg',
                                        default => null,
                                    };
                                }
                            @endphp
                            {{-- Table --}}
                            <div class="table-responsive">
                                <table class="table table-hover align-middle nowrap w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>IP Address</th>
                                            <th>Status</th>
                                            <th>Browser</th>
                                            <th>Platform</th>
                                            <th>Login Time</th>
                                            {{-- <th>Logout Time</th> --}}
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($logs as $log)
                                            <tr>
                                                {{-- User --}}
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-3">
                                                            <img src="{{ $log->user->profile ? asset('storage/' . $log->user->profile) : $log->user->profile_photo_url }}"
                                                                alt="{{ $log->user->name }}" class="rounded-circle"
                                                                style="width: 40px; height: 40px; object-fit: cover;"
                                                                data-bs-toggle="tooltip" title="{{ $log->user->name }}">
                                                        </div>
                                                        <div class="lh-sm">
                                                            <div class="fw-semibold">
                                                                {{ $log->user->name ?? 'Unknown' }}
                                                            </div>
                                                            <small class="text-muted">
                                                                {{ $log->user->email ?? '—' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                {{-- IP --}}
                                                <td><code>{{ $log->ip_address }}</code></td>

                                                {{-- Status --}}
                                                <td>
                                                    @if ($log->is_success)
                                                        <span class="badge bg-success">Success</span>
                                                    @else
                                                        <span class="badge bg-danger">Failed</span>
                                                    @endif
                                                </td>

                                                {{-- Browser --}}
                                                <td>
                                                    @php $icon = browserIcon($log->browser); @endphp
                                                    @if ($icon)
                                                        <img src="{{ asset('assets/images/browsers/' . $icon) }}"
                                                            alt="{{ $log->browser }}" title="{{ $log->browser }}"
                                                            width="22">
                                                    @else
                                                        <span class="text-muted">{{ $log->browser ?? '—' }}</span>
                                                    @endif
                                                </td>

                                                {{-- Platform --}}
                                                <td>
                                                    @php $icon = platformIcon($log->platform); @endphp
                                                    @if ($icon)
                                                        <img src="{{ asset('assets/images/platforms/' . $icon) }}"
                                                            alt="{{ $log->platform }}" title="{{ $log->platform }}"
                                                            width="22">
                                                    @else
                                                        <span class="text-muted">{{ $log->platform ?? '—' }}</span>
                                                    @endif
                                                </td>

                                                {{-- Login --}}
                                                <td>
                                                    {{ optional($log->logged_in_at)->format('d M Y, h:i A') ?? '—' }}
                                                </td>

                                                {{-- Logout --}}
                                                {{-- <td>
                                                    @if ($log->logged_out_at)
                                                        {{ $log->logged_out_at->format('d M Y, h:i A') }}
                                                    @elseif($log->is_success)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-warning">none</span>
                                                    @endif
                                                </td> --}}
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No login history found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $logs->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
