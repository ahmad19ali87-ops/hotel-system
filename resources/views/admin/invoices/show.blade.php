@extends('layouts.app')

@section('content')
<div class="container my-4">
    <style>
        @media print {
            header, footer, nav, .no-print { display: none !important; }
            body, main { background: #fff !important; margin: 0 !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: 0 !important; }
        }
    </style>
    @php
        $hotelPhones = $invoice->hotel?->phones ? (json_decode($invoice->hotel->phones, true) ?: []) : [];
        if (!$hotelPhones && $invoice->hotel?->phone) $hotelPhones = [$invoice->hotel->phone];
    @endphp
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            @if($invoice->hotel?->logo_url)<img src="{{ $invoice->hotel->logo_url }}" alt="شعار الفندق" style="max-width:80px;max-height:64px">@endif
            <div>
                <h1 class="h3 mb-1">{{ $invoice->hotel?->name }}</h1>
                <p class="mb-0 text-muted">فاتورة رقم {{ $invoice->invoice_number }} · حجز رقم {{ $invoice->booking_id }}</p>
            </div>
        </div>
        <div class="no-print d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-primary">طباعة الفاتورة</button>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-primary">تنزيل PDF</a>
        </div>
    </div>

    <div class="card p-4 shadow-sm">
        <p><strong>النزيل:</strong> {{ $invoice->booking?->guest?->name ?? '—' }}</p>
        <p><strong>الغرفة:</strong> رقم {{ $invoice->room?->room_number ?? '—' }}</p>
        <p><strong>الدخول:</strong> {{ $invoice->booking?->check_in }} <strong>الخروج:</strong> {{ $invoice->booking?->check_out }}</p>
        <hr>
        @php
            $currSymbol = ($invoice->currency === 'USD') ? '$ USD' : 'ل.س';
        @endphp
        <p><strong>الإقامة:</strong> {{ number_format((float) $invoice->amount, 2) }} {{ $currSymbol }}</p>
        @if((float) $invoice->service_amount > 0)
            <p><strong>الخدمات:</strong> {{ $invoice->service_description ?: 'خدمات إضافية' }} · {{ number_format((float) $invoice->service_amount, 2) }} {{ $currSymbol }}</p>
        @endif
        @if((float) $invoice->tax > 0)<p><strong>{{ $invoice->tax_name ?: 'الضريبة' }} ({{ number_format((float) $invoice->tax_rate, 2) }}%):</strong> {{ number_format((float) $invoice->tax, 2) }} {{ $currSymbol }}</p>@endif
        <p class="fs-4 fw-bold text-primary"><strong>الإجمالي:</strong> {{ number_format((float) $invoice->total, 2) }} {{ $currSymbol }}</p>
        <p><strong>الحالة:</strong> {{ $invoice->status === 'paid' ? 'مدفوعة' : 'غير مدفوعة' }}</p>
        @if($invoice->payments->last())
            <p><strong>وسيلة الدفع:</strong> {{ $invoice->payments->last()->payment_method }} · {{ $invoice->payments->last()->paid_at }}</p>
        @endif
    </div>
    <footer class="mt-4 border-top pt-3 text-muted">
        {{ $invoice->hotel?->address }} · {{ implode(' | ', $hotelPhones) }} · {{ $invoice->hotel?->email }}
    </footer>
</div>
@endsection
