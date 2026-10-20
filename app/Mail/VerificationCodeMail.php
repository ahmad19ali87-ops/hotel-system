<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public string $code;

    /**
     * Create a new message instance.
     */
    public function __construct($user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $fromAddress = config('mail.from.address') ?: 'info@ahmadinsights.online';
        $fromName = config('mail.from.name') ?: 'بوابة حجز الفنادق الذكية';

        return $this->from($fromAddress, $fromName)
                    ->subject('كود تفعيل حسابك - بوابة حجز الفنادق الذكية')
                    ->view('emails.verification-code');
    }
}

