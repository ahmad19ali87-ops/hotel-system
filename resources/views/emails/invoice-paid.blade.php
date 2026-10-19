<!DOCTYPE html>
<html>
<body style="font-family: Arial;">

<h2>Invoice Paid Successfully</h2>

<p>Dear {{ $invoice->booking->guest->name }},</p>

<p>Your payment has been received successfully.</p>

<p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
<p><strong>Amount:</strong> {{ number_format($invoice->total, 2) }} SAR</p>
<p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
<p><strong>Payment Method:</strong> {{ strtoupper($payment->payment_method) }}</p>

<p>Thank you for choosing our hotel.</p>

</body>
</html>
