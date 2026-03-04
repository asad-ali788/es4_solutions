<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Itrends</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>

</head>

<body data-sidebar="dark">
    <div class="account-pages">
        @yield('errors')
    </div>
    <!-- Core JS Libraries -->

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!-- UI Enhancement Plugins (after jQuery) -->
    <script src="{{ asset('assets/js/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/waves.min.js') }}"></script>
    <!-- Form Validation Plugin -->
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <!-- Main App Script (after dependencies) -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>
