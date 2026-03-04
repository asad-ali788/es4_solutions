@extends('layouts.auth')
@section('auth')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card overflow-hidden">
                <div class="bg-primary-subtle">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-4">
                                <h5 class="text-primary"> Reset Password</h5>
                                <p>Reset Password with ITrend.</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/auth/forgot.svg') }}" alt="" class="img-fluid"
                                style="width: 1080px; height:123px !important">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="auth-logo">
                        <a href="#">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span
                                    class="avatar-title rounded-circle bg-light p-0 overflow-hidden d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/images/logo-sm.png') }}" alt="Logo" class="w-100 h-100"
                                        style="object-fit: cover;transform: scale(2.3);padding-left: 2px;padding-bottom: 3px;">
                                </span>
                            </div>
                        </a>
                    </div>

                    <div class="p-2">
                        <span style="margin-top:10px ">
                            <div class="text-danger text-center fw-bold">
                                @error('email')
                                    <p>{{ $message }}</p>
                                @enderror
                            </div>
                        </span>
                        <form id="resetPasswordForm" class="form-horizontal" action="{{ route('verifyEmail') }}"
                            method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" class="form-control" id="email" placeholder="Enter email"
                                    name="email" value="{{ old('email') }}">
                            </div>

                            <div class="form-check">

                            </div>

                            <div class="mt-3 d-grid">
                                <button class="btn btn-primary waves-effect waves-light" type="submit"
                                    onclick="btnLoad(this, 'Verifying..')">Verify</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="mt-5 text-center">
                <p>Remember It ? <a href="{{ route('login') }}" class="fw-medium text-primary"> Sign In here</a> </p>
                <p>© {{ date('Y') }} Made with <i class="mdi mdi-heart text-danger"></i> and built by iTrends.
                </p>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#resetPasswordForm").validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    }
                },
                messages: {
                    email: {
                        required: "Please enter your email address",
                        email: "Please enter a valid email address"
                    }
                },
                errorElement: "div",
                errorClass: "invalid-feedback",
                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },
                errorPlacement: function(error, element) {
                    if (element.parent(".input-group").length) {
                        error.insertAfter(element.parent());
                    } else {
                        error.insertAfter(element);
                    }
                }
            });
        });
    </script>
@endsection
