@extends('layouts.auth')

@section('auth')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card overflow-hidden">
            <!-- Header / Banner -->
            <div class="bg-primary-subtle">
                <div class="row">
                    <div class="col-7">
                        <div class="text-primary p-4">
                            <h5 class="text-primary">Reset Link Sent</h5>
                            <p>We’ve emailed you a secure password reset link.</p>
                        </div>
                    </div>
                    <div class="col-5 align-self-end">
                        <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid">
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="card-body pt-0 text-center">
                <div class="p-3">
                    <h5 class="mb-3">Check your inbox</h5>
                    <p class="text-muted">
                        We’ve sent a password reset link to <strong>{{ $email }}</strong>.<br>
                        Please check your inbox — if you don’t see it, look in your
                        <strong>Spam</strong> or <strong>Junk</strong> folder.<br><br>
                        Didn’t receive it? Contact Developer Team.
                    </p>
                </div>

                <div class="mt-4 d-grid">
                    <a href="{{ route('login') }}" class="btn btn-success waves-effect waves-light">
                        Back to Login
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-5 text-center">
            <p>© {{ date('Y') }} Made with <i class="mdi mdi-heart text-danger"></i> by ITrend Solution.</p>
        </div>
    </div>
</div>
@endsection
