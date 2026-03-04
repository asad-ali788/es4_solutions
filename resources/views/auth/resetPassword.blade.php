@extends('layouts.auth')

@section('auth')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card overflow-hidden">
                <!-- Header / Banner -->
                <div class="bg-primary-subtle">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-4">
                                <h5 class="text-primary">Create Password</h5>
                                <p>Secure your account with ITrend.</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="card-body pt-0">
                    <!-- Brand -->
                    <div class="auth-logo">
                        <a href="#" class="auth-logo-light">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span class="avatar-title rounded-circle bg-light">
                                    <img src="{{ asset('assets/images/logo-itrend-solution.png') }}" alt="ITrend Logo"
                                        class="rounded-circle" height="34">
                                </span>
                            </div>
                        </a>

                        <a href="#" class="auth-logo-dark">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span class="avatar-title rounded-circle bg-light" style="background-color:#f8f9fa;">
                                    <img src="{{ asset('assets/images/favicon.png') }}" alt="ITrend Icon"
                                        class="rounded-circle" height="34">
                                </span>
                            </div>
                        </a>
                    </div>

                    <div class="p-2">
                        @if (session('status'))
                            <div class="alert alert-success text-center">{{ session('status') }}</div>
                        @endif

                        @if (session('info'))
                            <div class="alert alert-info text-center">{{ session('info') }}</div>
                        @endif

                        <!-- Server-side errors -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form id="changePasswordForm" class="form-horizontal" action="{{ route('reset.password.update') }}"
                            method="POST" novalidate>
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ request('email', old('email')) }}">

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" required
                                        minlength="8" placeholder="Enter new password" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary toggle-visibility" type="button"
                                        data-target="#password" aria-label="Show/Hide">
                                        <i class="mdi mdi-eye-off"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 8 characters. Use letters, numbers & symbols for strength.
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-control" required minlength="8" placeholder="Re-enter new password"
                                        autocomplete="new-password">
                                    <button class="btn btn-outline-secondary toggle-visibility" type="button"
                                        data-target="#password_confirmation" aria-label="Show/Hide">
                                        <i class="mdi mdi-eye-off"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-3 d-grid">
                                <button class="btn btn-success waves-effect waves-light" type="submit"
                                    onclick="btnLoad(this, 'Changing..')">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-5 text-center">
                <p>Know your password now?
                    <a href="{{ route('login') }}" class="fw-medium text-primary">Sign in</a>
                </p>
                <p>© {{ date('Y') }} Made with <i class="mdi mdi-heart text-danger"></i> and built by iTrends.</p>
            </div>
        </div>
    </div>

    <script>
        // Show/Hide password toggles
        document.querySelectorAll('.toggle-visibility').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = document.querySelector(this.dataset.target);
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('mdi-eye-off', 'mdi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.replace('mdi-eye', 'mdi-eye-off');
                }
            });
        });

        // jQuery Validate rules
        $(document).ready(function() {
            $("#changePasswordForm").validate({
                rules: {
                    password: {
                        required: true,
                        minlength: 8
                    },
                    password_confirmation: {
                        required: true,
                        minlength: 8,
                        equalTo: "#password"
                    }
                },
                messages: {
                    password: {
                        required: "Please enter your new password",
                        minlength: "Password must be at least 8 characters long"
                    },
                    password_confirmation: {
                        required: "Please confirm your new password",
                        minlength: "Password must be at least 8 characters long",
                        equalTo: "Passwords do not match"
                    }
                },
                errorElement: "div",
                errorClass: "invalid-feedback",
                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },
                errorPlacement: function(error, element) {
                    if (element.parent(".input-group").length) {
                        error.insertAfter(element.parent());
                    } else {
                        error.insertAfter(element);
                    }
                }
            });
        });
    </script>
@endsection
