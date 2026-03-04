@extends('errors.error')
@section('errors')
    <div class="my-5 pt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="home-wrapper">
                        <div class="row justify-content-center">
                            <div class="col-sm-4">
                                <div class="maintenance-img">
                                    <img src="{{ asset('assets/images/maintenance.svg') }}" alt=""
                                        class="img-fluid mx-auto d-block">
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-5">Site is Under Maintenance</h3>
                        <p>Please check back in sometime.</p>

                        <div class="row">
                            <div class="col-md-4 d-flex align-items-stretch">
                                <div class="card mt-4 maintenance-box h-100 w-100">
                                    <div class="card-body text-center">
                                        <i class="bx bx-broadcast mb-4 h1 text-primary"></i>
                                        <h5 class="font-size-15 text-uppercase">Why is the Site Down?</h5>
                                        <p class="text-muted mb-0">
                                            We're currently performing essential system updates and improvements to enhance
                                            your
                                            experience. This is a scheduled maintenance window.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-stretch">
                                <div class="card mt-4 maintenance-box h-100 w-100">
                                    <div class="card-body text-center">
                                        <i class="bx bx-time-five mb-4 h1 text-primary"></i>
                                        <h5 class="font-size-15 text-uppercase">Estimated Downtime</h5>
                                        <p class="text-muted mb-0">
                                            We're currently undergoing scheduled maintenance to ensure optimal performance
                                            and
                                            stability.
                                            We appreciate your patience and understanding while we work to make things
                                            better.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-stretch">
                                <div class="card mt-4 maintenance-box h-100 w-100">
                                    <div class="card-body text-center">
                                        <i class="bx bx-envelope mb-4 h1 text-primary"></i>
                                        <h5 class="font-size-15 text-uppercase">Need Assistance?</h5>
                                        <p class="text-muted mb-0">
                                            If you have any questions or concerns, please reach out to the development team.
                                            We'll help you out as soon as possible.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- end row -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
