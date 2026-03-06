<!doctype html>
<html lang="en">
{{-- data-bs-theme="dark" --}}

<head>

    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ES4 Solutions</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#37288f">
    @php($v = '1.33')
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('assets/css/common.css') }}?v={{ $v }}">
    @stack('style')
    @livewireStyles
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <meta name="img-fallback" content="{{ asset('storage/images/avatar-broken.png') }}">

</head>

{{-- data-topbar="dark" --}}

<body data-sidebar="light" class="relative">
    <!-- Loading Spinner -->
    @include('components.loading')

    {{-- error component to show success and error toster --}}
    @include('components.admin.error')

    <div id="layout-wrapper">

        <!-- Header -->
        @include('components.admin.header')

        <!-- Left Sidebar -->
        @include('components.admin.sidebar')
        <!-- ============================================================== -->

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Content loads here -->
                    @yield('content')
                    {{ $slot ?? '' }}
                    <!-- Content loads here -->
                </div>
            </div>

            <!-- footer -->
            @include('components.admin.footer')
        </div>
        <!-- ============================================================== -->
    </div>

    <div class="rightbar-overlay"></div>
    <!-- Core JS Libraries -->

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

    <!-- UI Enhancement Plugins (after jQuery) -->
    <script src="{{ asset('assets/js/common.js') }}?v={{ $v }}"></script>
    <script src="{{ asset('assets/js/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/waves.min.js') }}"></script>

    <!-- Form Validation Plugin -->
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/validationRules.js') }}"></script>
    <!-- Main App Script (after dependencies) -->
    <script src="{{ asset('assets/js/app.js') }}?v={{ $v }}"></script>
    @livewireScripts
    @stack('scripts')
    <!-- Back to Top Button -->
</body>

</html>
