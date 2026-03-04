@extends('errors.error')

@section('errors')
<div class="my-5 pt-5">
    <div class="container">

        {{-- Title --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center mb-5">

                    <h1 class="display-2 fw-medium">
                        4<i class="bx bx-buoy bx-spin text-primary display-3 mx-1"></i>4
                    </h1>

                    <h4 class="text-uppercase mt-3">
                        Page not found
                    </h4>

                    <p class="text-muted mt-2">
                        The page you’re looking for doesn’t exist or may have been moved.
                    </p>

                    <div class="mt-4">
                        @auth
                            <a class="btn btn-primary waves-effect waves-light"
                               href="{{ route('admin.dashboard') }}">
                                Back to Dashboard
                            </a>
                        @else
                            <a class="btn btn-primary waves-effect waves-light"
                               href="{{ route('login') }}">
                                Go to Login
                            </a>
                        @endauth
                    </div>

                </div>
            </div>
        </div>

        {{-- Illustration --}}
        <div class="row justify-content-center">
            <div class="col-md-8 col-xl-6 text-center">
                <img src="{{ asset('assets/images/error-img.png') }}"
                     alt="Page not found"
                     class="img-fluid">
            </div>
        </div>
    </div>
</div>
@endsection
