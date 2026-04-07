<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('page_title', 'Authentication') | Itrend</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    {{--
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}"> --}}

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet">

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

    <style>
        html,
        body {
            min-height: 100%;
        }

        .auth-wrapper {
            min-height: 100vh;
        }

        .auth-full-page-content .form-control,
        .auth-full-page-content .btn {
            min-height: 44px;
        }

        .auth-full-page-content .form-control {
            border-radius: 0.375rem;
        }

        .auth-full-page-content .btn {
            font-weight: 600;
        }

        .auth-review-item {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 0.5rem 1rem;
        }

        @media (max-width: 1199.98px) {
            .auth-wrapper {
                min-height: auto;
            }

            .auth-full-page-content {
                min-height: 100vh;
            }
        }
    </style>
</head>

<body>
    {{-- error component to show success and error toster --}}
    @include('components.admin.error')

    <div class="container-fluid p-0 auth-wrapper">
        <div class="row g-0 min-vh-100">
            <div class="col-xl-9 d-none d-xl-block">
                <div class="auth-full-bg pt-lg-5 p-4 h-100">
                    <div class="w-100 h-100">
                        <div class="bg-overlay"></div>
                        <div class="d-flex h-100 flex-column">
                            <div class="p-4 mt-auto">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="text-center text-white">
                                            <h4 class="mb-3">
                                                <i class="bx bxs-quote-alt-left text-primary h1 align-middle me-2"></i>
                                                <span class="text-primary">Amazon SP</span> Ads Growth Platform
                                            </h4>
                                            <div class="auth-review-item py-3">
                                                <p class="font-size-16 mb-4">
                                                    " Monitor Sponsored Products performance, optimize campaigns, and
                                                    boost
                                                    sales with one smart Amazon ads dashboard. "
                                                </p>
                                                <div>
                                                    <h4 class="font-size-16 text-primary mb-1">ES4 Solutions Team</h4>
                                                    <p class="font-size-14 mb-0">- Amazon Ads Dashboard</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-12">
                <div class="auth-full-page-content p-md-5 p-4 h-100">
                    <div class="w-100 h-100">
                        <div class="d-flex flex-column h-100">
                            <div class="mb-4 mb-md-5">
                                <a href="{{ route('login') }}" class="d-block card-logo">
                                    {{-- <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="18"
                                        class="card-logo-dark"> --}}
                                    {{-- <img src="{{ asset('assets/images/es4-logo.png') }}" alt="" height="18"
                                        class="card-logo-light"> --}}
                                </a>
                            </div>

                            <div class="my-auto">
                                <div>
                                    <h5 class="text-primary">@yield('auth_page_title', 'Welcome Back !')</h5>
                                    <p class="text-muted mb-0">
                                        @yield('auth_page_subtitle', 'Continue to ES4 Solutions.')</p>
                                </div>

                                <div class="mt-4">
                                    @yield('auth')
                                </div>
                            </div>

                            <div class="mt-4 mt-md-5 text-center">
                                <p class="mb-0">&copy; {{ date('Y') }} ES4 Solutions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // toaster message time out
        $(document).ready(function () {
            setTimeout(function () {
                $("#flash-message").fadeOut("slow");
            }, 4000);
        });

        function btnLoad(btn, text = 'Processing...', disable = true) {
            if (disable) {
                if (btn.disabled) return;
                btn.disabled = true;
            }

            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;

            if (disable && btn.form) {
                btn.form.submit();
            }
        }
    </script>
</body>

</html>