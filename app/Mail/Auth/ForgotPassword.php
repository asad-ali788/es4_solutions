<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public $user;

    public function __construct($user, string $otp)
    {
        $this->user = $user;
        $this->otp  = $otp;
    }

    public function build()
    {
        return $this->subject('Your Password Reset OTP')
            ->view('emails.auth.password_otp');
    }
}
