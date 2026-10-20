<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public string $token;
    public string $code;
    public string $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, string $token, string $code)
    {
        $this->user = $user;
        $this->token = $token;
        $this->code = $code;
        $this->resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $fromAddress = config('mail.from.address') ?: 'info@ahmadinsights.online';
        $fromName = config('mail.from.name') ?: 'بوابة حجز الفنادق الذكية';

        return $this->from($fromAddress, $fromName)
                    ->subject('استعادة كلمة المرور - بوابة حجز الفنادق الذكية')
                    ->view('emails.password-reset');
    }
}

