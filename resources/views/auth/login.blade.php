@extends('layouts.auth')
@section('page_title', 'Login')
@section('auth_page_title', 'Welcome Back !')
{{-- @section('auth_page_subtitle', 'Sign in to continue to ES4 Solutions.') --}}

@section('auth')
    {{-- <div class="text-muted mb-3" id="login-msg">Sign in to continue to ES4 Solutions.</div> --}}

    {{-- <img src="{{ asset('assets/images/login-page.svg') }}" alt="" class="img-fluid"> --}}

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
            <input type="text" id="email" class="form-control" placeholder="Enter email" name="email"
                value="{{ old('email') }}">
        </div>

        <div class="mb-3">
            <div class="float-end">
                <a href="{{ route('password.forgot') }}" class="text-muted">Forgot password?</a>
            </div>
            <label class="form-label">Password</label>
            <div class="input-group auth-pass-inputgroup">
                <input type="password" class="form-control" name="password" placeholder="Enter password"
                    aria-label="Password" aria-describedby="password-addon">

                <button class="btn btn-light" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
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

    <div class="mt-5 text-center">
        <p class="mb-0">Need help? Contact Developer Team.</p>
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
                                `Hi, ${data.name}!`;
                        } else {
                            document.getElementById('login-msg').innerText = "Sign in to continue to ES4 Solutions.";
                        }

                    });
            }
        });
    </script>
@endsection
