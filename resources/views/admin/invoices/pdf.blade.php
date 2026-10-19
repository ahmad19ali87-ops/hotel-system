<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; color: #20252b; font-size: 12px; direction: rtl; }
        .header { border-bottom: 2px solid #174c77; padding-bottom: 14px; margin-bottom: 20px; }
        .logo { max-width: 90px; max-height: 70px; }
        .muted { color: #66717c; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d5dbe1; padding: 9px; text-align: right; }
        th { background: #eef3f7; }
        .total { font-size: 17px; font-weight: bold; color: #174c77; }
        .footer { border-top: 1px solid #d5dbe1; margin-top: 30px; padding-top: 12px; }
        .qr { width: 105px; height: 105px; }
        .paid { color: #117145; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $hotelPhones = $invoice->hotel?->phones ? (json_decode($invoice->hotel->phones, true) ?: []) : [];
        if (!$hotelPhones && $invoice->hotel?->phone) $hotelPhones = [$invoice->hotel->phone];
    @endphp
    <div class="header">
        @if($logoData)<img class="logo" src="{{ $logoData }}" alt="شعار الفندق">@endif
        <h1>{{ $invoice->hotel?->name ?? 'الفندق' }}</h1>
        <p class="muted">{{ $invoice->hotel?->address }} — {{ $invoice->hotel?->province }}</p>
        <p class="muted">{{ implode(' | ', $hotelPhones) }} @if($invoice->hotel?->email)| {{ $invoice->hotel->email }}@endif</p>
    </div>

    <h2>فاتورة رقم {{ $invoice->invoice_number }}</h2>
    <p>رقم الحجز: {{ $invoice->booking_id }} | تاريخ الإصدار: {{ $invoice->created_at?->format('Y-m-d H:i') }}</p>
    <p>النزيل: {{ $invoice->booking?->guest?->name ?? '—' }} | الغرفة: {{ $invoice->room?->room_number ?? '—' }}</p>
    <p>تاريخ الدخول: {{ $invoice->booking?->check_in }} | تاريخ الخروج: {{ $invoice->booking?->check_out }}</p>

    @php
        $curr = ($invoice->currency === 'USD') ? '$ USD' : 'ل.س';
    @endphp
    <table>
        <thead><tr><th>البيان</th><th>المبلغ</th></tr></thead>
        <tbody>
            <tr><td>قيمة الإقامة</td><td>{{ number_format((float) $invoice->amount, 2) }} {{ $curr }}</td></tr>
            @if((float) $invoice->service_amount > 0)
                <tr><td>الخدمات: {{ $invoice->service_description ?: 'خدمات إضافية' }}</td><td>{{ number_format((float) $invoice->service_amount, 2) }} {{ $curr }}</td></tr>
            @endif
            @if((float) $invoice->tax > 0)<tr><td>{{ $invoice->tax_name ?: 'الضريبة' }} ({{ number_format((float) $invoice->tax_rate, 2) }}%)</td><td>{{ number_format((float) $invoice->tax, 2) }} {{ $curr }}</td></tr>@endif
            <tr><td class="total">الإجمالي</td><td class="total">{{ number_format((float) $invoice->total, 2) }} {{ $curr }}</td></tr>
        </tbody>
    </table>

    <p class="paid">الحالة: {{ $invoice->status === 'paid' ? 'مدفوعة' : 'غير مدفوعة' }}</p>
    @if($invoice->payments->last())
        <p>وسيلة الدفع: {{ ['cash' => 'نقدًا', 'card' => 'بطاقة', 'transfer' => 'تحويل'][$invoice->payments->last()->payment_method] ?? $invoice->payments->last()->payment_method }}</p>
        <p>تاريخ الدفع: {{ $invoice->payments->last()->paid_at?->format('Y-m-d H:i') }}</p>
    @endif

    <div class="footer">
        @if($qrCode)<div class="qr">{!! $qrCode !!}</div>@endif
        <p>{{ $invoice->hotel?->name }} — {{ $invoice->hotel?->address }} — {{ implode(' | ', $hotelPhones) }}</p>
        <p class="muted">رابط التحقق من الفاتورة: {{ $publicUrl }}</p>
    </div>
</body>
</html>