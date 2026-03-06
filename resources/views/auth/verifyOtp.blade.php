@extends('layouts.auth')
@section('auth')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card overflow-hidden">
                <div class="bg-primary-subtle">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-4">
                                <h5 class="text-primary"> Verify OTP</h5>
                                <p>Verify your identity with ITrend.</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/auth/verify.svg') }}" alt="" class="img-fluid"
                                style="width: 1080px; height:123px !important">
                        </div>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div class="auth-logo">
                        <a href="#">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span
                                    class="avatar-title rounded-circle bg-light p-0 overflow-hidden d-flex align-items-center justify-content-center">
                                    {{-- <img src="{{ asset('assets/images/logo-sm.png') }}" alt="Logo" class="w-100 h-100"
                                        style="object-fit: cover;transform: scale(2.3);padding-left: 2px;padding-bottom: 3px;"> --}}
                                </span>
                            </div>
                        </a>
                    </div>

                    <div class="p-2">
                        @if (session('status'))
                            <div class="alert alert-success text-center">{{ session('status') }}</div>
                        @endif

                        <div class="text-danger text-center fw-bold">
                            @error('otp')
                                <p>{{ $message }}</p>
                            @enderror
                        </div>

                        <form id="verifyOtpForm" class="form-horizontal" action="{{ route('password.verify.otp') }}"
                            method="POST" novalidate>
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">

                            <div class="mb-4">
                                <label for="otp" class="form-label fw-semibold">Enter 6-Digit OTP</label>
                                <input type="text" class="form-control otp-single-input" id="otp" name="otp"
                                    inputmode="text" autocomplete="one-time-code" maxlength="6" required>
                                <div class="form-text">Use letters A–Z and numbers 0–9.</div>
                            </div>

                            <div class="mt-3 d-grid">
                                <button id="submitBtn" class="btn btn-primary waves-effect waves-light" type="submit"
                                    onclick="btnLoad(this, 'Verifying OTP..')" disabled>
                                    Verify OTP
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-5 text-center">
                <p>Didn’t receive OTP? <a href="{{ route('password.forgot') }}" class="fw-medium text-primary">Try again</a>
                </p>
                <p>© {{ date('Y') }} Made with <i class="mdi mdi-heart text-danger"></i> and built by ES4 Solutions.</p>
            </div>
        </div>
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

            // Enforce A–Z 0–9 only, uppercase, and max 6
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

            // jQuery Validate – keep consistent with other pages
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
                            pattern: "Only letters A–Z and numbers 0–9 are allowed"
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
