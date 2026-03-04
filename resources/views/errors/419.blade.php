@extends('errors.error')

@section('errors')
    <div class="my-5 pt-5">
        <div class="container">

            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mb-5">

                        <h1 class="display-2 fw-medium">
                            419
                        </h1>

                        <h4 class="text-uppercase mt-3">
                            Page expired
                        </h4>

                        <p class="text-muted mt-2">
                            Your session has expired or this page was open for too long.
                            Please refresh the page and try again.
                        </p>

                        <div class="mt-4 text-center">
                            <button onclick="location.reload()" class="btn btn-outline-secondary me-2">
                                Refresh Page
                            </button>

                            <a class="btn btn-primary waves-effect waves-light" href="{{ route('admin.dashboard') }}">
                                Back to Dashboard
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-xl-6 text-center">
                    <img src="{{ asset('assets/images/error-img.png') }}" alt="Page expired" class="img-fluid">
                </div>
            </div>

        </div>
    </div>
@endsection
