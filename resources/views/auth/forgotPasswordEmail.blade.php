@extends('layouts.auth')
@section('page_title', 'Forgot Password')
@section('auth_page_title', 'Reset Password')
@section('auth_page_subtitle', 'Enter your email and we will verify your account.')

@section('auth')
    {{-- <img src="{{ asset('assets/images/auth/forgot.svg') }}" alt="" class="img-fluid"> --}}

    <span style="margin-top:10px ">
        <div class="text-danger text-center fw-bold">
            @error('email')
                <p>{{ $message }}</p>
            @enderror
        </div>
    </span>

    <form id="resetPasswordForm" class="form-horizontal" action="{{ route('verifyEmail') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" class="form-control" id="email" placeholder="Enter email" name="email"
                value="{{ old('email') }}">
        </div>

        <div class="mt-3 d-grid">
            <button class="btn btn-primary waves-effect waves-light" type="submit" onclick="btnLoad(this, 'Verifying..')">
                Verify
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <p class="mb-0">Remember it? <a href="{{ route('login') }}" class="fw-medium text-primary">Sign In here</a></p>
    </div>

    <script>
        $(document).ready(function() {
            $("#resetPasswordForm").validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    }
                },
                messages: {
                    email: {
                        required: "Please enter your email address",
                        email: "Please enter a valid email address"
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
