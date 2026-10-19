@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">

    {{-- Executive Header Toolbar --}}
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white text-xl shadow-md">
                    📊
                </span>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">تقارير وإحصائيات الفندق الشاملة</h1>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">لوحة تحليلية متقدمة للأداء التشغيلي، الإشغال، ومتابعة الإيرادات المنفصلة بالليرة السورية والدولار الأمريكي.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('accounts.reports') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50/80 px-4 py-2.5 text-xs font-bold text-blue-700 shadow-sm hover:bg-blue-100 transition">
                <span>🏦</span> كشف الحساب والستيتمنت البنكي
            </a>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                <span>🧾</span> إدارة الفواتير
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition">
                <span>🖨️</span> طباعة التقرير
            </button>
        </div>
    </div>

    {{-- Printable Formal Header (Visible Only on Print) --}}
    <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-black text-slate-900">{{ $hotel->name ?? 'فندق' }}</h2>
                <p class="text-xs text-slate-600 font-semibold uppercase">تقرير مؤشرات الأداء الشامل والمالي · Comprehensive Executive Report</p>
                <p class="text-xs text-slate-500 mt-1">تاريخ استخراج التقرير: {{ now()->format('Y-m-d H:i') }}</p>
            </div>
            <div class="text-left font-mono text-xs text-slate-500" dir="ltr">
                <div>REF: EXEC-REP-{{ date('Ymd') }}-{{ $hotel->id ?? 1 }}</div>
                <div>STATUS: OFFICIAL</div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- 1. MASTER DUAL-CURRENCY FINANCIAL HIGHLIGHTS --}}
    {{-- ======================================================== --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                    <span>💰</span> الإيرادات والمحافظ المالية (مفصولة بالكامل)
                </h2>
                <p class="text-xs text-slate-500">لا يتم دمج الليرة مع الدولار نهائياً؛ كل عملة تحسب وتدار بمحفظة وقيد مستقل.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                تسعير وحسابات منفصلة
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- 🇸🇾 1. SYRIAN POUND (SYP) WALLET CARD --}}
            <div class="rounded-3xl border-2 border-emerald-500/30 bg-gradient-to-br from-emerald-50/50 via-white to-white p-6 shadow-sm relative overflow-hidden">
                <div class="absolute -top-10 -left-10 h-32 w-32 rounded-full bg-emerald-100/40 blur-2xl pointer-events-none"></div>

                <div class="flex items-center justify-between border-b border-emerald-100 pb-4 mb-5">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🇸🇾</span>
                        <div>
                            <h3 class="text-base font-black text-slate-900">محفظة الليرة السورية</h3>
                            <span class="text-[11px] font-bold text-emerald-700 tracking-wider uppercase font-mono">SYP LEDGER & REVENUES</span>
                        </div>
                    </div>
                    <span class="rounded-xl bg-emerald-600 px-3 py-1 text-xs font-black text-white shadow-sm">
                        ل.س SYP
                    </span>
                </div>

                {{-- Total Hero Metric --}}
                <div class="mb-5 rounded-2xl bg-white p-4 border border-emerald-100/80 shadow-xs">
                    <span class="text-xs font-bold text-slate-500 block">إجمالي إيرادات الفواتير الصادرة (All-Time)</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-700 font-mono mt-1">
                        {{ number_format($totalRevenueSYP, 2) }}
                        <span class="text-sm font-bold text-slate-500 font-sans">ل.س</span>
                    </div>
                </div>

                {{-- Time Period Breakdown Cards --}}
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <span class="text-[11px] font-bold text-slate-400 block mb-1">إيراد اليوم</span>
                        <div class="text-sm sm:text-base font-black text-slate-800 font-mono">
                            {{ number_format($dailyRevenueSYP, 2) }}
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold block mt-0.5">اليوم</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <span class="text-[11px] font-bold text-slate-400 block mb-1">إيراد هذا الشهر</span>
                        <div class="text-sm sm:text-base font-black text-slate-800 font-mono">
                            {{ number_format($monthlyRevenueSYP, 2) }}
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold block mt-0.5">{{ now()->translatedFormat('F') }}</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <span class="text-[11px] font-bold text-slate-400 block mb-1">إيراد هذا العام</span>
                        <div class="text-sm sm:text-base font-black text-slate-800 font-mono">
                            {{ number_format($yearlyRevenueSYP, 2) }}
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold block mt-0.5">{{ now()->year }}</span>
                    </div>
                </div>

                {{-- Financial Accounts & Profit Breakdown --}}
                <div class="rounded-2xl bg-emerald-900 text-white p-4">
                    <div class="text-xs font-bold text-emerald-200 mb-2 flex items-center justify-between">
                        <span>سجل المحاسبة والدفاتر (SYP)</span>
                        <span class="text-[10px] bg-emerald-800 px-2 py-0.5 rounded text-emerald-100">صافي الأرباح</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div>
                            <span class="text-emerald-300 block text-[11px]">الوارد (دخل):</span>
                            <strong class="font-mono text-emerald-100 text-sm block mt-0.5">+{{ number_format($incomeSYP, 2) }}</strong>
                        </div>
                        <div>
                            <span class="text-rose-300 block text-[11px]">الصادر (مصروف):</span>
                            <strong class="font-mono text-rose-200 text-sm block mt-0.5">-{{ number_format($expenseSYP, 2) }}</strong>
                        </div>
                        <div class="border-r border-emerald-700 pr-2">
                            <span class="text-amber-300 block text-[11px]">صافي الربح:</span>
                            <strong class="font-mono text-white text-sm block mt-0.5">{{ number_format($profitSYP, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🇺🇸 2. US DOLLAR (USD) WALLET CARD --}}
            <div class="rounded-3xl border-2 border-blue-500/30 bg-gradient-to-br from-blue-50/50 via-white to-white p-6 shadow-sm relative overflow-hidden">
                <div class="absolute -top-10 -left-10 h-32 w-32 rounded-full bg-blue-100/40 blur-2xl pointer-events-none"></div>

                <div class="flex items-center justify-between border-b border-blue-100 pb-4 mb-5">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🇺🇸</span>
                        <div>
                            <h3 class="text-base font-black text-slate-900">محفظة الدولار الأمريكي</h3>
                            <span class="text-[11px] font-bold text-blue-700 tracking-wider uppercase font-mono">USD LEDGER & REVENUES</span>
                        </div>
                    </div>
                    <span class="rounded-xl bg-blue-700 px-3 py-1 text-xs font-black text-white shadow-sm font-mono">
                        $ USD
                    </span>
                </div>

                {{-- Total Hero Metric --}}
                <div class="mb-5 rounded-2xl bg-white p-4 border border-blue-100/80 shadow-xs">
                    <span class="text-xs font-bold text-slate-500 block">إجمالي إيرادات الفواتير الصادرة (All-Time)</span>
                    <div class="text-2xl sm:text-3xl font-black text-blue-800 font-mono mt-1">
                        ${{ number_format($totalRevenueUSD, 2) }}
                        <span class="text-sm font-bold text-slate-500 font-sans">USD</span>
                    </div>
                </div>

                {{-- Time Period Breakdown Cards --}}
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <span class="text-[11px] font-bold text-slate-400 block mb-1">إيراد اليوم</span>
                        <div class="text-sm sm:text-base font-black text-slate-800 font-mono">
                            ${{ number_format($dailyRevenueUSD, 2) }}
                        </div>
                        <span class="text-[10px] text-blue-600 font-bold block mt-0.5">اليوم</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <span class="text-[11px] font-bold text-slate-400 block mb-1">إيراد هذا الشهر</span>
                        <div class="text-sm sm:text-base font-black text-slate-800 font-mono">
                            ${{ number_format($monthlyRevenueUSD, 2) }}
                        </div>
                        <span class="text-[10px] text-blue-600 font-bold block mt-0.5">{{ now()->translatedFormat('F') }}</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <span class="text-[11px] font-bold text-slate-400 block mb-1">إيراد هذا العام</span>
                        <div class="text-sm sm:text-base font-black text-slate-800 font-mono">
                            ${{ number_format($yearlyRevenueUSD, 2) }}
                        </div>
                        <span class="text-[10px] text-blue-600 font-bold block mt-0.5">{{ now()->year }}</span>
                    </div>
                </div>

                {{-- Financial Accounts & Profit Breakdown --}}
                <div class="rounded-2xl bg-slate-900 text-white p-4">
                    <div class="text-xs font-bold text-slate-300 mb-2 flex items-center justify-between">
                        <span>سجل المحاسبة والدفاتر (USD)</span>
                        <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-blue-300 font-mono">PROFIT MARGIN</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">الوارد (دخل):</span>
                            <strong class="font-mono text-emerald-400 text-sm block mt-0.5">+${{ number_format($incomeUSD, 2) }}</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">الصادر (مصروف):</span>
                            <strong class="font-mono text-rose-400 text-sm block mt-0.5">-${{ number_format($expenseUSD, 2) }}</strong>
                        </div>
                        <div class="border-r border-slate-700 pr-2">
                            <span class="text-slate-400 block text-[11px]">صافي الربح:</span>
                            <strong class="font-mono text-white text-sm block mt-0.5">${{ number_format($profitUSD, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- 2. OCCUPANCY & CAPACITY METRICS --}}
    {{-- ======================================================== --}}
    <div class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                    <span>🛏️</span> نسبة الإشغال والطاقة الاستيعابية للغرف
                </h2>
                <p class="text-xs text-slate-500">معدل الإشغال اللحظي وحالة توزيع الغرف الفندقية.</p>
            </div>
            <a href="{{ route('rooms.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:underline">
                إدارة الغرف والتوزيع ←
            </a>
        </div>

        <div class="grid gap-6 md:grid-cols-12 items-center">
            {{-- Big Circular / Highlight Badge --}}
            <div class="md:col-span-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 text-center relative overflow-hidden">
                <div class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">معدل الإشغال الحالي</div>
                <div class="text-5xl font-black font-mono tracking-tight text-white mb-2">
                    {{ $occupancyRate }}<span class="text-2xl text-blue-400">%</span>
                </div>
                <p class="text-xs text-slate-400">
                    {{ $roomsReserved }} من أصل {{ $roomsTotal }} غرفة مشغولة حالياً
                </p>

                {{-- Segmented Visual Progress Bar --}}
                <div class="w-full bg-slate-700 rounded-full h-3 mt-4 overflow-hidden flex">
                    @php
                        $pctAvailable = $roomsTotal > 0 ? ($roomsAvailable / $roomsTotal) * 100 : 0;
                        $pctReserved = $roomsTotal > 0 ? ($roomsReserved / $roomsTotal) * 100 : 0;
                        $pctCleaning = $roomsTotal > 0 ? ($roomsCleaning / $roomsTotal) * 100 : 0;
                        $pctMaint = $roomsTotal > 0 ? ($roomsMaintenance / $roomsTotal) * 100 : 0;
                    @endphp
                    <div style="width: {{ $pctReserved }}%" class="bg-blue-500" title="محجوزة: {{ $roomsReserved }}"></div>
                    <div style="width: {{ $pctAvailable }}%" class="bg-emerald-500" title="متاحة: {{ $roomsAvailable }}"></div>
                    <div style="width: {{ $pctCleaning }}%" class="bg-amber-400" title="بحاجة تنظيف: {{ $roomsCleaning }}"></div>
                    <div style="width: {{ $pctMaint }}%" class="bg-rose-500" title="صيانة: {{ $roomsMaintenance }}"></div>
                </div>
            </div>

            {{-- Room Categories Breakdown Cards --}}
            <div class="md:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-4">
                {{-- Available --}}
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-emerald-800">غرف متاحة</span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-3xl font-black text-emerald-700 font-mono">{{ $roomsAvailable }}</div>
                    <span class="text-[11px] text-emerald-600 block mt-1">جاهزة فوراً للتسكين</span>
                </div>

                {{-- Reserved / Booked --}}
                <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-blue-800">غرف مشغولة</span>
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                    </div>
                    <div class="text-3xl font-black text-blue-700 font-mono">{{ $roomsReserved }}</div>
                    <span class="text-[11px] text-blue-600 block mt-1">حجوزات وإقامة نشطة</span>
                </div>

                {{-- Needs Cleaning --}}
                <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-amber-800">بحاجة تنظيف</span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="text-3xl font-black text-amber-700 font-mono">{{ $roomsCleaning }}</div>
                    <span class="text-[11px] text-amber-600 block mt-1">تنتظر التدبير الفندقي</span>
                </div>

                {{-- In Maintenance --}}
                <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-rose-800">قيد الصيانة</span>
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    </div>
                    <div class="text-3xl font-black text-rose-700 font-mono">{{ $roomsMaintenance }}</div>
                    <span class="text-[11px] text-rose-600 block mt-1">معطلة أو قيد الإصلاح</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- 3. BOOKINGS & OPERATIONAL WORKFLOW --}}
    {{-- ======================================================== --}}
    <div class="grid gap-6 lg:grid-cols-12 mb-8">
        {{-- Bookings Velocity & Lifecycle (8 Cols) --}}
        <div class="lg:col-span-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <span>🛎️</span> حركة وتدفق الحجوزات
                    </h2>
                    <p class="text-xs text-slate-500">إحصاءات الحجوزات الجديدة ودورة حياة الحجز في الفندق.</p>
                </div>
                <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:underline">
                    كل الحجوزات ←
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5">
                    <span class="text-xs font-bold text-slate-400 block mb-1">حجوزات اليوم</span>
                    <div class="text-2xl font-black text-slate-900 font-mono">{{ $dailyBookings }}</div>
                    <span class="text-[10px] text-slate-500">خلال الـ 24 ساعة الماضية</span>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5">
                    <span class="text-xs font-bold text-slate-400 block mb-1">حجوزات هذا الشهر</span>
                    <div class="text-2xl font-black text-slate-900 font-mono">{{ $monthlyBookings }}</div>
                    <span class="text-[10px] text-slate-500">شهر {{ now()->translatedFormat('F') }}</span>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5">
                    <span class="text-xs font-bold text-slate-400 block mb-1">حجوزات هذا العام</span>
                    <div class="text-2xl font-black text-slate-900 font-mono">{{ $yearlyBookings }}</div>
                    <span class="text-[10px] text-slate-500">سنة {{ now()->year }}</span>
                </div>
                <div class="rounded-xl bg-blue-50 border border-blue-200 p-3.5">
                    <span class="text-xs font-bold text-blue-600 block mb-1">إجمالي الحجوزات</span>
                    <div class="text-2xl font-black text-blue-800 font-mono">{{ $totalBookings }}</div>
                    <span class="text-[10px] text-blue-600">منذ تأسيس النظام</span>
                </div>
            </div>

            {{-- Lifecycle Status Badges --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-xs">
                <div class="rounded-xl border border-slate-200 p-3">
                    <span class="text-slate-500 block mb-1">مؤكدة</span>
                    <strong class="text-base font-black text-emerald-700 block font-mono">{{ $confirmedBookings }}</strong>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <span class="text-slate-500 block mb-1">نزلاء حاليون (Check-in)</span>
                    <strong class="text-base font-black text-blue-700 block font-mono">{{ $checkedInBookings }}</strong>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <span class="text-slate-500 block mb-1">مكتملة ومسددة</span>
                    <strong class="text-base font-black text-slate-800 block font-mono">{{ $completedBookings }}</strong>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <span class="text-slate-500 block mb-1">ملغاة</span>
                    <strong class="text-base font-black text-rose-700 block font-mono">{{ $cancelledBookings }}</strong>
                </div>
            </div>
        </div>

        {{-- Operations: Maintenance & Housekeeping (4 Cols) --}}
        <div class="lg:col-span-4 space-y-6">
            {{-- Maintenance Card --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🛠️</span> الصيانة والأعطال
                    </h3>
                    <a href="{{ route('maintenance.index') }}" class="text-xs font-bold text-blue-700 hover:underline">
                        إدارة ←
                    </a>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-xl bg-rose-50 border border-rose-100 p-3">
                        <span class="text-rose-700 font-bold block mb-1">مفتوحة</span>
                        <div class="text-xl font-black text-rose-800 font-mono">{{ $maintenanceOpen }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                        <span class="text-amber-700 font-bold block mb-1">قيد الإصلاح</span>
                        <div class="text-xl font-black text-amber-800 font-mono">{{ $maintenanceInProgress }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
                        <span class="text-emerald-700 font-bold block mb-1">منجزة</span>
                        <div class="text-xl font-black text-emerald-800 font-mono">{{ $maintenanceDone }}</div>
                    </div>
                </div>
            </div>

            {{-- Cleaning Card --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🧹</span> النظافة والتدبير الفندقي
                    </h3>
                    <a href="{{ route('cleaning.index') }}" class="text-xs font-bold text-blue-700 hover:underline">
                        إدارة ←
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-3 text-center text-xs">
                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                        <span class="text-amber-700 font-bold block mb-1">بحاجة تنظيف</span>
                        <div class="text-xl font-black text-amber-800 font-mono">{{ $cleaningOpen }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
                        <span class="text-emerald-700 font-bold block mb-1">مهام منجزة</span>
                        <div class="text-xl font-black text-emerald-800 font-mono">{{ $cleaningDone }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- 4. RECENT INVOICES FEED (SHOWING CURRENCY SEPARATION) --}}
    {{-- ======================================================== --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <div>
                <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                    <span>🧾</span> آخر الفواتير المالية الصادرة
                </h2>
                <p class="text-xs text-slate-500">سجل لحظي للفواتير الصادرة بالليرة السورية والدولار الأمريكي.</p>
            </div>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                عرض أرشيف الفواتير بالكامل ←
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="py-3 px-4">رقم الفاتورة</th>
                        <th class="py-3 px-4">النزيل والغرفة</th>
                        <th class="py-3 px-4">تاريخ الإصدار</th>
                        <th class="py-3 px-4 text-center">العملة</th>
                        <th class="py-3 px-4 text-left">قيمة الفاتورة الإجمالية</th>
                        <th class="py-3 px-4 text-center">حالة السداد</th>
                        <th class="py-3 px-4 text-center print:hidden">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($recentInvoices as $inv)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $loop->odd ? 'bg-white' : 'bg-slate-50/30' }}">
                            <td class="py-3 px-4 font-mono font-bold text-blue-700">
                                #{{ $inv->invoice_number ?? ('INV-' . str_pad($inv->id, 5, '0', STR_PAD_LEFT)) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $inv->booking?->guest?->name ?? 'نزيل عام' }}</div>
                                <div class="text-[11px] text-slate-500">غرفة: {{ $inv->room?->room_number ?? '—' }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600" dir="ltr">
                                {{ $inv->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($inv->currency === 'USD')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-blue-100 px-2.5 py-0.5 text-[11px] font-black text-blue-800 font-mono">
                                        🇺🇸 $ USD
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-800">
                                        🇸🇾 ل.س SYP
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-slate-900">
                                @if($inv->currency === 'USD')
                                    ${{ number_format($inv->total, 2) }} <span class="text-[10px] text-blue-600 font-sans font-normal">USD</span>
                                @else
                                    {{ number_format($inv->total, 2) }} <span class="text-[10px] text-slate-500 font-sans font-normal">ل.س</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800">
                                        ✓ مسددة
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800">
                                        ⏳ بانتظار الدفع
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center print:hidden">
                                <a href="{{ route('invoices.show', $inv->id) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 transition">
                                    معاينة
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                لا توجد فواتير مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
