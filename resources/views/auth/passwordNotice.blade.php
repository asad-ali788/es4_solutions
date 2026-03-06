@extends('layouts.auth')
@section('page_title', 'Reset Link Sent')
@section('auth_page_title', 'Reset Link Sent')
@section('auth_page_subtitle', 'We have emailed you a secure password reset link.')

@section('auth')
    {{-- <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid"> --}}

    <div class="text-center">
        <h5 class="mb-3">Check your inbox</h5>
        <p class="text-muted">
            We have sent a password reset link to <strong>{{ $email }}</strong>.<br>
            Please check your inbox. If you do not see it, check your <strong>Spam</strong> or
            <strong>Junk</strong> folder.<br><br>
            Didn't receive it? Contact Developer Team.
        </p>
    </div>

    <div class="mt-4 d-grid">
        <a href="{{ route('login') }}" class="btn btn-success waves-effect waves-light">
            Back to Login
        </a>
    </div>
@endsection
