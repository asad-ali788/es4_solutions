@extends('errors.error')

@section('errors')
    <div class="my-5 pt-5">
        <div class="container">

            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mb-5">

                        <h1 class="display-2 fw-medium">
                            4<i class="bx bx-buoy bx-spin text-primary display-3 mx-1"></i>2
                        </h1>

                        <h4 class="text-uppercase mt-3">
                            Payment required
                        </h4>

                        <p class="text-muted mt-2">
                            Your account requires an active subscription or payment to access this feature.
                        </p>

                        <div class="mt-4 text-center">
                            <a class="btn btn-primary waves-effect waves-light" href="{{ route('admin.dashboard') }}">
                                Back to Dashboard
                            </a>

                            {{-- Optional: billing link --}}
                            {{-- <a class="btn btn-outline-secondary ms-2" href="{{ route('billing.index') }}">Manage Billing</a> --}}
                        </div>

                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-xl-6 text-center">
                    <img src="{{ asset('assets/images/error-img.png') }}" alt="Payment required" class="img-fluid">
                </div>
            </div>

        </div>
    </div>
@endsection
