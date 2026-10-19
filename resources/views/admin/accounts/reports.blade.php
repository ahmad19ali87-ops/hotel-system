@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    {{-- Header Actions Toolbar (Hidden on Print) --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-700 text-lg">🏦</span>
                <h1 class="text-2xl font-bold text-slate-800">كشف الحساب والتقارير المالية</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">كشف حساب بنكي تفصيلي (Bank Statement) للحركات، الصادر، الوارد، والأرصدة التراكمية.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('accounts.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                <span>←</span> دفتر الحسابات
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                <span>🖨️</span> طباعة الكشف
            </button>
            <a href="{{ route('accounts.reports.pdf', request()->query()) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 transition">
                <span>📥</span> تحميل PDF
            </a>
        </div>
    </div>

    {{-- Period Filter Bar with Interactive Dropdown (Hidden on Print) --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm print:hidden">
        <form method="GET" action="{{ route('accounts.reports') }}" id="reportFilterForm" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
                {{-- Period Selector --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">نوع الفترة الزمنية</label>
                    <select name="period" id="periodSelect" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        <option value="daily" @selected($period === 'daily')>📅 تقرير يومي (تحديد يوم)</option>
                        <option value="weekly" @selected($period === 'weekly')>🗓️ تقرير أسبوعي (أسبوع محدد)</option>
                        <option value="monthly" @selected($period === 'monthly')>📊 تقرير شهري (تحديد شهر)</option>
                        <option value="yearly" @selected($period === 'yearly')>📈 تقرير سنوي (تحديد سنة)</option>
                        <option value="custom" @selected($period === 'custom')>⚙️ تقرير مخصص (من - إلى)</option>
                    </select>
                </div>

                {{-- Dynamic Period Input Fields --}}
                {{-- 1. Daily Input --}}
                <div id="fieldDaily" class="period-field {{ $period === 'daily' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">تحديد اليوم</label>
                    <input type="date" name="date" value="{{ $date }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>

                {{-- 2. Weekly Input --}}
                <div id="fieldWeekly" class="period-field {{ $period === 'weekly' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">تاريخ ضمن الأسبوع</label>
                    <input type="date" name="week_date" value="{{ $weekDate }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>

                {{-- 3. Monthly Input --}}
                <div id="fieldMonthly" class="period-field {{ $period === 'monthly' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">تحديد الشهر</label>
                    <input type="month" name="month" value="{{ $month }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>

                {{-- 4. Yearly Input --}}
                <div id="fieldYearly" class="period-field {{ $period === 'yearly' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">تحديد السنة</label>
                    <input type="number" min="2000" max="2100" name="year" value="{{ $year }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>

                {{-- 5. Custom Inputs (From & To) --}}
                <div id="fieldCustomFrom" class="period-field {{ $period === 'custom' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>
                <div id="fieldCustomTo" class="period-field {{ $period === 'custom' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>

                {{-- Currency Filter --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">العملة</label>
                    <select name="currency" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        <option value="all" @selected($currency === 'all')>كل العملات (SYP + USD)</option>
                        <option value="SYP" @selected($currency === 'SYP')>الليرة السورية (ل.س)</option>
                        <option value="USD" @selected($currency === 'USD')>الدولار الأمريكي ($ USD)</option>
                    </select>
                </div>

                {{-- Transaction Type Filter --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">نوع الحركة</label>
                    <select name="type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        <option value="all" @selected($type === 'all')>كل الحركات (صادر ووارد)</option>
                        <option value="income" @selected($type === 'income')>الوارد فقط (إيرادات)</option>
                        <option value="expense" @selected($type === 'expense')>الصادر فقط (مصروفات)</option>
                    </select>
                </div>

                {{-- Submit Button --}}
                <div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 transition">
                        تحديث الكشف
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Official Bank Statement Container --}}
    <div class="bank-statement-sheet rounded-2xl border border-slate-300 bg-white p-6 md:p-8 shadow-sm">
        {{-- Statement Bank-Style Header --}}
        <div class="border-b-2 border-slate-900 pb-6 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-900 text-white text-3xl font-bold shadow-md">
                        🏢
                    </div>
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900">{{ $hotel->name }}</h2>
                        <p class="text-xs text-slate-500 font-semibold tracking-wider uppercase mt-0.5">قسم الإدارة المالية والمحاسبية · FINANCIAL DEPARTMENT</p>
                        <p class="text-xs text-slate-600 mt-1">
                            📍 {{ $hotel->address ?? 'الجمهورية العربية السورية' }} · هاتف: {{ $hotel->phone ?? '—' }}
                        </p>
                    </div>
                </div>

                <div class="text-left" dir="ltr">
                    <div class="inline-block rounded-lg bg-slate-100 px-3 py-1.5 border border-slate-200">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest block">DOCUMENT TYPE</span>
                        <span class="text-sm font-black text-slate-900">ACCOUNT STATEMENT</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 space-y-0.5 font-mono">
                        <div><strong>REF:</strong> STMT-{{ date('Ymd', strtotime($to)) }}-{{ str_pad($hotel->id, 4, '0', STR_PAD_LEFT) }}</div>
                        <div><strong>DATE:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
            </div>

            {{-- Statement Period Badge Info --}}
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 rounded-xl bg-slate-50 p-4 border border-slate-200 text-xs">
                <div>
                    <span class="text-slate-500 block font-medium">نوع التقرير</span>
                    <strong class="text-slate-800 text-sm mt-0.5 block">{{ $periodLabel }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block font-medium">الفترة الزمنية</span>
                    <strong class="text-slate-800 text-sm mt-0.5 block" dir="ltr">{{ $from }} &nbsp;➔&nbsp; {{ $to }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block font-medium">العملة المحددة</span>
                    <strong class="text-slate-800 text-sm mt-0.5 block">
                        {{ $currency === 'all' ? 'جميع العملات (SYP / USD)' : ($currency === 'USD' ? 'الدولار الأمريكي ($)' : 'الليرة السورية (ل.س)') }}
                    </strong>
                </div>
                <div>
                    <span class="text-slate-500 block font-medium">عدد الحركات في الكشف</span>
                    <strong class="text-slate-800 text-sm mt-0.5 block">{{ count($statementRows) }} عملية</strong>
                </div>
            </div>
        </div>

        {{-- Financial Balance Cards (Bank Statement Summary) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            {{-- Opening Balance --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                <span class="text-xs font-bold text-slate-500 block">الرصيد الافتتاحي (السابق)</span>
                @if($currency === 'all')
                    <div class="mt-1 space-y-0.5">
                        <div class="text-base font-black text-slate-800 font-mono">
                            {{ number_format($openingBalanceSYP, 2) }} <span class="text-xs font-normal text-slate-500 font-sans">ل.س</span>
                        </div>
                        <div class="text-base font-black text-blue-700 font-mono">
                            ${{ number_format($openingBalanceUSD, 2) }} <span class="text-xs font-normal text-blue-500 font-sans">USD</span>
                        </div>
                    </div>
                @elseif($currency === 'USD')
                    <div class="text-lg font-black text-blue-700 font-mono mt-1">
                        ${{ number_format($openingBalanceUSD, 2) }} <span class="text-xs font-normal text-slate-500 font-sans">USD</span>
                    </div>
                @else
                    <div class="text-lg font-black text-slate-800 font-mono mt-1">
                        {{ number_format($openingBalanceSYP, 2) }} <span class="text-xs font-normal text-slate-500 font-sans">ل.س</span>
                    </div>
                @endif
                <small class="text-[11px] text-slate-400 block mt-0.5">قبل تاريخ {{ $from }}</small>
            </div>

            {{-- Total Credits / Inflow --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                <span class="text-xs font-bold text-emerald-800 block">إجمالي الوارد (دائن / إيرادات)</span>
                @if($currency === 'all')
                    <div class="mt-1 space-y-0.5">
                        <div class="text-base font-black text-emerald-700 font-mono">
                            +{{ number_format($totalInflowSYP, 2) }} <span class="text-xs font-normal text-emerald-600 font-sans">ل.س</span>
                        </div>
                        <div class="text-base font-black text-emerald-800 font-mono">
                            +${{ number_format($totalInflowUSD, 2) }} <span class="text-xs font-normal text-emerald-600 font-sans">USD</span>
                        </div>
                    </div>
                @elseif($currency === 'USD')
                    <div class="text-lg font-black text-emerald-700 font-mono mt-1">
                        +${{ number_format($totalInflowUSD, 2) }} <span class="text-xs font-normal text-emerald-600 font-sans">USD</span>
                    </div>
                @else
                    <div class="text-lg font-black text-emerald-700 font-mono mt-1">
                        +{{ number_format($totalInflowSYP, 2) }} <span class="text-xs font-normal text-emerald-600 font-sans">ل.س</span>
                    </div>
                @endif
                <small class="text-[11px] text-emerald-600 block mt-0.5">خلال الفترة المحددة</small>
            </div>

            {{-- Total Debits / Outflow --}}
            <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-4">
                <span class="text-xs font-bold text-rose-800 block">إجمالي الصادر (مدين / مصروفات)</span>
                @if($currency === 'all')
                    <div class="mt-1 space-y-0.5">
                        <div class="text-base font-black text-rose-700 font-mono">
                            -{{ number_format($totalOutflowSYP, 2) }} <span class="text-xs font-normal text-rose-600 font-sans">ل.س</span>
                        </div>
                        <div class="text-base font-black text-rose-800 font-mono">
                            -${{ number_format($totalOutflowUSD, 2) }} <span class="text-xs font-normal text-rose-600 font-sans">USD</span>
                        </div>
                    </div>
                @elseif($currency === 'USD')
                    <div class="text-lg font-black text-rose-700 font-mono mt-1">
                        -${{ number_format($totalOutflowUSD, 2) }} <span class="text-xs font-normal text-rose-600 font-sans">USD</span>
                    </div>
                @else
                    <div class="text-lg font-black text-rose-700 font-mono mt-1">
                        -{{ number_format($totalOutflowSYP, 2) }} <span class="text-xs font-normal text-rose-600 font-sans">ل.س</span>
                    </div>
                @endif
                <small class="text-[11px] text-rose-600 block mt-0.5">خلال الفترة المحددة</small>
            </div>

            {{-- Closing Balance --}}
            <div class="rounded-xl border-2 border-blue-600 bg-blue-50/40 p-4">
                <span class="text-xs font-bold text-blue-900 block">الرصيد الختامي الحالي</span>
                @if($currency === 'all')
                    <div class="mt-1 space-y-0.5">
                        <div class="text-base font-black text-slate-900 font-mono">
                            {{ number_format($closingBalanceSYP, 2) }} <span class="text-xs font-normal text-slate-500 font-sans">ل.س</span>
                        </div>
                        <div class="text-base font-black text-blue-800 font-mono">
                            ${{ number_format($closingBalanceUSD, 2) }} <span class="text-xs font-normal text-blue-600 font-sans">USD</span>
                        </div>
                    </div>
                @elseif($currency === 'USD')
                    <div class="text-xl font-black text-blue-800 font-mono mt-1">
                        ${{ number_format($closingBalanceUSD, 2) }} <span class="text-xs font-normal font-sans">USD</span>
                    </div>
                @else
                    <div class="text-xl font-black text-slate-900 font-mono mt-1">
                        {{ number_format($closingBalanceSYP, 2) }} <span class="text-xs font-normal font-sans">ل.س</span>
                    </div>
                @endif
                <small class="text-[11px] text-slate-500 block mt-0.5">في نهاية {{ $to }}</small>
            </div>
        </div>

        {{-- Detailed Bank Statement Ledger Table --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-800 text-white">
                        <th class="py-3 px-3 text-center w-12">#</th>
                        <th class="py-3 px-3">التاريخ</th>
                        <th class="py-3 px-3">رقم المرجع / الفاتورة</th>
                        <th class="py-3 px-3">البيان والتفاصيل</th>
                        <th class="py-3 px-3 text-center">العملة</th>
                        <th class="py-3 px-3 text-left">صادر (مدين)</th>
                        <th class="py-3 px-3 text-left">وارد (دائن)</th>
                        <th class="py-3 px-3 text-left bg-slate-900">الرصيد التراكمي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    {{-- Row 0: Opening Balance Row --}}
                    <tr class="bg-slate-100/80 font-semibold text-slate-700">
                        <td class="py-2.5 px-3 text-center text-slate-400">—</td>
                        <td class="py-2.5 px-3 font-mono" dir="ltr">{{ $from }}</td>
                        <td class="py-2.5 px-3 font-mono text-slate-500">START-BAL</td>
                        <td class="py-2.5 px-3">
                            <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
                                📌 الرصيد الافتتاحي المرحل (Opening Balance)
                            </span>
                            <span class="text-slate-400 text-[11px] block">رصيد الحساب ما قبل تاريخ {{ $from }}</span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-bold text-slate-600">{{ $currency === 'all' ? 'SYP / USD' : $currency }}</td>
                        <td class="py-2.5 px-3 text-left font-mono text-slate-400">—</td>
                        <td class="py-2.5 px-3 text-left font-mono text-slate-400">—</td>
                        <td class="py-2.5 px-3 text-left font-mono font-black text-slate-900 bg-slate-100">
                            @if($currency === 'all')
                                <div class="space-y-0.5">
                                    <div>{{ number_format($openingBalanceSYP, 2) }} <span class="text-[10px] text-slate-500 font-sans font-normal">ل.س</span></div>
                                    <div class="text-blue-700">${{ number_format($openingBalanceUSD, 2) }} <span class="text-[10px] text-blue-500 font-sans font-normal">USD</span></div>
                                </div>
                            @elseif($currency === 'USD')
                                <div class="text-blue-700">${{ number_format($openingBalanceUSD, 2) }} <span class="text-[10px] text-blue-500 font-sans font-normal">USD</span></div>
                            @else
                                <div>{{ number_format($openingBalanceSYP, 2) }} <span class="text-[10px] text-slate-500 font-sans font-normal">ل.س</span></div>
                            @endif
                        </td>
                    </tr>

                    {{-- Transaction Rows --}}
                    @forelse($statementRows as $idx => $row)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $loop->odd ? 'bg-white' : 'bg-slate-50/30' }}">
                            <td class="py-2.5 px-3 text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-mono text-slate-700" dir="ltr">{{ $row['date'] }}</td>
                            <td class="py-2.5 px-3 font-mono">
                                @if($row['invoice'])
                                    <a href="{{ route('invoices.show', $row['invoice']->id) }}" class="text-blue-700 font-semibold hover:underline print:no-underline print:text-black">
                                        #{{ $row['ref'] }}
                                    </a>
                                @else
                                    <span class="text-slate-600 font-medium">{{ $row['ref'] }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-slate-800">{{ $row['title'] }}</div>
                                @if($row['notes'])
                                    <div class="text-[11px] text-slate-500">{{ $row['notes'] }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-block rounded px-2 py-0.5 text-[10px] font-bold {{ $row['currency'] === 'USD' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row['currency'] === 'USD' ? '$ USD' : 'ل.س' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono font-bold text-rose-600">
                                @if($row['outflow'] > 0)
                                    -{{ number_format($row['outflow'], 2) }} <span class="text-[10px] font-sans font-normal">{{ $row['currency'] === 'USD' ? 'USD' : 'ل.س' }}</span>
                                @else
                                    <span class="text-slate-300 font-normal">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono font-bold text-emerald-600">
                                @if($row['inflow'] > 0)
                                    +{{ number_format($row['inflow'], 2) }} <span class="text-[10px] font-sans font-normal">{{ $row['currency'] === 'USD' ? 'USD' : 'ل.س' }}</span>
                                @else
                                    <span class="text-slate-300 font-normal">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono font-bold bg-slate-50/50">
                                @if($currency === 'all')
                                    <div class="space-y-0.5">
                                        <div class="text-slate-900">{{ number_format($row['balance_syp'], 2) }} <span class="text-[10px] text-slate-500 font-sans font-normal">ل.س</span></div>
                                        <div class="text-blue-700">${{ number_format($row['balance_usd'], 2) }} <span class="text-[10px] text-blue-500 font-sans font-normal">USD</span></div>
                                    </div>
                                @elseif($currency === 'USD')
                                    <div class="text-blue-700 font-black">${{ number_format($row['balance_usd'], 2) }} <span class="text-[10px] text-blue-500 font-sans font-normal">USD</span></div>
                                @else
                                    <div class="text-slate-900 font-black">{{ number_format($row['balance_syp'], 2) }} <span class="text-[10px] text-slate-500 font-sans font-normal">ل.س</span></div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                <div class="text-3xl mb-2">📂</div>
                                لا توجد حركات مالية مسجلة خلال هذه الفترة المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-900 text-white font-bold text-xs">
                        <td colspan="5" class="py-3 px-4 text-right">
                            الإجمالي والرصيد الختامي للفترة (Total & Closing Balance)
                        </td>
                        <td class="py-3 px-3 text-left font-mono text-rose-300">
                            @if($currency === 'all')
                                <div>-{{ number_format($totalOutflowSYP, 2) }} <span class="text-[10px] text-rose-200 font-sans">ل.س</span></div>
                                <div>-${{ number_format($totalOutflowUSD, 2) }} <span class="text-[10px] text-rose-200 font-sans">USD</span></div>
                            @elseif($currency === 'USD')
                                -${{ number_format($totalOutflowUSD, 2) }} USD
                            @else
                                -{{ number_format($totalOutflowSYP, 2) }} ل.س
                            @endif
                        </td>
                        <td class="py-3 px-3 text-left font-mono text-emerald-300">
                            @if($currency === 'all')
                                <div>+{{ number_format($totalInflowSYP, 2) }} <span class="text-[10px] text-emerald-200 font-sans">ل.س</span></div>
                                <div>+${{ number_format($totalInflowUSD, 2) }} <span class="text-[10px] text-emerald-200 font-sans">USD</span></div>
                            @elseif($currency === 'USD')
                                +${{ number_format($totalInflowUSD, 2) }} USD
                            @else
                                +{{ number_format($totalInflowSYP, 2) }} ل.س
                            @endif
                        </td>
                        <td class="py-3 px-3 text-left font-mono text-white text-sm bg-black">
                            @if($currency === 'all')
                                <div class="space-y-0.5">
                                    <div class="text-white">{{ number_format($closingBalanceSYP, 2) }} <span class="text-[10px] text-slate-300 font-sans font-normal">ل.س</span></div>
                                    <div class="text-blue-300">${{ number_format($closingBalanceUSD, 2) }} <span class="text-[10px] text-blue-200 font-sans font-normal">USD</span></div>
                                </div>
                            @elseif($currency === 'USD')
                                <div class="text-blue-300">${{ number_format($closingBalanceUSD, 2) }} USD</div>
                            @else
                                <div>{{ number_format($closingBalanceSYP, 2) }} ل.س</div>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Dual Currency Summary Note if All Currencies Selected --}}
        @if($currency === 'all')
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs">
                <div class="font-bold text-slate-700 mb-2">📌 ملخص مفصل بالعملتين المنفصلتين (يتم جمع كل عملة على حدة دون دمجهما):</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-white p-4 border border-slate-200 space-y-1">
                        <div class="font-bold text-slate-800 text-sm flex items-center justify-between border-b pb-1.5 mb-2">
                            <span>🇸🇾 محفظة الليرة السورية (SYP)</span>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">SYP</span>
                        </div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>الرصيد الافتتاحي:</span> <strong class="text-slate-800">{{ number_format($openingBalanceSYP, 2) }} ل.س</strong></div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>إجمالي الوارد (إيرادات):</span> <strong class="text-emerald-700">+{{ number_format($totalInflowSYP, 2) }} ل.س</strong></div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>إجمالي الصادر (مصروفات):</span> <strong class="text-rose-700">-{{ number_format($totalOutflowSYP, 2) }} ل.س</strong></div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>صافي حركة الفترة:</span> <strong class="text-slate-900">{{ number_format($totalInflowSYP - $totalOutflowSYP, 2) }} ل.س</strong></div>
                        <div class="text-slate-900 font-bold flex justify-between pt-1 border-t mt-1 text-sm font-mono"><span>الرصيد الختامي (SYP):</span> <strong class="text-blue-800">{{ number_format($closingBalanceSYP, 2) }} ل.س</strong></div>
                    </div>
                    <div class="rounded-lg bg-white p-4 border border-slate-200 space-y-1">
                        <div class="font-bold text-slate-800 text-sm flex items-center justify-between border-b pb-1.5 mb-2">
                            <span>🇺🇸 محفظة الدولار الأمريكي (USD)</span>
                            <span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700 font-mono">USD</span>
                        </div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>الرصيد الافتتاحي:</span> <strong class="text-slate-800">${{ number_format($openingBalanceUSD, 2) }} USD</strong></div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>إجمالي الوارد (إيرادات):</span> <strong class="text-emerald-700">+${{ number_format($totalInflowUSD, 2) }} USD</strong></div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>إجمالي الصادر (مصروفات):</span> <strong class="text-rose-700">-${{ number_format($totalOutflowUSD, 2) }} USD</strong></div>
                        <div class="text-slate-600 flex justify-between font-mono"><span>صافي حركة الفترة:</span> <strong class="text-slate-900">${{ number_format($totalInflowUSD - $totalOutflowUSD, 2) }} USD</strong></div>
                        <div class="text-slate-900 font-bold flex justify-between pt-1 border-t mt-1 text-sm font-mono"><span>الرصيد الختامي (USD):</span> <strong class="text-blue-800">${{ number_format($closingBalanceUSD, 2) }} USD</strong></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bank Statement Signatures / Endorsement Section --}}
        <div class="mt-10 pt-6 border-t border-slate-200 grid grid-cols-3 gap-6 text-center text-xs text-slate-600">
            <div>
                <p class="font-bold text-slate-700">المحاسب المعتمد</p>
                <div class="mt-8 border-b border-dashed border-slate-400 w-36 mx-auto"></div>
                <span class="text-[10px] text-slate-400 mt-1 block">التوقيع والاسم</span>
            </div>
            <div>
                <p class="font-bold text-slate-700">مدير الفندق</p>
                <div class="mt-8 border-b border-dashed border-slate-400 w-36 mx-auto"></div>
                <span class="text-[10px] text-slate-400 mt-1 block">الاعتماد والختم</span>
            </div>
            <div>
                <p class="font-bold text-slate-700">ختم التدقيق والرقابة المالية</p>
                <div class="mt-8 flex items-center justify-center">
                    <div class="h-12 w-28 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center text-[10px] text-slate-400">
                        الختم الرسمي
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Interactive Dynamic Field Selector Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const periodSelect = document.getElementById('periodSelect');
    const fieldDaily = document.getElementById('fieldDaily');
    const fieldWeekly = document.getElementById('fieldWeekly');
    const fieldMonthly = document.getElementById('fieldMonthly');
    const fieldYearly = document.getElementById('fieldYearly');
    const fieldCustomFrom = document.getElementById('fieldCustomFrom');
    const fieldCustomTo = document.getElementById('fieldCustomTo');

    function updatePeriodFields() {
        const value = periodSelect.value;
        fieldDaily.classList.add('hidden');
        fieldWeekly.classList.add('hidden');
        fieldMonthly.classList.add('hidden');
        fieldYearly.classList.add('hidden');
        fieldCustomFrom.classList.add('hidden');
        fieldCustomTo.classList.add('hidden');

        if (value === 'daily') {
            fieldDaily.classList.remove('hidden');
        } else if (value === 'weekly') {
            fieldWeekly.classList.remove('hidden');
        } else if (value === 'monthly') {
            fieldMonthly.classList.remove('hidden');
        } else if (value === 'yearly') {
            fieldYearly.classList.remove('hidden');
        } else if (value === 'custom') {
            fieldCustomFrom.classList.remove('hidden');
            fieldCustomTo.classList.remove('hidden');
        }
    }

    periodSelect.addEventListener('change', updatePeriodFields);
});
</script>

<style>
@media print {
    header, footer, nav, .print\:hidden {
        display: none !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .bank-statement-sheet {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    table {
        font-size: 10px !important;
    }
}
</style>
@endsection