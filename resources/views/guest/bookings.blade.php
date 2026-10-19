@extends('layouts.app')

@section('content')
<div class="container my-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="font-black text-2xl sm:text-3xl tracking-tight text-slate-900 dark:text-slate-100 mb-1">
                🛎️ سجل حجوزاتي الفندقية
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mb-0">
                استعرض تفاصيل إقاماتك السابقة والحالية، مدة الإقامة، وحالات الحجز وسندات الفواتير
            </p>
        </div>
        <a href="{{ route('hotels.list') }}" class="btn btn-primary rounded-2xl font-bold text-xs sm:text-sm px-4 py-2.5 shadow-md shadow-blue-600/20">
            ➕ حجز إقامة جديدة
        </a>
    </div>

    @if($bookings->isEmpty())
        <div class="card border-0 shadow-sm rounded-3xl p-5 text-center my-4 bg-white dark:bg-slate-800">
            <div class="text-5xl mb-3">🏨</div>
            <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-2">لا توجد لديك أي حجوزات حتى الآن</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                تصفح قائمة الفنادق والمنتجعات الفاخرة، واختر غرفتك المثالية مع خيارات تسعير وتأكيد فوري للحجز.
            </p>
            <div>
                <a href="{{ route('hotels.list') }}" class="btn btn-primary rounded-xl px-4 py-2 font-bold text-sm">
                    استكشف الفنادق المتاحة
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($bookings as $booking)
                @php
                    $checkIn = \Carbon\Carbon::parse($booking->check_in);
                    $checkOut = \Carbon\Carbon::parse($booking->check_out);
                    $nights = max(1, (int) $checkIn->diffInDays($checkOut));

                    $statusMap = [
                        'pending' => ['label' => 'قيد الانتظار والمراجعة', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
                        'confirmed' => ['label' => 'مؤكد وجاهز للتسكين', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
                        'checked_in' => ['label' => 'مسجل دخول (إقامة حالية)', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
                        'checked_out' => ['label' => 'إقامة مكتملة ومغادرة', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
                        'completed' => ['label' => 'حجز مكتمل', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
                        'cancelled' => ['label' => 'ملغي', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
                    ];
                    $statusInfo = $statusMap[$booking->status] ?? ['label' => $booking->status, 'class' => 'bg-slate-100 text-slate-700 border-slate-300'];
                @endphp

                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-md rounded-3xl overflow-hidden hover:shadow-lg transition bg-white dark:bg-slate-800 h-100 d-flex flex-column">
                        {{-- Card Header with Hotel Info & Status --}}
                        <div class="p-4 border-b border-slate-100 dark:border-slate-700/60 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="badge border font-extrabold text-xs px-2.5 py-1 rounded-xl mb-2 {{ $statusInfo['class'] }}">
                                    {{ $statusInfo['label'] }}
                                </span>
                                <h5 class="font-black text-lg text-slate-900 dark:text-slate-100 mb-0">
                                    {{ $booking->hotel?->name ?? 'فندق' }}
                                </h5>
                                <span class="text-xs text-slate-400">
                                    📍 {{ $booking->hotel?->address ?? $booking->hotel?->province }}
                                </span>
                            </div>
                            <div class="text-end">
                                <span class="text-[11px] text-slate-400 block">رقم الحجز</span>
                                <strong class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">#{{ $booking->id }}</strong>
                            </div>
                        </div>

                        {{-- Card Body: Room & Dates --}}
                        <div class="p-4 flex-grow-1">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-700/50">
                                        <span class="text-[10px] text-slate-400 block font-bold uppercase">الغرفة والنوع</span>
                                        <strong class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 block truncate">
                                            غرفة {{ $booking->room?->number ?? '—' }} ({{ $booking->room?->type?->name ?? 'مزدوجة' }})
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-700/50">
                                        <span class="text-[10px] text-slate-400 block font-bold uppercase">مدة الإقامة</span>
                                        <strong class="text-xs sm:text-sm font-black text-blue-700 dark:text-blue-400 block">
                                            🌙 {{ $nights }} {{ $nights == 1 ? 'ليلة واحدة' : ($nights == 2 ? 'ليلتان' : 'ليالي') }}
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3 text-xs">
                                <div class="col-6">
                                    <span class="text-slate-400 block">تاريخ الوصول (Check-in):</span>
                                    <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ $booking->check_in }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-slate-400 block">تاريخ المغادرة (Check-out):</span>
                                    <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ $booking->check_out }}</strong>
                                </div>
                            </div>

                            {{-- Price separation --}}
                            <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 d-flex justify-content-between align-items-center">
                                <span class="text-xs text-emerald-800 dark:text-emerald-300 font-bold">إجمالي تكلفة الإقامة:</span>
                                <div class="text-end">
                                    <strong class="text-sm font-black text-emerald-900 dark:text-emerald-200 block">
                                        {{ number_format($booking->total_price, 2) }} ل.س
                                    </strong>
                                    @if($booking->total_price_usd > 0)
                                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                            ${{ number_format($booking->total_price_usd, 2) }} USD
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card Footer Actions --}}
                        <div class="p-3 bg-slate-50/80 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-700/60 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-1.5">
                                @if($booking->invoices->isNotEmpty())
                                    @php $inv = $booking->invoices->first(); @endphp
                                    <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary rounded-xl font-bold text-xs py-1.5 px-2.5">
                                        🧾 الفاتورة
                                    </a>
                                @endif
                                
                                <a href="{{ route('guest.messages') }}" class="btn btn-sm btn-outline-secondary rounded-xl font-bold text-xs py-1.5 px-2.5" title="مراسلة الفندق">
                                    ✉️ استفسار
                                </a>
                            </div>

                            @if(in_array($booking->status, ['pending', 'confirmed']))
                                <form action="{{ route('guest.bookings.cancel', $booking) }}" method="POST" class="m-0" onsubmit="return confirm('هل أنت متأكد من رغبتك بإلغاء هذا الحجز؟');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-xl font-bold text-xs py-1.5 px-2.5">
                                        ❌ إلغاء الحجز
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
@endsection

