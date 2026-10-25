<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestCheckInMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, array $data = [])
    {
        $this->booking = $booking;
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $fromAddress = config('mail.from.address') ?: 'info@ahmadinsights.online';
        $siteName = config('app.name', 'بوابة حجز الفنادق الذكية');
        $hotelName = $this->booking->hotel?->name ?: $siteName;

        $subject = "🔑 أهلاً بك في فندق {$hotelName} - تم تسجيل وصولك بنجاح (حجز #{$this->booking->id})";

        return $this->from($fromAddress, $siteName . ' - ' . $hotelName)
                    ->subject($subject)
                    ->view('emails.guest-checkin');
    }
}

