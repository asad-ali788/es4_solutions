@extends('errors.error')

@section('errors')
    <div class="my-5 pt-5">
        <div class="container">

            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mb-5">

                        <h1 class="display-2 fw-medium">
                            4<i class="bx bx-buoy bx-spin text-primary display-3 mx-1"></i>1
                        </h1>

                        <h4 class="text-uppercase mt-3">Unauthorized</h4>

                        <p class="text-muted mt-2">
                            You’re not authorized to access this page. Please sign in with an account that has permission.
                        </p>

                        <div class="mt-4 text-center">
                            <a class="btn btn-primary waves-effect waves-light" href="{{ route('admin.dashboard') }}">
                                Back to Dashboard
                            </a>

                            {{-- Optional: if you have a login route --}}
                            {{-- <a class="btn btn-outline-secondary waves-effect ms-2" href="{{ route('login') }}">Sign In</a> --}}
                        </div>

                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-xl-6 text-center">
                    <img src="{{ asset('assets/images/error-img.png') }}" alt="Unauthorized" class="img-fluid">
                </div>
            </div>

        </div>
    </div>
@endsection
