@extends('layouts.auth')
@section('page_title', 'Verify OTP')
@section('auth_page_title', 'Verify OTP')
@section('auth_page_subtitle', 'Enter the 6-character code sent to your email.')

@section('auth')
    {{-- <img src="{{ asset('assets/images/auth/verify.svg') }}" alt="" class="img-fluid"> --}}

    @if (session('status'))
        <div class="alert alert-success text-center">{{ session('status') }}</div>
    @endif

    <div class="text-danger text-center fw-bold">
        @error('otp')
            <p>{{ $message }}</p>
        @enderror
    </div>

    <form id="verifyOtpForm" class="form-horizontal" action="{{ route('password.verify.otp') }}" method="POST" novalidate>
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="mb-4">
            <label for="otp" class="form-label fw-semibold">Enter 6-Digit OTP</label>
            <input type="text" class="form-control otp-single-input" id="otp" name="otp" inputmode="text"
                autocomplete="one-time-code" maxlength="6" required>
            <div class="form-text">Use letters A-Z and numbers 0-9.</div>
        </div>

        <div class="mt-3 d-grid">
            <button id="submitBtn" class="btn btn-primary waves-effect waves-light" type="submit"
                onclick="btnLoad(this, 'Verifying OTP..')" disabled>
                Verify OTP
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <p class="mb-0">Didn't receive OTP? <a href="{{ route('password.forgot') }}" class="fw-medium text-primary">Try again</a></p>
    </div>

    <style>
        .otp-single-input {
            letter-spacing: 0.15rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .otp-single-input.is-invalid {
            border-color: #dc3545;
        }
    </style>

    <script>
        (function() {
            const otp = document.getElementById('otp');
            const submitBtn = document.getElementById('submitBtn');

            // Enforce A-Z 0-9 only, uppercase, and max 6
            function sanitize(val) {
                return val.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 6);
            }

            otp.addEventListener('input', function() {
                const cleaned = sanitize(this.value);
                if (this.value !== cleaned) this.value = cleaned;

                // enable button only when length == 6
                submitBtn.disabled = cleaned.length !== 6;
            });

            otp.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text');
                this.value = sanitize(pasted);
                submitBtn.disabled = this.value.length !== 6;
            });

            // jQuery Validate - keep consistent with other pages
            $(document).ready(function() {
                $("#verifyOtpForm").validate({
                    rules: {
                        otp: {
                            required: true,
                            minlength: 6,
                            maxlength: 6,
                            // custom: alphanumeric only
                            pattern: /^[A-Za-z0-9]{6}$/
                        }
                    },
                    messages: {
                        otp: {
                            required: "Please enter your 6-digit OTP",
                            minlength: "OTP must be 6 characters",
                            maxlength: "OTP must be 6 characters",
                            pattern: "Only letters A-Z and numbers 0-9 are allowed"
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
        })();
    </script>
@endsection
