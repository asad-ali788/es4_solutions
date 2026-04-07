@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Profile</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-xl-4">
            <div class="card overflow-hidden">
                <div class="bg-primary-subtle">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-3">
                                <h5 class="text-primary">Welcome Back!</h5>
                                <p>Manage your profile details</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="avatar-md profile-user-wid mb-4">
                                <img src="{{ $user->profile ? asset('storage/' . $user->profile) : $user->profile_photo_url }}"
                                    alt="" class="img-thumbnail rounded-circle"
                                    style="width: 72px; height: 72px; object-fit: cover;">
                                <div class="position-absolute" style="bottom: 5px; right: -5px;">
                                    <form action="{{ route('admin.profile.destroy', $user->id) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete your profile picture?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm rounded-circle p-0" style="width: 24px; height: 24px;">
                                            <i class="bx bx-trash font-size-12"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="pt-4">
                                <h5 class="font-size-15 mb-1">{{ $user->name ?? 'N/A' }}</h5>
                                <p class="text-muted mb-0 font-size-13">{{ $user->getRoleNames()->first() ?? 'Administrator' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end card -->

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Account Information</h4>
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0 font-size-14">
                            <tbody>
                                <tr>
                                    <th scope="row" class="ps-0 border-top-0 w-50">Full Name :</th>
                                    <td class="text-muted border-top-0">{{ $user->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0 w-50">Mobile :</th>
                                    <td class="text-muted">{{ $user->mobile ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0 w-50">E-mail :</th>
                                    <td class="text-muted">{{ $user->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0 border-bottom-0 w-50">Role :</th>
                                    <td class="text-muted border-bottom-0">{{ $user->getRoleNames()->first() ?? 'Administrator' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- end card -->
        </div>

        <div class="col-xl-8">
            <div class="card mb-4">
                <form action="{{ route('admin.profile.store') }}" method="POST" id="profileForm" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <h4 class="card-title mb-4">Personal Information</h4>
                        
                        <input type="hidden" name="formAction" value="update">
                        <input type="hidden" name="user_id" value="{{ $user->id }}">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input id="name" name="name" type="text"
                                    class="form-control @error('name') is-invalid @enderror" placeholder="Enter your full name"
                                    value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3 mt-md-0">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" placeholder="Enter your email"
                                    value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mobile" class="form-label">Mobile Number</label>
                                <input type="text" class="form-control @error('mobile') is-invalid @enderror"
                                    id="mobile" name="mobile" placeholder="Enter your mobile number"
                                    value="{{ old('mobile', $user->mobile) }}">
                                @error('mobile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3 mt-md-0">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input class="form-control @error('profile_image') is-invalid @enderror" type="file"
                                    id="profile_image" name="profile_image">
                                @error('profile_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                class="btn btn-primary w-md" data-loading-text="Updating...">Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card shadow-none border">
                <form action="{{ route('admin.updatePassword') }}" method="POST" id="ChangePasswordForm">
                    @csrf
                    <div class="card-body">
                        <h4 class="card-title mb-4">Change Password</h4>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="current-password" class="form-label">Current Password</label>
                                <input id="current-password" name="current_password" type="password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    placeholder="Current password" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="bg-light p-2 rounded border-start border-3 border-warning small text-muted">
                                    <span class="text-dark fw-medium">Requirements:</span> Min 8 characters with at least one special character.
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="new-password" class="form-label">New Password</label>
                                <input id="new-password" name="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="New password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3 mt-md-0">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password"
                                    class="form-control" placeholder="Confirm password" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4" data-loading-false>
                                Update Security
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

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

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Login History</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle nowrap w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="rounded-start">IP Address</th>
                                    <th>Status</th>
                                    <th>Browser</th>
                                    <th>Platform</th>
                                    <th>Login Time</th>
                                    <th class="rounded-end">Logout Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        {{-- IP --}}
                                        <td>
                                            <code>{{ $log->ip_address }}</code>
                                        </td>
                                        {{-- Status --}}
                                        <td>
                                            @if ($log->is_success)
                                                <span class="badge badge-soft-success">Success</span>
                                            @else
                                                <span class="badge badge-soft-danger">Failed</span>
                                            @endif
                                        </td>

                                        {{-- Browser --}}
                                        <td>
                                            @php
                                                $icon = browserIcon($log->browser);
                                            @endphp

                                            @if ($icon)
                                                <img src="{{ asset('assets/images/browsers/' . $icon) }}"
                                                    alt="{{ $log->browser }}" title="{{ $log->browser }}"
                                                    width="22" data-bs-toggle="tooltip">
                                            @else
                                                <span class="text-muted">
                                                    {{ $log->browser ?? '—' }}
                                                </span>
                                            @endif
                                        </td>
                                        {{-- Platform --}}
                                        <td>
                                            @php
                                                $icon = platformIcon($log->platform);
                                            @endphp

                                            @if ($icon)
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="{{ asset('assets/images/platforms/' . $icon) }}"
                                                        alt="{{ $log->platform }}" title="{{ $log->platform }}"
                                                        width="22" data-bs-toggle="tooltip">
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    {{ $log->platform ?? '—' }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Login --}}
                                        <td>
                                            {{ optional($log->logged_in_at)->format('d M Y, h:i A') }}
                                        </td>


                                        {{-- Logout --}}
                                        <td>
                                            @if ($log->logged_out_at)
                                                {{ $log->logged_out_at->format('d M Y, h:i A') }}
                                            @elseif ($log->session_id == session()->getId())
                                                <span class="badge bg-success">Current</span>
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            No login history found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $logs->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
