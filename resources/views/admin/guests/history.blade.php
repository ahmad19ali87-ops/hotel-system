@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl py-6 px-4">
    {{-- Header & Breadcrumb --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <a href="{{ route('guests.index') }}" class="hover:underline text-blue-600">سجل النزلاء</a>
                <span>/</span>
                <span class="text-slate-500">سجل إقامات النزيل</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-2">
                <span>🏨 سجل إقامات النزيل:</span>
                <span class="text-blue-700">{{ $guest->full_name }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                استعراض تواريخ الإقامة، سعر الليلة الفعلي، العملة المعتمدة، والمبالغ المسددة من واقع الفواتير
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('guests.print', $guest) }}" target="_blank" class="btn btn-outline-secondary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5">
                <span>🖨️</span>
                <span>طباعة بطاقة النزيل</span>
            </a>
            <a href="{{ route('guests.index') }}" class="btn btn-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5">
                <span>⬅️</span>
                <span>العودة لسجل النزلاء</span>
            </a>
        </div>
    </div>

    {{-- Guest Summary Card & Financial Stats --}}
    @php
        $totalPaidSYP = 0;
        $totalPaidUSD = 0;
        $totalNights = 0;

        foreach ($bookings as $b) {
            $cIn = \Carbon\Carbon::parse($b->check_in);
            $cOut = \Carbon\Carbon::parse($b->check_out);
            $n = max(1, $cIn->diffInDays($cOut));
            $totalNights += $n;

            $inv = $b->invoices->first();
            $curr = $inv?->currency ?? $b->currency ?? 'SYP';
            $paid = 0;
            if ($inv) {
                $paid = ($inv->status === 'paid') ? (float)$inv->total : (float)$inv->payments->where('status', 'success')->sum('amount');
            } elseif ($b->status === 'checked_out') {
                $paid = ($curr === 'USD' && (float)$b->total_price_usd > 0) ? (float)$b->total_price_usd : (float)$b->total_price;
            }

            if ($curr === 'USD') {
                $totalPaidUSD += $paid;
            } else {
                $totalPaidSYP += $paid;
            }
        }
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Guest Profile Mini Card --}}
        <div class="md:col-span-2 card p-4 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-xl text-blue-600 font-black shrink-0">
                    {{ mb_substr($guest->first_name ?: $guest->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-slate-900 truncate">{{ $guest->full_name }}</h3>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600 mt-2">
                        <div>
                            <span class="text-slate-400">الهاتف:</span>
                            <span class="font-bold text-slate-800" dir="ltr">{{ $guest->phone ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">الجنسية:</span>
                            <span class="font-bold text-slate-800">{{ $guest->nationality ?: 'سوري' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">رقم الوثيقة:</span>
                            <span class="font-bold text-slate-800">{{ $guest->id_number ?: $guest->national_id ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">البريد:</span>
                            <span class="text-slate-800 truncate block">{{ $guest->email ?: '—' }}</span>
                        </div>
                    </div>
                    @if($guest->notes)
                        <div class="mt-2.5 p-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600">
                            <span class="font-bold text-slate-700">ملاحظات:</span> {{ $guest->notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Total Stays Metric --}}
        <div class="card p-4 shadow-sm border border-slate-200 bg-gradient-to-br from-blue-50 to-white rounded-2xl flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold text-blue-700 uppercase tracking-wider block">سجل الإقامات</span>
                <div class="text-2xl font-black text-blue-950 mt-1">{{ count($bookings) }} إقامة</div>
                <div class="text-xs text-slate-500 mt-0.5">إجمالي الليالي: <strong class="text-slate-800">{{ $totalNights }} ليلة</strong></div>
            </div>
            <div class="mt-3 pt-2 border-t border-blue-100 flex items-center justify-between text-xs text-blue-800">
                <span>الحالة العامة</span>
                <span class="font-bold">{{ count($bookings) > 1 ? 'نزيل متكرر (VIP)' : 'نزيل مسجل' }}</span>
            </div>
        </div>

        {{-- Total Paid Metric --}}
        <div class="card p-4 shadow-sm border border-slate-200 bg-gradient-to-br from-emerald-50 to-white rounded-2xl flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block">إجمالي المبالغ المسددة فعلاً</span>
                <div class="text-lg font-black text-emerald-950 mt-1">
                    {{ number_format($totalPaidSYP) }} <span class="text-xs font-bold text-emerald-700">ل.س</span>
                </div>
                @if($totalPaidUSD > 0)
                    <div class="text-base font-black text-emerald-800 mt-0.5">
                        ${{ number_format($totalPaidUSD, 2) }} <span class="text-xs font-bold text-emerald-600">USD</span>
                    </div>
                @endif
            </div>
            <div class="mt-3 pt-2 border-t border-emerald-100 flex items-center justify-between text-xs text-emerald-800">
                <span>المطابقة المالية</span>
                <span class="font-bold">حسب الفواتير المسددة</span>
            </div>
        </div>
    </div>

    {{-- Stays Table --}}
    <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900 m-0 flex items-center gap-2">
                <span>📋 قائمة الإقامات والحجوزات التفصيلية</span>
                <span class="badge bg-blue-100 text-blue-800 border border-blue-300 font-bold px-2 py-0.5 text-xs rounded-full">
                    {{ count($bookings) }}
                </span>
            </h3>
            <span class="text-xs text-slate-500">مرتبة من الأحدث إلى الأقدم حسب تاريخ الوصول</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-slate-100 text-slate-700 text-xs font-extrabold uppercase border-b border-slate-200">
                    <tr>
                        <th class="ps-4 py-3">الغرفة والنوع</th>
                        <th>فترة الإقامة</th>
                        <th>عدد الليالي</th>
                        <th>سعر الليلة الفعلي</th>
                        <th>إجمالي الفاتورة والمسدد</th>
                        <th>حالة الحجز</th>
                        <th>بيانات الفاتورة</th>
                        <th class="text-end pe-4">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($bookings as $booking)
                        @php
                            $checkIn = \Carbon\Carbon::parse($booking->check_in);
                            $checkOut = \Carbon\Carbon::parse($booking->check_out);
                            $nights = max(1, $checkIn->diffInDays($checkOut));

                            $invoice = $booking->invoices->first();
                            $currency = $invoice?->currency ?? $booking->currency ?? 'SYP';
                            $isUSD = ($currency === 'USD');

                            // 1. سعر الليلة الفعلي
                            if ($invoice && (float)$invoice->amount > 0) {
                                $nightlyRate = round((float)$invoice->amount / $nights, 2);
                            } elseif ($isUSD && (float)$booking->total_price_usd > 0) {
                                $nightlyRate = round((float)$booking->total_price_usd / $nights, 2);
                            } else {
                                $nightlyRate = round((float)$booking->total_price / $nights, 2);
                            }

                            // 2. إجمالي الفاتورة والمسدد فعلاً
                            if ($invoice) {
                                $invoiceTotal = (float)$invoice->total;
                                $isPaid = ($invoice->status === 'paid');
                                $actualPaid = $isPaid ? $invoiceTotal : (float)$invoice->payments->where('status', 'success')->sum('amount');
                            } else {
                                $invoiceTotal = ($isUSD && (float)$booking->total_price_usd > 0) ? (float)$booking->total_price_usd : (float)$booking->total_price;
                                $isPaid = ($booking->status === 'checked_out');
                                $actualPaid = $isPaid ? $invoiceTotal : 0.0;
                            }

                            $currencySymbol = $isUSD ? '$' : 'ل.س';
                            $currencySuffix = $isUSD ? 'USD' : 'ل.س';
                        @endphp
                        <tr>
                            {{-- Room & Type --}}
                            <td class="ps-4 py-3">
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-black">
                                        {{ $booking->room?->room_number ?? '—' }}
                                    </span>
                                    <span>غرفة رقم {{ $booking->room?->room_number ?? '—' }}</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $booking->room?->roomType?->name ?? 'غرفة فندقية' }}
                                </div>
                            </td>

                            {{-- Stay Period --}}
                            <td>
                                <div class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                    <span>{{ $booking->check_in }}</span>
                                    <span class="text-slate-400">⬅️</span>
                                    <span>{{ $booking->check_out }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    تاريخ التسجيل: {{ $booking->created_at ? $booking->created_at->format('Y-m-d') : '—' }}
                                </div>
                            </td>

                            {{-- Nights Count --}}
                            <td>
                                <span class="badge bg-slate-100 text-slate-800 border border-slate-300 font-bold px-2 py-1 text-xs">
                                    {{ $nights }} {{ $nights == 1 ? 'ليلة واحدة' : ($nights == 2 ? 'ليلتان' : 'ليالٍ') }}
                                </span>
                            </td>

                            {{-- Nightly Rate (Fixed and Exact with Currency) --}}
                            <td>
                                @if($isUSD)
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-800 font-black text-xs">
                                        <span>$ {{ number_format($nightlyRate, 2) }}</span>
                                        <span class="text-[10px] text-emerald-600">USD / ليلة</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 font-black text-xs">
                                        <span>{{ number_format($nightlyRate) }}</span>
                                        <span class="text-[10px] text-blue-600">ل.س / ليلة</span>
                                    </div>
                                @endif
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $invoice ? 'حسب الفاتورة الصادرة' : 'حسب سعر الحجز' }}
                                </div>
                            </td>

                            {{-- Total & Actually Paid Amount --}}
                            <td>
                                <div class="text-xs">
                                    <div class="font-bold text-slate-700">
                                        المجموع: 
                                        @if($isUSD)
                                            <span class="font-black text-slate-900">${{ number_format($invoiceTotal, 2) }} USD</span>
                                        @else
                                            <span class="font-black text-slate-900">{{ number_format($invoiceTotal) }} ل.س</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs text-slate-500">المسدد فعلاً:</span>
                                        @if($isUSD)
                                            <span class="font-black text-emerald-700">${{ number_format($actualPaid, 2) }} USD</span>
                                        @else
                                            <span class="font-black text-emerald-700">{{ number_format($actualPaid) }} ل.س</span>
                                        @endif

                                        @if($isPaid || ($actualPaid >= $invoiceTotal && $invoiceTotal > 0))
                                            <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                                                ✔ مسدد بالكامل
                                            </span>
                                        @elseif($actualPaid > 0)
                                            <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                                                مسدد جزئياً
                                            </span>
                                        @else
                                            <span class="badge bg-rose-100 text-rose-800 border border-rose-300 text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                                                غير مسدد
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Booking Status --}}
                            <td>
                                @if($booking->status === 'checked_in')
                                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold px-2.5 py-1 text-xs rounded-full">
                                        🏨 مقيم حالياً (تم الدخول)
                                    </span>
                                @elseif($booking->status === 'checked_out')
                                    <span class="badge bg-slate-100 text-slate-700 border border-slate-300 font-bold px-2.5 py-1 text-xs rounded-full">
                                        🚪 تم تسجيل المغادرة
                                    </span>
                                @elseif($booking->status === 'confirmed')
                                    <span class="badge bg-blue-100 text-blue-800 border border-blue-300 font-bold px-2.5 py-1 text-xs rounded-full">
                                        📅 حجز مؤكد
                                    </span>
                                @else
                                    <span class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold px-2.5 py-1 text-xs rounded-full">
                                        ❌ ملغي
                                    </span>
                                @endif
                            </td>

                            {{-- Invoice Details --}}
                            <td>
                                @if($invoice)
                                    <div class="text-xs">
                                        <div class="font-bold text-slate-800 font-mono">{{ $invoice->invoice_number }}</div>
                                        <div class="mt-0.5">
                                            @if($invoice->status === 'paid')
                                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] px-1.5 py-0.5">فاتورة مسددة</span>
                                            @else
                                                <span class="badge bg-amber-50 text-amber-700 border border-amber-200 text-[10px] px-1.5 py-0.5">بانتظار السداد</span>
                                            @endif
                                            <span class="badge bg-slate-100 text-slate-600 text-[10px] px-1 py-0.5 font-bold">{{ $currency }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">لم تصدر فاتورة بعد</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <a href="{{ route('bookings.edit', $booking->id) }}" class="btn btn-outline-secondary btn-sm text-xs font-bold px-2 py-1" title="عرض تفاصيل الحجز">
                                        👁️ الحجز
                                    </a>

                                    @if($invoice)
                                        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-primary btn-sm text-xs font-bold px-2.5 py-1" title="معاينة الفاتورة الرسمية">
                                            📄 الفاتورة
                                        </a>
                                        <a href="{{ route('invoices.pdf', $invoice->id) }}" class="btn btn-outline-dark btn-sm text-xs font-bold px-2 py-1" title="طباعة أو تحميل PDF">
                                            🖨️
                                        </a>
                                    @else
                                        @if(auth()->user()->role !== 'hotel_owner')
                                            <a href="{{ route('invoices.create', ['booking_id' => $booking->id]) }}" class="btn btn-outline-success btn-sm text-xs font-bold px-2 py-1" title="إصدار فاتورة جديدة">
                                                ➕ فاتورة
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="text-3xl mb-2">🏨</div>
                                <div class="font-bold text-slate-700">لا توجد إقامات أو حجوزات مسجلة لهذا النزيل حتى الآن.</div>
                                <div class="text-xs text-slate-400 mt-1">عند تسجيل حجز جديد للنزيل ستظهر سجلات إقامته ومبالغها هنا تلقائياً.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
