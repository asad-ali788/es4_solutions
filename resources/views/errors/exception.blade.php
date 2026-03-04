@extends('errors.error')

@section('errors')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center">

                    <div class="row justify-content-center">
                        <div class="col-sm-4">
                            <div class="maintenance-img">
                                <img src="{{ asset('assets/images/exception.svg') }}" alt="Dashboard error"
                                    class="img-fluid mx-auto d-block">
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-3">We’re having trouble loading this dashboard</h4>

                    <p class="text-muted">
                        Some data couldn’t be loaded at the moment.
                        Please refresh the page to try again.
                        If the issue persists, it will be resolved shortly.
                    </p>

                    <div class="mt-3">
                        <button onclick="location.reload()" class="btn btn-outline-secondary">
                            Refresh Page
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
