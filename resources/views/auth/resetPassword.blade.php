@extends('layouts.auth')
@section('page_title', 'Create Password')
@section('auth_page_title', 'Create Password')
@section('auth_page_subtitle', 'Secure your account with a new password.')

@section('auth')
    {{-- <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid"> --}}
    {{-- <img src="{{ asset('assets/images/logo-itrend-solution.png') }}" alt="ITrend Logo" class="rounded-circle" height="34"> --}}

    @if (session('status'))
        <div class="alert alert-success text-center">{{ session('status') }}</div>
    @endif

    @if (session('info'))
        <div class="alert alert-info text-center">{{ session('info') }}</div>
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

    <form id="changePasswordForm" class="form-horizontal" action="{{ route('reset.password.update') }}" method="POST"
        novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ request('email', old('email')) }}">

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" required minlength="8"
                    placeholder="Enter new password" autocomplete="new-password">
                <button class="btn btn-outline-secondary toggle-visibility" type="button" data-target="#password"
                    aria-label="Show/Hide">
                    <i class="mdi mdi-eye-off"></i>
                </button>
            </div>
            <div class="form-text">Minimum 8 characters. Use letters, numbers & symbols for strength.</div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                    required minlength="8" placeholder="Re-enter new password" autocomplete="new-password">
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
            <button class="btn btn-success waves-effect waves-light" type="submit" onclick="btnLoad(this, 'Changing..')">
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
