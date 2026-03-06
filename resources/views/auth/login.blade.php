@extends('layouts.auth')
@section('auth')
    {{-- <script type='text/javascript'>document.addEventListener('DOMContentLoaded', function () {window.setTimeout(document.querySelector('svg').classList.add('animated'),1000);})</script> --}}
    <div class="row justify-content-center align-items-center" style="min-height: calc(100vh - 6rem);">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card overflow-hidden">
                <div class="bg-primary-subtle">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-4">
                                <h5 class="text-primary">Welcome Back !</h5>
                                <h6 id="login-msg">Sign in to continue to ES4 Solutions.</h6>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/login-page.svg') }}" alt="" class="img-fluid"
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
                                    <img src="{{ asset('assets/images/logo-sm.png') }}" alt="Logo" class="w-100 h-100"
                                        style="object-fit: cover;transform: scale(2.3);padding-left: 2px;padding-bottom: 3px;">
                                </span>
                            </div>
                        </a>
                    </div>

                    <div class="p-2">
                        <span style="margin-top:10px ">
                            <div class="text-danger text-center fw-bold">

                                @error('email')
                                    <p>{{ $message }}</p>
                                @enderror
                            </div>
                        </span>
                        <form id="loginForm" class="form-horizontal" action="{{ route('login') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" id="email" class="form-control" id="email"
                                    placeholder="Enter email" name="email" value="{{ old('email') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group auth-pass-inputgroup">
                                    <input type="password" class="form-control" name="password" placeholder="Enter password"
                                        aria-label="Password" aria-describedby="password-addon">

                                    <button class="btn btn-light " type="button" id="password-addon"><i
                                            class="mdi mdi-eye-outline"></i></button>
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember-check" name="remember">
                                <label class="form-check-label" for="remember-check">
                                    Remember me
                                </label>
                            </div>

                            <div class="mt-3 d-grid">
                                <button type="submit" class="btn btn-primary w-100 waves-effect waves-light"
                                    onclick="btnLoad(this, 'Logging in...')">
                                    Log In
                                </button>
                            </div>


                        </form>
                        <div class="mt-4 text-center">
                            <a href="{{ route('password.forgot') }}" class="text-muted"><i class="mdi mdi-lock me-1"></i>
                                Forgot
                                your password?</a>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="mt-5 text-center">

                <div>
                    <p>© {{ date('Y') }} Made with <i class="mdi mdi-heart text-danger"></i> and built by ES4 Solutions.
                    </p>
                </div>
            </div> --}}
        </div>
    </div>

    <script>
        $(document).ready(function() {
            jQuery.validator.addMethod(
                "regex",
                function(value, element, regexp) {
                    var re = new RegExp(regexp);
                    return this.optional(element) || re.test(value);
                },
                "Invalid format."
            );

            $("#loginForm").validate({
                rules: {
                    email: {
                        required: true,
                        email: true,
                    },
                    password: {
                        required: true,
                        minlength: 8,
                        // regex: "^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).+$",
                    },
                },
                messages: {
                    email: {
                        required: "Please enter your email",
                        email: "Please enter a valid email address",
                    },
                    password: {
                        required: "Please enter your password",
                        minlength: "Password must be at least 8 characters",
                        // regex: "Password must contain at least one uppercase letter, one lowercase letter, and one digit",
                    },
                },
                errorElement: "div",
                errorPlacement: function(error, element) {
                    element.closest(".mb-3").append(error);
                },
                highlight: function(element) {
                    $(element).addClass("error fw-bold");
                },
                unhighlight: function(element) {
                    $(element).removeClass("error fw-bold");
                },
            });
            $('#password-addon').click(function() {
                const $input = $(this).siblings('input[name="password"]');
                const isPassword = $input.attr('type') === 'password';

                $input.attr('type', isPassword ? 'text' : 'password');
                $(this).find('i').toggleClass('mdi-eye-outline mdi-eye-off-outline');
            });

        });

        document.getElementById('email').addEventListener('blur', function() {
            let email = this.value;

            // simple email regex (covers most valid cases)
            let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (emailRegex.test(email)) {
                fetch("{{ route('check.email') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            email: email
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.exists) {
                            document.getElementById('login-msg').innerText =
                                `Hi, ${data.name}! 👋`;
                        } else {
                            document.getElementById('login-msg').innerText = "Sign in to continue to ES4 Solutions.";
                        }

                    });
            }
        });
    </script>
@endsection
