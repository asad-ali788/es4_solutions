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
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.dev/svgjs' width='1440' height='560' preserveAspectRatio='none' viewBox='0 0 1440 560'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1061%26quot%3b)' fill='none'%3e%3crect width='1440' height='560' x='0' y='0' fill='rgba(187%2c 202%2c 254%2c 1)'%3e%3c/rect%3e%3cpath d='M0%2c397.346C75.159%2c395.788%2c140.91%2c357.249%2c208.675%2c324.705C286.003%2c287.568%2c397.324%2c275.176%2c424.091%2c193.676C451.546%2c110.082%2c353.822%2c37.378%2c330.945%2c-47.583C311.861%2c-118.46%2c340.739%2c-199.238%2c301.578%2c-261.319C260.337%2c-326.698%2c185.579%2c-359.732%2c114.652%2c-390.468C35.539%2c-424.752%2c-50.85%2c-478.614%2c-131.886%2c-449.163C-213.031%2c-419.672%2c-240.162%2c-320.353%2c-283.591%2c-245.733C-319.39%2c-184.223%2c-347.428%2c-121.641%2c-362.929%2c-52.181C-379.581%2c22.434%2c-404.329%2c100.759%2c-376.838%2c172.096C-348.818%2c244.808%2c-281.198%2c293.496%2c-214.312%2c333.476C-149.006%2c372.512%2c-76.067%2c398.923%2c0%2c397.346' fill='%2394acfd'%3e%3c/path%3e%3cpath d='M1440 1134.877C1547.29 1125.021 1592.85 992.3430000000001 1668.63 915.755 1726.205 857.566 1784.285 805.591 1831.779 738.92 1889.907 657.321 1994.083 581.512 1977.059 482.783 1959.935 383.47900000000004 1831.608 352.198 1754.009 287.91 1690.785 235.531 1637.963 174.20800000000003 1563.319 140.014 1479.408 101.57400000000001 1387.983 55.31700000000001 1298.8890000000001 79.42000000000002 1209.464 103.613 1166.46 200.85399999999998 1099.972 265.364 1025.866 337.26599999999996 913.048 380.735 885.711 480.305 857.944 581.439 903.564 690.595 958.736 779.7860000000001 1010.2280000000001 863.029 1103.9189999999999 902.28 1182.682 960.394 1267.796 1023.193 1334.669 1144.5529999999999 1440 1134.877' fill='%23e2e9ff'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1061'%3e%3crect width='1440' height='560' fill='white'%3e%3c/rect%3e%3c/mask%3e%3c/defs%3e%3c/svg%3e");
        }

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
