@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 900px;">
    <style>
        @media print {
            header, footer, nav, .no-print, .btn, .custom-navbar { display: none !important; }
            body, main { background: #fff !important; margin: 0 !important; padding: 0 !important; }
            .invoice-wrapper { box-shadow: none !important; border: 0 !important; padding: 0 !important; margin: 0 !important; }
        }
        .invoice-wrapper {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            color: #1e293b;
            font-size: 13px;
        }
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .inv-hotel-title {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .inv-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .badge-paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
        }
        .badge-unpaid {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
        }
        .inv-info-grid {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            gap: 16px;
        }
        .inv-info-col {
            flex: 1;
        }
        .inv-info-label {
            font-size: 11px;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .inv-info-val {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .inv-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            text-align: right;
            font-size: 12px;
        }
        .inv-table td {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            text-align: right;
            font-size: 13px;
        }
        .inv-bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 20px;
        }
        .inv-qr-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
            max-width: 240px;
        }
        .inv-qr-box svg, .inv-qr-box img {
            width: 120px;
            height: 120px;
            margin: 0 auto 8px auto;
            display: block;
        }
        .inv-totals-box {
            width: 320px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px 16px;
        }
        .inv-totals-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
            color: #334155;
        }
        .inv-totals-final {
            font-size: 16px;
            font-weight: bold;
            color: #1d4ed8;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            margin-top: 8px;
        }
        .inv-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            text-align: center;
            padding-top: 10px;
        }
        .inv-sign-line {
            border-bottom: 1px dotted #94a3b8;
            width: 170px;
            margin: 28px auto 4px auto;
        }
        .inv-footer {
            border-top: 1px solid #e2e8f0;
            margin-top: 24px;
            padding-top: 12px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
    </style>

    @php
        $hotel = $invoice->hotel;
        $hotelPhones = $hotel?->phones ? (json_decode($hotel->phones, true) ?: []) : [];
        if (!$hotelPhones && $hotel?->phone) $hotelPhones = [$hotel->phone];

        $currency = $invoice->currency ?: 'USD';
        $currSymbol = ($currency === 'USD') ? '$ USD' : 'ل.س';
        $isPaid = $invoice->status === 'paid';

        $checkIn = $invoice->booking?->check_in ? \Carbon\Carbon::parse($invoice->booking->check_in) : null;
        $checkOut = $invoice->booking?->check_out ? \Carbon\Carbon::parse($invoice->booking->check_out) : null;
        $nights = $invoice->nights ?: (($checkIn && $checkOut) ? max(1, $checkIn->diffInDays($checkOut)) : 1);
        $roomPricePerNight = $invoice->nightly_rate ?: round(((float) $invoice->amount) / $nights, 2);

        $cashier = $invoice->booking?->creator?->name ?? auth()->user()?->name ?? 'موظف الاستقبال';
        $dateStr = $invoice->created_at?->format('Y-m-d H:i') ?? date('Y-m-d H:i');
    @endphp

    {{-- Top Action Toolbar (No Print) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 no-print">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
            ⬅️ العودة لقائمة الفواتير
        </a>
        <div class="d-flex gap-2">
            @if(!$isPaid && in_array(auth()->user()->role, ['hotel_admin', 'reception', 'accountant', 'super_admin']))
                <button type="button" class="btn btn-success btn-sm font-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                    💳 تسجيل سداد الفاتورة
                </button>
            @endif
            <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm font-bold">
                🖨️ طباعة الفاتورة (A4)
            </button>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-primary btn-sm font-bold">
                ⬇️ تنزيل كملف PDF
            </a>
        </div>
    </div>

    {{-- Main Invoice Sheet (Desktop Design A4) --}}
    <div class="invoice-wrapper">
        {{-- Header --}}
        <div class="inv-header">
            <div>
                <div class="d-flex align-items-center gap-3">
                    @if($hotel?->logo_url)
                        <img src="{{ $hotel->logo_url }}" alt="شعار الفندق" style="max-height: 52px; max-width: 90px; object-fit: contain;">
                    @endif
                    <div>
                        <div class="inv-hotel-title">🏨 {{ $hotel?->name ?? 'الفندق' }}</div>
                        <div class="inv-subtitle">نظام الإدارة الفندقية المتكامل · قسم المحاسبة والفوترة الرسمية</div>
                        <div class="inv-subtitle">المصدر / المحاسب: {{ $cashier }} · تاريخ الإصدار: {{ $dateStr }}</div>
                    </div>
                </div>
            </div>
            <div class="text-start">
                <span class="{{ $isPaid ? 'badge-paid' : 'badge-unpaid' }}">
                    {{ $isPaid ? 'مدفوعة بالكامل ✓' : 'غير مسددة ⚠️' }}
                </span>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px; font-weight: bold; font-family: monospace;">
                    REF: {{ $invoice->invoice_number }}
                </div>
            </div>
        </div>

        {{-- Info Grid (3 Columns) --}}
        <div class="inv-info-grid">
            <div class="inv-info-col">
                <div class="inv-info-label">بيانات النزيل المستفيد</div>
                <div class="inv-info-val">{{ $invoice->booking?->guest?->name ?? 'نزيل محترم' }}</div>
                <div style="font-size: 12px; color: #475569;">الهاتف: {{ $invoice->booking?->guest?->phone ?? '—' }}</div>
                @if($invoice->booking?->guest?->national_id || $invoice->booking?->guest?->id_number)
                    <div style="font-size: 11px; color: #64748b;">الهوية/الجواز: {{ $invoice->booking->guest->id_number ?: $invoice->booking->guest->national_id }}</div>
                @endif
            </div>
            <div class="inv-info-col">
                <div class="inv-info-label">بيانات الغرفة والإقامة</div>
                <div class="inv-info-val">غرفة رقم {{ $invoice->room?->room_number ?? '—' }}</div>
                <div style="font-size: 12px; color: #475569;">تصنيف: {{ $invoice->room?->room_type ?? 'غرفة فندقية' }}</div>
                <div style="font-size: 11px; color: #64748b;">
                    {{ $invoice->booking?->check_in }} ⬅️ {{ $invoice->booking?->check_out }} ({{ $nights }} {{ $nights > 1 ? 'ليالٍ' : 'ليلة' }})
                </div>
            </div>
            <div class="inv-info-col">
                <div class="inv-info-label">رقم الفاتورة والمرجع</div>
                <div class="inv-info-val">{{ $invoice->invoice_number }}</div>
                <div style="font-size: 12px; color: #475569;">العملة المعتمدة: {{ $currSymbol }} ({{ $currency === 'USD' ? 'دولار أمريكي' : 'ليرة سورية' }})</div>
                <div style="font-size: 11px; color: #64748b;">رقم الحجز: #{{ $invoice->booking_id }}</div>
            </div>
        </div>

        {{-- Table --}}
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width: 50%;">بيان الخدمة / بند الإقامة</th>
                    <th style="width: 15%; text-align: center;">الكمية</th>
                    <th style="width: 15%; text-align: left;">السعر الفردي</th>
                    <th style="width: 20%; text-align: left;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>إقامة فندقية - غرفة رقم {{ $invoice->room?->room_number ?? '—' }}</strong>
                        <span class="text-muted">({{ $invoice->room?->room_type ?? 'غرفة' }}) · مدة الإقامة {{ $nights }} {{ $nights > 1 ? 'ليالٍ' : 'ليلة' }}</span>
                    </td>
                    <td style="text-align: center;">{{ $nights }}</td>
                    <td style="text-align: left;">{{ number_format($roomPricePerNight, 2) }} {{ $currSymbol }}</td>
                    <td style="text-align: left;"><strong>{{ number_format((float) $invoice->amount, 2) }} {{ $currSymbol }}</strong></td>
                </tr>
                @if((float) $invoice->service_amount > 0)
                    <tr>
                        <td>
                            <strong>خدمات فندقية إضافية:</strong> {{ $invoice->service_description ?: 'خدمات وضيافة إضافية' }}
                        </td>
                        <td style="text-align: center;">1</td>
                        <td style="text-align: left;">{{ number_format((float) $invoice->service_amount, 2) }} {{ $currSymbol }}</td>
                        <td style="text-align: left;"><strong>{{ number_format((float) $invoice->service_amount, 2) }} {{ $currSymbol }}</strong></td>
                    </tr>
                @endif
                @if((float) $invoice->tax > 0)
                    <tr>
                        <td>
                            <strong>الضريبة والرسوم الفندقية:</strong> {{ $invoice->tax_name ?: 'الضريبة الرسمية' }}
                            @if((float) $invoice->tax_rate > 0)
                                ({{ number_format((float) $invoice->tax_rate, 2) }}%)
                            @endif
                        </td>
                        <td style="text-align: center;">1</td>
                        <td style="text-align: left;">{{ number_format((float) $invoice->tax, 2) }} {{ $currSymbol }}</td>
                        <td style="text-align: left;"><strong>{{ number_format((float) $invoice->tax, 2) }} {{ $currSymbol }}</strong></td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Bottom Section: QR Code (Right) and Totals Box (Left) --}}
        <div class="inv-bottom-section">
            {{-- QR Code Box --}}
            <div class="inv-qr-box">
                @if(isset($qrCode) && $qrCode)
                    {!! $qrCode !!}
                @endif
                <div style="font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 6px;">رمز التحقق الرقمي من الفاتورة</div>
                <div style="font-size: 10px; color: #64748b; word-break: break-all; margin-top: 2px;">
                    /{{ $hotel?->slug ?: 'hotel' }}/invoices/{{ $invoice->id }}
                </div>
            </div>

            {{-- Totals Box --}}
            <div class="inv-totals-box">
                <div class="inv-totals-row">
                    <span>المجموع الفرعي للإقامة:</span>
                    <span>{{ number_format((float) $invoice->amount, 2) }} {{ $currSymbol }}</span>
                </div>
                @if((float) $invoice->service_amount > 0)
                    <div class="inv-totals-row">
                        <span>الخدمات الإضافية:</span>
                        <span>{{ number_format((float) $invoice->service_amount, 2) }} {{ $currSymbol }}</span>
                    </div>
                @endif
                @if((float) $invoice->tax > 0)
                    <div class="inv-totals-row">
                        <span>الضريبة والرسوم:</span>
                        <span>{{ number_format((float) $invoice->tax, 2) }} {{ $currSymbol }}</span>
                    </div>
                @endif
                <div class="inv-totals-row inv-totals-final">
                    <span>المبلغ الإجمالي النهائي:</span>
                    <span>{{ number_format((float) $invoice->total, 2) }} {{ $currSymbol }}</span>
                </div>

                @if($isPaid && $invoice->payments->last())
                    <div style="font-size: 11px; color: #166534; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                        ✓ تم السداد بواسطة: {{ ['cash' => 'نقدًا', 'card' => 'بطاقة دفع', 'transfer' => 'تحويل بنكي'][$invoice->payments->last()->payment_method] ?? $invoice->payments->last()->payment_method }}
                        <br>تاريخ السداد: {{ $invoice->payments->last()->paid_at?->format('Y-m-d H:i') ?? $invoice->payments->last()->created_at?->format('Y-m-d H:i') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Signatures --}}
        <div class="inv-signatures">
            <div>
                <div style="font-weight: bold; font-size: 12px; color: #475569;">توقيع النزيل المستلم</div>
                <div class="inv-sign-line"></div>
            </div>
            <div>
                <div style="font-weight: bold; font-size: 12px; color: #475569;">المحاسب المسؤول</div>
                <div class="inv-sign-line"></div>
            </div>
            <div>
                <div style="font-weight: bold; font-size: 12px; color: #475569;">ختم وتوقيع إدارة الفندق</div>
                <div class="inv-sign-line"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="inv-footer">
            <div>{{ $hotel?->name }} · {{ $hotel?->address }} — {{ $hotel?->province }}</div>
            <div>{{ implode(' | ', $hotelPhones) }} @if($hotel?->email) · {{ $hotel->email }} @endif</div>
            <div style="color: #94a3b8; margin-top: 4px;">شكراً لاختياركم فندقنا · نتمنى لكم إقامة سعيدة ومميزة</div>
        </div>
    </div>
</div>

{{-- Payment Modal (If unpaid) --}}
@if(!$isPaid)
<div class="modal fade no-print" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('invoices.pay', $invoice) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="payModalLabel">💳 تسجيل سداد الفاتورة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        المبلغ المطلوب سداده: <strong class="text-primary fs-5">{{ number_format((float) $invoice->total, 2) }} {{ $currSymbol }}</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label font-bold">طريقة الدفع:</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">نقداً (كاش)</option>
                            <option value="card">بطاقة بنكية / نقطة بيع POS</option>
                            <option value="transfer">تحويل بنكي</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-bold">رقم المرجع / الإيصال (اختياري):</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="رقم إيصال الدفع إن وجد">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success font-bold">تأكيد السداد وإصدار الإيصال</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
