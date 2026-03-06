@extends('layouts.auth')
@section('page_title', 'Change Password')
@section('auth_page_title', 'Change Password')
@section('auth_page_subtitle', 'Set a strong new password for your account.')

@section('auth')
    {{-- <img src="{{ asset('assets/images/auth/change-password.svg') }}" alt="" class="img-fluid"> --}}

    @if (session('status'))
        <div class="alert alert-success text-center">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="changePasswordForm" class="form-horizontal" action="{{ route('password.update', ['token' => $token]) }}"
        method="POST" novalidate>
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" required minlength="8"
                    placeholder="Enter new password">
                <button class="btn btn-outline-secondary toggle-visibility" type="button" data-target="#password"
                    aria-label="Show/Hide">
                    <i class="mdi mdi-eye-off"></i>
                </button>
            </div>
            <div class="form-text">Minimum 8 characters. Use letters, numbers & symbols for strength.</div>
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                    required minlength="8" placeholder="Re-enter new password">
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

    <div class="mt-4 text-center">
        <p class="mb-0">Know your password now? <a href="{{ route('login') }}" class="fw-medium text-primary">Sign in</a></p>
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
