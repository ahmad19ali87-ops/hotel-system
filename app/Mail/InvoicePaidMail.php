<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable
{
    public $invoice;
    public $payment;
    public $pdfPath;

    public function __construct($invoice, $payment, $pdfPath)
    {
        $this->invoice = $invoice;
        $this->payment = $payment;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        return $this->subject('Your Invoice Has Been Paid')
                    ->view('emails.invoice-paid')
                    ->attach($this->pdfPath, [
                        'as' => $this->invoice->invoice_number.'.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}
