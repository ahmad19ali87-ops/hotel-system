<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            color: #1e293b;
            font-size: 11px;
            direction: rtl;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .hotel-title {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .subtitle {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 11px;
            display: inline-block;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 11px;
            display: inline-block;
        }
        .info-table {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .info-table td {
            padding: 8px 10px;
            vertical-align: top;
            border: none;
            width: 33.33%;
        }
        .info-label {
            font-size: 8.5px;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .info-val {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .inv-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            padding: 7px 9px;
            border: 1px solid #cbd5e1;
            text-align: right;
            font-size: 9.5px;
        }
        .inv-table td {
            padding: 7px 9px;
            border: 1px solid #cbd5e1;
            text-align: right;
            font-size: 10px;
        }
        .bottom-table {
            width: 100%;
            margin-bottom: 18px;
        }
        .bottom-table td {
            vertical-align: top;
            border: none;
        }
        .qr-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            width: 160px;
        }
        .qr-box svg, .qr-box img {
            width: 95px;
            height: 95px;
            margin: 0 auto;
        }
        .totals-box {
            width: 250px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
        }
        .totals-row-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-row-table td {
            border: none;
            padding: 2px 0;
            font-size: 9.5px;
        }
        .totals-final {
            font-size: 12px;
            font-weight: bold;
            color: #1d4ed8;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 4px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 20px;
            text-align: center;
        }
        .signatures-table td {
            border: none;
            vertical-align: top;
            width: 33.33%;
        }
        .sign-title {
            font-weight: bold;
            font-size: 9.5px;
            color: #475569;
        }
        .sign-line {
            border-bottom: 1px dotted #94a3b8;
            width: 120px;
            margin: 20px auto 0 auto;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            margin-top: 16px;
            padding-top: 8px;
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
        }
    </style>
</head>
<body>
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

        $cashier = $invoice->booking?->creator?->name ?? 'موظف الاستقبال';
        $dateStr = $invoice->created_at?->format('Y-m-d H:i') ?? date('Y-m-d H:i');
    @endphp

    {{-- Header Table --}}
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <table style="border: none;">
                    <tr>
                        @if($logoData)
                            <td style="border: none; padding-left: 10px;">
                                <img src="{{ $logoData }}" style="max-height: 48px; max-width: 80px;" alt="شعار الفندق">
                            </td>
                        @endif
                        <td style="border: none;">
                            <div class="hotel-title">🏨 {{ $hotel?->name ?? 'الفندق' }}</div>
                            <div class="subtitle">نظام الإدارة الفندقية المتكامل · قسم المحاسبة والفوترة الرسمية</div>
                            <div class="subtitle">المصدر / المحاسب: {{ $cashier }} · تاريخ الإصدار: {{ $dateStr }}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 30%; text-align: left;">
                <span class="{{ $isPaid ? 'badge-paid' : 'badge-unpaid' }}">
                    {{ $isPaid ? 'مدفوعة بالكامل ✓' : 'غير مسددة ⚠️' }}
                </span>
                <div style="font-size: 9.5px; color: #64748b; margin-top: 5px; font-weight: bold; font-family: monospace;">
                    REF: {{ $invoice->invoice_number }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Info Grid (3 Columns) --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-label">بيانات النزيل المستفيد</div>
                <div class="info-val">{{ $invoice->booking?->guest?->name ?? 'نزيل محترم' }}</div>
                <div style="font-size: 9px; color: #475569;">الهاتف: {{ $invoice->booking?->guest?->phone ?? '—' }}</div>
                @if($invoice->booking?->guest?->national_id || $invoice->booking?->guest?->id_number)
                    <div style="font-size: 8.5px; color: #64748b;">الهوية/الجواز: {{ $invoice->booking->guest->id_number ?: $invoice->booking->guest->national_id }}</div>
                @endif
            </td>
            <td>
                <div class="info-label">بيانات الغرفة والإقامة</div>
                <div class="info-val">غرفة رقم {{ $invoice->room?->room_number ?? '—' }}</div>
                <div style="font-size: 9px; color: #475569;">تصنيف: {{ $invoice->room?->room_type ?? 'غرفة فندقية' }}</div>
                <div style="font-size: 8.5px; color: #64748b;">
                    {{ $invoice->booking?->check_in }} ⬅️ {{ $invoice->booking?->check_out }} ({{ $nights }} {{ $nights > 1 ? 'ليالٍ' : 'ليلة' }})
                </div>
            </td>
            <td>
                <div class="info-label">رقم الفاتورة والمرجع</div>
                <div class="info-val">{{ $invoice->invoice_number }}</div>
                <div style="font-size: 9px; color: #475569;">العملة: {{ $currSymbol }} ({{ $currency === 'USD' ? 'دولار أمريكي' : 'ليرة سورية' }})</div>
                <div style="font-size: 8.5px; color: #64748b;">رقم الحجز: #{{ $invoice->booking_id }}</div>
            </td>
        </tr>
    </table>

    {{-- Main Items Table --}}
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
                    <span style="color: #64748b;">({{ $invoice->room?->room_type ?? 'غرفة' }}) · مدة الإقامة {{ $nights }} {{ $nights > 1 ? 'ليالٍ' : 'ليلة' }}</span>
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

    {{-- Bottom Table: QR Code (Right) and Totals Box (Left) --}}
    <table class="bottom-table">
        <tr>
            <td style="width: 45%;">
                <div class="qr-box">
                    @if(isset($qrCode) && $qrCode)
                        <div>{!! $qrCode !!}</div>
                    @endif
                    <div style="font-size: 8.5px; font-weight: bold; color: #0f172a; margin-top: 4px;">رمز التحقق الرقمي من الفاتورة</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">/{{ $hotel?->slug ?: 'hotel' }}/invoices/{{ $invoice->id }}</div>
                </div>
            </td>
            <td style="width: 55%; text-align: left;">
                <div class="totals-box" style="margin-left: 0; margin-right: auto;">
                    <table class="totals-row-table">
                        <tr>
                            <td>المجموع الفرعي للإقامة:</td>
                            <td style="text-align: left; font-weight: bold;">{{ number_format((float) $invoice->amount, 2) }} {{ $currSymbol }}</td>
                        </tr>
                        @if((float) $invoice->service_amount > 0)
                            <tr>
                                <td>الخدمات الإضافية:</td>
                                <td style="text-align: left; font-weight: bold;">{{ number_format((float) $invoice->service_amount, 2) }} {{ $currSymbol }}</td>
                            </tr>
                        @endif
                        @if((float) $invoice->tax > 0)
                            <tr>
                                <td>الضريبة والرسوم:</td>
                                <td style="text-align: left; font-weight: bold;">{{ number_format((float) $invoice->tax, 2) }} {{ $currSymbol }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="totals-final">المبلغ الإجمالي النهائي:</td>
                            <td class="totals-final" style="text-align: left;">{{ number_format((float) $invoice->total, 2) }} {{ $currSymbol }}</td>
                        </tr>
                    </table>

                    @if($isPaid && $invoice->payments->last())
                        <div style="font-size: 8.5px; color: #166534; margin-top: 5px; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                            ✓ تم السداد بواسطة: {{ ['cash' => 'نقدًا', 'card' => 'بطاقة دفع', 'transfer' => 'تحويل بنكي'][$invoice->payments->last()->payment_method] ?? $invoice->payments->last()->payment_method }}
                            | تاريخ السداد: {{ $invoice->payments->last()->paid_at?->format('Y-m-d H:i') ?? $invoice->payments->last()->created_at?->format('Y-m-d H:i') }}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Signatures Table --}}
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sign-title">توقيع النزيل المستلم</div>
                <div class="sign-line"></div>
            </td>
            <td>
                <div class="sign-title">المحاسب المسؤول</div>
                <div class="sign-line"></div>
            </td>
            <td>
                <div class="sign-title">ختم وتوقيع إدارة الفندق</div>
                <div class="sign-line"></div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div>{{ $hotel?->name }} · {{ $hotel?->address }} — {{ $hotel?->province }}</div>
        <div>{{ implode(' | ', $hotelPhones) }} @if($hotel?->email) · {{ $hotel->email }} @endif</div>
        <div style="color: #94a3b8; margin-top: 3px;">شكراً لاختياركم فندقنا · نتمنى لكم إقامة سعيدة ومميزة</div>
    </div>
</body>
</html>
