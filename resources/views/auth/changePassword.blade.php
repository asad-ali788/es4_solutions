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
                                <h5 class="text-primary"> Change Password</h5>
                                <p>Secure your account with ES4 Solutions.</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/auth/change-password.svg') }}" alt="" class="img-fluid"
                                style="width: 1080px; height:123px !important">
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="card-body pt-0">
                    <!-- Brand -->
                    <div class="auth-logo">
                        <a href="#">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span
                                    class="avatar-title rounded-circle bg-light p-0 overflow-hidden d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/images/logo-sm.png') }}" alt="Logo" class="w-100 h-100"
                                        style="object-fit: cover;transform: scale(2.3);padding-left: 2px;padding-bottom: 3px;">
                                </span>
                            </div>
                        </a>
                    </div>

                    <div class="p-2">
                        @if (session('status'))
                            <div class="alert alert-success text-center">{{ session('status') }}</div>
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

                        <form id="changePasswordForm" class="form-horizontal"
                            action="{{ route('password.update', ['token' => $token]) }}" method="POST" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" required
                                        minlength="8" placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary toggle-visibility" type="button"
                                        data-target="#password" aria-label="Show/Hide">
                                        <i class="mdi mdi-eye-off"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 8 characters. Use letters, numbers & symbols for strength.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-control" required minlength="8" placeholder="Re-enter new password">
                                    <button class="btn btn-outline-secondary toggle-visibility" type="button"
                                        data-target="#password_confirmation" aria-label="Show/Hide">
                                        <i class="mdi mdi-eye-off"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 d-grid">
                                <button class="btn btn-primary waves-effect waves-light" type="submit"
                                    onclick="btnLoad(this, 'Changing Password..')">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-5 text-center">
                <p>Know your password now? <a href="{{ route('login') }}" class="fw-medium text-primary">Sign in</a></p>
                <p>© {{ date('Y') }} Made with <i class="mdi mdi-heart text-danger"></i> and built by ES4 Solutions.</p>
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
                    icon.classList.remove('mdi-eye-off');
                    icon.classList.add('mdi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('mdi-eye');
                    icon.classList.add('mdi-eye-off');
                }
            });
        });

        // jQuery Validate to mirror your other pages
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
