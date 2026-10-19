<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:760px">
    <section class="rounded bg-white p-4 shadow">
        @if($invoice->hotel?->logo_url)<img src="{{ $invoice->hotel->logo_url }}" alt="شعار الفندق" style="max-height:80px;max-width:120px">@endif
        <h1 class="h3 mt-3">{{ $invoice->hotel?->name }}</h1>
        <p>{{ $invoice->hotel?->address }} — {{ $invoice->hotel?->province }}</p>
        <p>{{ implode(' | ', $hotelPhones) }} @if($invoice->hotel?->email)| {{ $invoice->hotel->email }}@endif</p>
        <hr>
        <h2 class="h4">فاتورة رقم {{ $invoice->invoice_number }}</h2>
        <p>رقم الحجز: {{ $invoice->booking_id }} | الغرفة: {{ $invoice->room?->room_number ?? '—' }}</p>
        <p>النزيل: {{ $invoice->booking?->guest?->name ?? '—' }}</p>
        <p>الإقامة: {{ number_format((float) $invoice->amount, 2) }} ل.س</p>
        @if((float) $invoice->service_amount > 0)
            <p>الخدمات ({{ $invoice->service_description ?: 'إضافية' }}): {{ number_format((float) $invoice->service_amount, 2) }} ل.س</p>
        @endif
        @if((float) $invoice->tax > 0)
            <p>{{ $invoice->tax_name ?: 'الضريبة' }} ({{ number_format((float) $invoice->tax_rate, 2) }}%): {{ number_format((float) $invoice->tax, 2) }} ل.س</p>
        @endif
        <p class="h4">الإجمالي: {{ number_format((float) $invoice->total, 2) }} ل.س</p>
        <p>الحالة: {{ $invoice->status === 'paid' ? 'مدفوعة' : 'غير مدفوعة' }}</p>
    </section>
</main>
</body>
</html>