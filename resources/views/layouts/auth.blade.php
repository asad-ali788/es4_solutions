<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Login | Itrend</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet">


    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

    <style>
        :root {
            --auth-card-radius: 18px;
            --auth-shadow: 0 18px 40px rgba(30, 41, 59, .14);
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            background-repeat: no-repeat;
            background-position: top center;
            background-size: cover;
background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.dev/svgjs' width='1440' height='560' preserveAspectRatio='none' viewBox='0 0 1440 560'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1015%26quot%3b)' fill='none'%3e%3crect width='1440' height='560' x='0' y='0' fill='rgba(179%2c 194%2c 253%2c 1)'%3e%3c/rect%3e%3cpath d='M0%2c382.686C71.774%2c375.189%2c141.405%2c353.2%2c199.318%2c310.145C254.846%2c268.863%2c279.611%2c202.599%2c318.868%2c145.622C367.787%2c74.623%2c459.339%2c20.304%2c458.423%2c-65.911C457.512%2c-151.568%2c369.449%2c-206.798%2c314.424%2c-272.45C258.394%2c-339.301%2c218.19%2c-433.956%2c133.267%2c-453.866C48.485%2c-473.743%2c-25.641%2c-396.416%2c-109.158%2c-371.759C-192.507%2c-347.151%2c-300.459%2c-375.338%2c-356.81%2c-309.177C-413.203%2c-242.966%2c-375.036%2c-141.406%2c-379.34%2c-54.541C-383.186%2c23.08%2c-407.696%2c100.988%2c-380.746%2c173.881C-352.812%2c249.433%2c-298.631%2c316.047%2c-228.004%2c354.78C-159.481%2c392.359%2c-77.728%2c390.805%2c0%2c382.686' fill='%238da4fc'%3e%3c/path%3e%3cpath d='M1440 1072.1599999999999C1540.182 1061.31 1635.208 1045.6979999999999 1729.779 1010.904 1848.287 967.304 1984.962 944.383 2061.105 843.649 2142.128 736.4590000000001 2189.319 585.97 2147.499 458.277 2106.138 331.985 1963.971 274.181 1851.4470000000001 203.48000000000002 1766.404 150.04700000000003 1672.862 123.137 1575.412 98.827 1482.001 75.52499999999998 1390.794 63.817999999999984 1294.523 64.55000000000001 1164.2350000000001 65.541 997.55 4.2309999999999945 913.586 103.86000000000001 829.24 203.94299999999998 930.613 357.11199999999997 937.8240000000001 487.798 943.3679999999999 588.277 923.385 686.537 951.077 783.284 982.318 892.429 1009.1220000000001 1020.62 1106.864 1078.37 1204.462 1136.0349999999999 1327.298 1084.366 1440 1072.1599999999999' fill='%23d9e0fe'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1015'%3e%3crect width='1440' height='560' fill='white'%3e%3c/rect%3e%3c/mask%3e%3c/defs%3e%3c/svg%3e");        }

        .account-pages {
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .account-pages .container {
            width: 100%;
        }

        .account-pages .container>.row.justify-content-center {
            min-height: calc(100vh - 6rem);
            align-items: center;
        }

        .account-pages .container>.row.justify-content-center .card {
            border-radius: var(--auth-card-radius);
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: var(--auth-shadow);
            overflow: hidden;
        }

        .account-pages .container>.row.justify-content-center .card .card-body {
            padding-bottom: 1.25rem;
        }

        .account-pages .form-control,
        .account-pages .form-select,
        .account-pages .btn {
            min-height: 44px;
        }

        .account-pages .form-control,
        .account-pages .form-select {
            border-radius: 10px;
        }

        .account-pages .btn {
            border-radius: 10px;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            .account-pages {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                min-height: 100dvh;
                align-items: stretch;
            }

            .account-pages .container {
                max-width: 100%;
                padding-left: 0;
                padding-right: 0;
            }

            .account-pages .container>.row.justify-content-center {
                min-height: 100dvh;
                margin: 0;
                align-items: stretch;
            }

            .account-pages .container>.row.justify-content-center>[class*='col-'] {
                flex: 0 0 100%;
                max-width: 100%;
                padding-left: 0;
                padding-right: 0;
            }

            .account-pages .container>.row.justify-content-center .card {
                min-height: 100dvh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .account-pages .container>.row.justify-content-center .card .card-body {
                padding: 1rem 1rem 1.25rem;
            }

            .account-pages .container>.row.justify-content-center .card .p-2 {
                padding: .25rem !important;
            }

            .account-pages .container>.row.justify-content-center .bg-primary-subtle img {
                width: 100% !important;
                height: auto !important;
                max-height: 123px;
                object-fit: contain;
            }

            .account-pages .container>.row.justify-content-center .bg-primary-subtle .text-primary {
                padding: 1rem !important;
            }

            .account-pages .container>.row.justify-content-center .auth-logo {
                margin-bottom: .25rem;
            }

            .account-pages .container>.row.justify-content-center>.text-center.mt-5,
            .account-pages .container>.row.justify-content-center>[class*='col-']>.text-center.mt-5 {
                margin-top: 1rem !important;
                padding: 0 1rem calc(env(safe-area-inset-bottom, 0px) + .75rem);
            }

            .account-pages .form-control,
            .account-pages .form-select,
            .account-pages .btn {
                min-height: 48px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="account-pages py-5 pt-sm-5">
        {{-- error component to show success and error toster --}}
        @include('components.admin.error')
        <div class="container">
            @yield('auth')
        </div>
    </div>
    <script>
        // toaster message time out
        $(document).ready(function() {
            setTimeout(function() {
                $("#flash-message").fadeOut("slow");
            }, 4000); // 4 seconds
        });

        function btnLoad(btn, text = 'Processing...', disable = true) {
            if (disable) {
                if (btn.disabled) return;
                btn.disabled = true;
            }

            btn.innerHTML =
                `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;

            if (disable && btn.form) {
                btn.form.submit();
            }
        }
    </script>
    
</body>

</html>
