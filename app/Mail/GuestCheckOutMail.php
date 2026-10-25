<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestCheckOutMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public ?Invoice $invoice;
    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, ?Invoice $invoice = null, array $data = [])
    {
        $this->booking = $booking;
        $this->invoice = $invoice;
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
        $invNum = $this->invoice?->invoice_number ?: ($this->invoice ? "#{$this->invoice->id}" : "#{$this->booking->id}");

        $subject = "🚪 شكراً لإقامتك في فندق {$hotelName} - تفاصيل المغادرة والفاتورة ({$invNum})";

        $mail = $this->from($fromAddress, $siteName . ' - ' . $hotelName)
                     ->subject($subject)
                     ->view('emails.guest-checkout');

        // Attach PDF invoice if present on disk
        if (!empty($this->data['pdf_full_path']) && file_exists($this->data['pdf_full_path'])) {
            $fileName = ($this->invoice?->invoice_number ?: "invoice-{$this->booking->id}") . '.pdf';
            $mail->attach($this->data['pdf_full_path'], [
                'as' => $fileName,
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}

