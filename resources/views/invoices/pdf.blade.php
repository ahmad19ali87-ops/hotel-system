<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .title { font-size: 22px; font-weight: bold; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>

    <div class="title">Invoice #{{ $invoice->id }}</div>

    <div class="section">
        <div class="label">Guest:</div>
        {{ $invoice->booking->guest->name }}  
    </div>

    <div class="section">
        <div class="label">Room:</div>
        {{ $invoice->booking->room->name }}  
    </div>

    <div class="section">
        <div class="label">Check-in:</div>
        {{ $invoice->booking->check_in }}
    </div>

    <div class="section">
        <div class="label">Check-out:</div>
        {{ $invoice->booking->check_out }}
    </div>

   <table>
    <tr>
        <th>Description</th>
        <th>Amount</th>
    </tr>

    <tr>
        <td>Room Charges</td>
        <td>{{ number_format($subtotal, 2) }} SAR</td>
    </tr>

    <tr>
        <td>Tax</td>
        <td>{{ number_format($tax, 2) }} SAR</td>
    </tr>

    <tr>
        <td>Discount</td>
        <td>-{{ number_format($discount, 2) }} SAR</td>
    </tr>

    <tr>
        <th>Total</th>
        <th>{{ number_format($total, 2) }} SAR</th>
    </tr>
</table>
<div style="margin-top: 30px; text-align: center;">
    <div style="font-weight: bold; margin-bottom: 10px;">Scan to view invoice</div>
    <img src="data:image/png;base64, {{ $qr }}" alt="QR Code">
</div>


</body>
</html>
