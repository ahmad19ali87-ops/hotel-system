@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl py-6 px-4">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">
                <a href="{{ route('platform.hotels.index') }}" class="hover:underline text-indigo-600">لوحة تحكم المنصة</a>
                <span>/</span>
                <span class="text-slate-500">نظام CRM والاشتراكات المدمج</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-2">
                <span>💼 المركز الشامل لإدارة العملاء والاشتراكات (Platform CRM)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                إدارة الفنادق المشتركة، الباقات والاشتراكات، الفواتير، تذاكر الدعم الفني، مسار المبيعات، فريق العمل، والحملات التسويقية
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#assignSubModal">
                <span>➕</span>
                <span>تعيين / تجديد اشتراك فندق</span>
            </button>
            <button type="button" class="btn btn-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#newLeadModal">
                <span>🎯</span>
                <span>إضافة عميل محتمل للمبيعات</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-5">✓</span>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Top SaaS & CRM Financial Metrics Bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        {{-- MRR USD --}}
        <div class="card p-3 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <span class="text-[11px] font-bold text-slate-500 block">الإيراد الشهري المتكرر (MRR USD)</span>
            <div class="text-xl font-black text-emerald-600 mt-1">${{ number_format($mrrUSD, 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">ARR التقديري: ${{ number_format($arrUSD, 2) }}</div>
        </div>

        {{-- MRR SYP --}}
        <div class="card p-3 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <span class="text-[11px] font-bold text-slate-500 block">الإيراد الشهري المتكرر (MRR ل.س)</span>
            <div class="text-lg font-black text-blue-600 mt-1">{{ number_format($mrrSYP) }} <span class="text-xs">ل.س</span></div>
            <div class="text-[10px] text-slate-400 mt-0.5">ARR التقديري: {{ number_format($arrSYP) }} ل.س</div>
        </div>

        {{-- Active Hotels & Subscriptions --}}
        <div class="card p-3 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <span class="text-[11px] font-bold text-slate-500 block">الفنادق المشتركة والنشطة</span>
            <div class="text-xl font-black text-slate-800 mt-1">{{ $activeSubscriptionsCount }} / {{ $totalHotels }} فندق</div>
            <div class="text-[10px] text-amber-600 mt-0.5 font-bold">
                @if(count($expiringSubscriptions) > 0)
                    ⚠️ {{ count($expiringSubscriptions) }} ينتهي خلال 7 أيام!
                @else
                    كل الاشتراكات الحالية مستقرة
                @endif
            </div>
        </div>

        {{-- Support SLA --}}
        <div class="card p-3 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <span class="text-[11px] font-bold text-slate-500 block">تذاكر الدعم الفني المفتوحة</span>
            <div class="text-xl font-black text-indigo-600 mt-1">{{ $openTicketsCount }} تذكرة</div>
            <div class="text-[10px] text-rose-600 mt-0.5 font-bold">
                {{ $urgentTicketsCount }} تذاكر طارئة بحاجة رد فوري
            </div>
        </div>

        {{-- Sales Pipeline --}}
        <div class="card p-3 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <span class="text-[11px] font-bold text-slate-500 block">مسار المبيعات والصفقات (Leads)</span>
            <div class="text-xl font-black text-amber-600 mt-1">{{ $activeLeadsCount }} صفقة جارية</div>
            <div class="text-[10px] text-emerald-600 mt-0.5 font-bold">
                {{ $wonLeadsCount }} صفقة رابحة تم تحويلها
            </div>
        </div>
    </div>

    {{-- CRM Tabs Header --}}
    <div class="border-b border-slate-200 mb-6 bg-white rounded-2xl p-2 shadow-sm">
        <ul class="nav nav-pills flex flex-wrap gap-1 text-sm font-bold">
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'overview']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'overview' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    📊 نظرة عامة وتقارير SaaS
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'hotels']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'hotels' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    🏨 الفنادق المشتركة والعملاء ({{ $totalHotels }})
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'subscriptions']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'subscriptions' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    💳 الباقات والاشتراكات ({{ count($plans) }})
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'invoices']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'invoices' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    📄 فواتير المنصة والتحصيل
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'support']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'support' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    🎧 تذاكر الدعم الفني
                    @if($openTicketsCount > 0)
                        <span class="badge bg-rose-500 text-white ms-1 text-[10px] rounded-full">{{ $openTicketsCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'sales']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'sales' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    🎯 المبيعات ومسار الصفقات
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'team']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'team' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    👥 فريق المنصة ({{ count($team) }})
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('platform.crm.index', ['tab' => 'marketing']) }}" class="nav-link rounded-xl py-2 px-3.5 {{ $tab === 'marketing' ? 'active bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    📣 التسويق وأكواد الخصم ({{ count($campaigns) }})
                </a>
            </li>
        </ul>
    </div>

    {{-- TAB 1: OVERVIEW & REPORTS --}}
    @if($tab === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Expiring Soon Alert Box --}}
            <div class="lg:col-span-2 card shadow-sm border border-slate-200 bg-white rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>⏰ اشتراكات تقترب من الانتهاء (خلال 7 أيام)</span>
                        <span class="badge bg-amber-100 text-amber-800 border border-amber-300">{{ count($expiringSubscriptions) }}</span>
                    </h3>
                    <span class="text-xs text-slate-400">تحتاج تواصل ومتابعة تجديد</span>
                </div>

                @if(count($expiringSubscriptions) > 0)
                    <div class="space-y-3">
                        @foreach($expiringSubscriptions as $expSub)
                            <div class="flex items-center justify-between p-3.5 rounded-xl border border-amber-200 bg-amber-50/50">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $expSub->hotel?->name }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        الباقة: <strong>{{ $expSub->plan?->name }}</strong> · ينتهي بتاريخ: <strong class="text-amber-800">{{ $expSub->expires_at?->format('Y-m-d') }}</strong> (متبقي {{ $expSub->days_remaining }} أيام)
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="btn btn-sm btn-outline-primary font-bold text-xs" data-bs-toggle="modal" data-bs-target="#assignSubModal" onclick="document.getElementById('modal_hotel_id').value = '{{ $expSub->hotel_id }}'; document.getElementById('modal_plan_id').value = '{{ $expSub->plan_id }}';">
                                        🔄 تجديد فوري
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-slate-400">
                        <div class="text-3xl mb-1">🎉</div>
                        <div class="font-bold text-slate-600">لا توجد اشتراكات تقترب من الانتهاء خلال هذا الأسبوع.</div>
                    </div>
                @endif
            </div>

            {{-- Platform Revenue Summary --}}
            <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl p-5">
                <h3 class="text-base font-bold text-slate-900 mb-3 border-b pb-3">💰 ملخص إيرادات الاشتراكات المحصلة</h3>
                <div class="space-y-4">
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                        <span class="text-xs text-emerald-800 font-bold block">إجمالي التحصيل بالدولار الأمريكي</span>
                        <div class="text-2xl font-black text-emerald-700 mt-1">${{ number_format($totalCollectedUSD, 2) }} USD</div>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl border border-blue-200">
                        <span class="text-xs text-blue-800 font-bold block">إجمالي التحصيل بالليرة السورية</span>
                        <div class="text-xl font-black text-blue-700 mt-1">{{ number_format($totalCollectedSYP) }} ل.س</div>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                        <span>فواتير بانتظار السداد:</span>
                        <span class="font-black text-rose-600">{{ $unpaidInvoicesCount }} فاتورة</span>
                    </div>
                </div>
            </div>

            {{-- Plans Distribution --}}
            <div class="lg:col-span-3 card shadow-sm border border-slate-200 bg-white rounded-2xl p-5">
                <h3 class="text-base font-bold text-slate-900 mb-4 border-b pb-3">📦 توزيع باقات الاشتراك على الفنادق النشطة</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($plans as $p)
                        @php
                            $subsCount = $subscriptions->where('plan_id', $p->id)->where('status', 'active')->count();
                        @endphp
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900">{{ $p->name }}</span>
                                    @if($p->badge)
                                        <span class="badge bg-indigo-100 text-indigo-800 text-[10px]">{{ $p->badge }}</span>
                                    @endif
                                </div>
                                <div class="text-2xl font-black text-indigo-700 mt-2">{{ $subsCount }} فندق مشترك</div>
                                <div class="text-xs text-slate-500 mt-1">
                                    {{ $p->price_monthly_usd > 0 ? '$' . $p->price_monthly_usd . ' USD / شهر' : number_format($p->price_monthly_syp) . ' ل.س' }}
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-200 text-[11px] text-slate-400">
                                الحد الأقصى للغرف: {{ $p->max_rooms == -1 ? 'غير محدود' : $p->max_rooms }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 2: HOTELS & CLIENTS --}}
    @if($tab === 'hotels')
        <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-black text-slate-900 m-0">قائمة الفنادق المشتركة والعملاء</h3>
                <button class="btn btn-primary btn-sm font-bold text-xs" data-bs-toggle="modal" data-bs-target="#assignSubModal">
                    ➕ تعيين اشتراك جديد
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                        <tr>
                            <th class="ps-4">الفندق والمدينة</th>
                            <th>الاشتراك الحالي</th>
                            <th>فترة الصلاحية</th>
                            <th>الأيام المتبقية</th>
                            <th>حالة الاشتراك</th>
                            <th>الغرف والنزلاء</th>
                            <th class="text-end pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($hotelsWithSubs as $h)
                            @php
                                $sub = $h->currentSubscription;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="font-bold text-slate-900">{{ $h->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $h->province ?: 'سوريا' }} · {{ $h->phone ?: '—' }}</div>
                                </td>
                                <td>
                                    @if($sub && $sub->plan)
                                        <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-2 py-1">
                                            {{ $sub->plan->name }}
                                        </span>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $sub->billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }} · {{ $sub->currency }}</div>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-500 border">بدون باقة محددة</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub)
                                        <div class="text-xs">
                                            من: {{ $sub->starts_at?->format('Y-m-d') }}<br>
                                            إلى: <strong class="text-slate-800">{{ $sub->expires_at?->format('Y-m-d') }}</strong>
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub)
                                        @if($sub->is_expired)
                                            <span class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold">منتهي الصلاحية</span>
                                        @elseif($sub->is_expiring_soon)
                                            <span class="badge bg-amber-100 text-amber-800 border border-amber-300 font-bold">باقي {{ $sub->days_remaining }} أيام ⚠️</span>
                                        @else
                                            <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">باقي {{ $sub->days_remaining }} يوم</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub)
                                        <span class="badge {{ $sub->status_badge_class }}">{{ $sub->status_label }}</span>
                                    @else
                                        <span class="badge bg-secondary">غير مسجل</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs">
                                        الغرف: <strong>{{ $h->rooms_count }}</strong>
                                        @if($sub?->plan && $sub->plan->max_rooms != -1)
                                            / {{ $sub->plan->max_rooms }}
                                        @endif
                                        <br>
                                        الحجوزات: {{ $h->bookings_count }}
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-outline-primary btn-sm font-bold text-xs" data-bs-toggle="modal" data-bs-target="#assignSubModal" onclick="document.getElementById('modal_hotel_id').value = '{{ $h->id }}';">
                                        🔄 ترقية / تجديد
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400">لا توجد فنادق مسجلة في النظام.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($hotelsWithSubs->hasPages())
                <div class="p-3 border-t bg-slate-50">
                    {{ $hotelsWithSubs->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 3: SUBSCRIPTIONS & PLANS --}}
    @if($tab === 'subscriptions')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- Plans Cards --}}
            @foreach($plans as $plan)
                <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-black text-slate-900 m-0">{{ $plan->name }}</h3>
                            @if($plan->badge)
                                <span class="badge bg-indigo-100 text-indigo-800 font-bold">{{ $plan->badge }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 min-h-[36px]">{{ $plan->description }}</p>

                        <div class="my-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="flex items-baseline justify-between mb-1">
                                <span class="text-xs text-slate-500 font-bold">الاشتراك الشهري:</span>
                                <span class="font-black text-slate-900">${{ $plan->price_monthly_usd }} USD <span class="text-xs text-slate-400">({{ number_format($plan->price_monthly_syp) }} ل.س)</span></span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-slate-500 font-bold">الاشتراك السنوي:</span>
                                <span class="font-black text-emerald-700">${{ $plan->price_yearly_usd }} USD <span class="text-xs text-slate-400">({{ number_format($plan->price_yearly_syp) }} ل.س)</span></span>
                            </div>
                        </div>

                        <div class="text-xs font-bold text-slate-700 mb-2">المزايا والحدود:</div>
                        <ul class="text-xs text-slate-600 space-y-1.5 mb-4">
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-500">✓</span>
                                <span>عدد الغرف: <strong>{{ $plan->max_rooms == -1 ? 'غير محدود' : $plan->max_rooms . ' غرفة' }}</strong></span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-500">✓</span>
                                <span>حسابات المستخدمين: <strong>{{ $plan->max_users == -1 ? 'غير محدود' : $plan->max_users . ' مستخدم' }}</strong></span>
                            </li>
                            @foreach($plan->features_list as $feat)
                                <li class="flex items-center gap-1.5">
                                    <span class="text-emerald-500">✓</span>
                                    <span>{{ $feat }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-between text-xs">
                        <span class="badge {{ $plan->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                            {{ $plan->is_active ? 'باقة نشطة ومتاحة' : 'معطلة' }}
                        </span>
                        <button class="btn btn-sm btn-outline-secondary font-bold text-xs" data-bs-toggle="modal" data-bs-target="#assignSubModal" onclick="document.getElementById('modal_plan_id').value = '{{ $plan->id }}';">
                            تعيين لفندق ➕
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Add New Plan Form Button --}}
        <div class="text-center">
            <button class="btn btn-outline-primary font-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newPlanModal">
                ➕ إنشاء باقة اشتراك جديدة في النظام
            </button>
        </div>
    @endif

    {{-- TAB 4: PLATFORM INVOICES & BILLING --}}
    @if($tab === 'invoices')
        <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-black text-slate-900 m-0">سجل فواتير الاشتراكات والتحصيل المالي للمنصة</h3>
                <button class="btn btn-primary btn-sm font-bold text-xs" data-bs-toggle="modal" data-bs-target="#newInvoiceModal">
                    ➕ إصدار فاتورة اشتراك جديدة
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                        <tr>
                            <th class="ps-4">رقم الفاتورة</th>
                            <th>الفندق</th>
                            <th>الباقة</th>
                            <th>المبلغ والعملة</th>
                            <th>طريقة الدفع</th>
                            <th>الحالة</th>
                            <th>تاريخ التحصيل</th>
                            <th class="text-end pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="ps-4 font-mono font-bold text-slate-900">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->hotel?->name }}</td>
                                <td>{{ $inv->plan?->name }}</td>
                                <td>
                                    @if($inv->currency === 'USD')
                                        <span class="font-bold text-slate-900">${{ number_format($inv->amount, 2) }} USD</span>
                                    @else
                                        <span class="font-bold text-slate-900">{{ number_format($inv->amount) }} ل.س</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 border">{{ $inv->payment_method }}</span>
                                </td>
                                <td>
                                    @if($inv->status === 'paid')
                                        <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold">مسددة ✔</span>
                                    @else
                                        <span class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold">غير مسددة</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $inv->paid_at ? $inv->paid_at->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="text-end pe-4">
                                    @if($inv->status === 'unpaid')
                                        <form action="{{ route('platform.crm.invoices.pay', $inv) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد تحصيل وسداد هذه الفاتورة؟');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success font-bold text-xs">
                                                ✔ تأكيد التحصيل
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-emerald-600 font-bold">محصلة</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">لا توجد فواتير اشتراك مسجلة حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="p-3 border-t bg-slate-50">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 5: SUPPORT TICKETS --}}
    @if($tab === 'support')
        <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-black text-slate-900 m-0">صندوق تذاكر الدعم الفني للمنصة</h3>
                <div class="flex items-center gap-2">
                    <a href="{{ route('platform.crm.index', ['tab' => 'support', 'ticket_status' => 'open']) }}" class="btn btn-outline-danger btn-sm font-bold text-xs">
                        المفتوحة فقط
                    </a>
                    <a href="{{ route('platform.crm.index', ['tab' => 'support']) }}" class="btn btn-outline-secondary btn-sm font-bold text-xs">
                        الكل
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                        <tr>
                            <th class="ps-4">رقم التذكرة</th>
                            <th>الفندق والعميل</th>
                            <th>الموضوع والبيان</th>
                            <th>القسم</th>
                            <th>الأولوية</th>
                            <th>الحالة</th>
                            <th>آخر رد</th>
                            <th class="text-end pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tickets as $tck)
                            <tr>
                                <td class="ps-4 font-mono font-bold text-indigo-700">{{ $tck->ticket_number }}</td>
                                <td>
                                    <div class="font-bold text-slate-900">{{ $tck->hotel?->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $tck->creator?->name }}</div>
                                </td>
                                <td>
                                    <div class="font-bold text-slate-800">{{ $tck->subject }}</div>
                                    <div class="text-xs text-slate-500 truncate max-w-xs">{{ $tck->latestMessage?->message }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 border">{{ $tck->department_label }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $tck->priority_badge_class }}">{{ $tck->priority_label }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $tck->status_badge_class }}">{{ $tck->status_label }}</span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $tck->last_reply_at ? $tck->last_reply_at->diffForHumans() : '—' }}
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('platform.crm.tickets.show', $tck) }}" class="btn btn-primary btn-sm font-bold text-xs">
                                        💬 فتح والمحادثة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">لا توجد تذاكر دعم فني في هذا القسم.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
                <div class="p-3 border-t bg-slate-50">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 6: SALES PIPELINE & LEADS --}}
    @if($tab === 'sales')
        <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-black text-slate-900 m-0">مسار المبيعات والعملاء المحتملين (Sales Pipeline)</h3>
                <button class="btn btn-primary btn-sm font-bold text-xs" data-bs-toggle="modal" data-bs-target="#newLeadModal">
                    ➕ إضافة عميل محتمل
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                        <tr>
                            <th class="ps-4">اسم الفندق / العميل</th>
                            <th>بيانات الاتصال</th>
                            <th>المصدر والمحافظة</th>
                            <th>الإيراد المتوقع</th>
                            <th>المسؤول من الفريق</th>
                            <th>مرحلة الصفقة</th>
                            <th class="text-end pe-4">تحديث المرحلة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($leads as $lead)
                            <tr>
                                <td class="ps-4">
                                    <div class="font-bold text-slate-900">{{ $lead->hotel_name }}</div>
                                    <div class="text-xs text-slate-500">المسؤول: {{ $lead->name }}</div>
                                </td>
                                <td>
                                    <div class="text-xs">
                                        <div>📞 {{ $lead->phone ?: '—' }}</div>
                                        <div class="text-slate-400">✉️ {{ $lead->email ?: '—' }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-xs">
                                        <span class="badge bg-slate-100 text-slate-700 border">{{ $lead->source }}</span>
                                        <div class="text-slate-500 mt-0.5">{{ $lead->province }}</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-bold text-slate-900">${{ number_format($lead->expected_revenue) }} {{ $lead->currency }}</span>
                                </td>
                                <td class="text-xs text-slate-700 font-medium">
                                    {{ $lead->assignedAgent?->name ?? 'غير معين' }}
                                </td>
                                <td>
                                    <span class="badge {{ $lead->status_badge_class }}">{{ $lead->status_label }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('platform.crm.leads.status', $lead) }}" method="POST" class="d-inline-flex align-items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="form-select form-select-sm text-xs py-1" onchange="this.form.submit()">
                                            <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>جديد</option>
                                            <option value="contacted" {{ $lead->status === 'contacted' ? 'selected' : '' }}>تم التواصل</option>
                                            <option value="demo_scheduled" {{ $lead->status === 'demo_scheduled' ? 'selected' : '' }}>جدولة Demo</option>
                                            <option value="proposal_sent" {{ $lead->status === 'proposal_sent' ? 'selected' : '' }}>إرسال العرض</option>
                                            <option value="won_converted" {{ $lead->status === 'won_converted' ? 'selected' : '' }}>تمت الصفقة 🎉</option>
                                            <option value="lost" {{ $lead->status === 'lost' ? 'selected' : '' }}>خاسرة</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400">لا يوجد عملاء محتملون في مسار المبيعات حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leads->hasPages())
                <div class="p-3 border-t bg-slate-50">
                    {{ $leads->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 7: TEAM --}}
    @if($tab === 'team')
        <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-black text-slate-900 m-0">فريق إدارة وتشغيل المنصة والدعم</h3>
                <a href="{{ route('platform.admins.create') }}" class="btn btn-primary btn-sm font-bold text-xs">
                    ➕ إضافة عضو جديد للفريق
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                        <tr>
                            <th class="ps-4">العضو</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور والصلاحية</th>
                            <th>الهاتف</th>
                            <th>حالة الحساب</th>
                            <th class="text-end pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($team as $member)
                            <tr>
                                <td class="ps-4">
                                    <div class="font-bold text-slate-900">{{ $member->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $member->job_title ?: 'طاقم المنصة' }}</div>
                                </td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold">
                                        {{ $member->role }}
                                    </span>
                                </td>
                                <td>{{ $member->phone ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300">نشط</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('platform.admins.edit', $member) }}" class="btn btn-outline-secondary btn-sm text-xs font-bold">
                                        ✏️ تعديل
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- TAB 8: MARKETING & CAMPAIGNS --}}
    @if($tab === 'marketing')
        <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-black text-slate-900 m-0">الحملات التسويقية وأكواد الخصم والترويج</h3>
                <button class="btn btn-primary btn-sm font-bold text-xs" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                    ➕ إنشاء كود خصم / حملة
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                        <tr>
                            <th class="ps-4">اسم الحملة</th>
                            <th>كود الخصم (Promo Code)</th>
                            <th>نسبة / قيمة الخصم</th>
                            <th>الاستخدامات</th>
                            <th>فترة الصلاحية</th>
                            <th>الحالة</th>
                            <th class="text-end pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($campaigns as $camp)
                            <tr>
                                <td class="ps-4">
                                    <div class="font-bold text-slate-900">{{ $camp->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $camp->description }}</div>
                                </td>
                                <td>
                                    <span class="font-mono font-bold bg-slate-100 border px-2 py-1 rounded text-indigo-700">{{ $camp->code }}</span>
                                </td>
                                <td>
                                    @if($camp->discount_percent > 0)
                                        <span class="font-black text-emerald-700">{{ $camp->discount_percent }}% خصم</span>
                                    @else
                                        <span class="font-black text-emerald-700">${{ $camp->discount_fixed }} {{ $camp->currency }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-xs font-bold">{{ $camp->used_count }} / {{ $camp->max_uses == -1 ? 'غير محدود' : $camp->max_uses }}</span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $camp->starts_at?->format('Y-m-d') }} ⬅️ {{ $camp->ends_at?->format('Y-m-d') ?: 'مستمرة' }}
                                </td>
                                <td>
                                    <span class="badge {{ $camp->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $camp->is_active ? 'فعالة' : 'معطلة' }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('platform.crm.campaigns.toggle', $camp) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary font-bold text-xs">
                                            {{ $camp->is_active ? 'تعطيل' : 'تفعيل' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400">لا توجد حملات تسويقية مسجلة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- MODAL: Assign / Renew Subscription --}}
<div class="modal fade" id="assignSubModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl shadow-lg border-0">
            <form action="{{ route('platform.crm.subscriptions.assign') }}" method="POST">
                @csrf
                <div class="modal-header border-b px-6 py-4">
                    <h5 class="modal-title font-bold text-slate-900">تعيين أو تجديد اشتراك لفندق</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">الفندق المشترك *</label>
                        <select name="hotel_id" id="modal_hotel_id" class="form-select rounded-xl" required>
                            <option value="">-- اختر الفندق --</option>
                            @foreach(\App\Models\Hotel::orderBy('name')->get() as $ht)
                                <option value="{{ $ht->id }}">{{ $ht->name }} ({{ $ht->province }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">باقة الاشتراك *</label>
                        <select name="plan_id" id="modal_plan_id" class="form-select rounded-xl" required>
                            @foreach($plans as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->name }} (${{ $pl->price_monthly_usd }} / شهر)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">دورة السداد *</label>
                            <select name="billing_cycle" class="form-select rounded-xl" required>
                                <option value="monthly">شهري</option>
                                <option value="yearly">سنوي</option>
                                <option value="lifetime">دائم / مدى الحياة</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">المدة (أشهر) *</label>
                            <input type="number" name="duration_months" value="1" min="1" max="120" class="form-control rounded-xl" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">المبلغ المسدد *</label>
                            <input type="number" step="any" name="price_paid" value="65" class="form-control rounded-xl" required>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">العملة *</label>
                            <select name="currency" class="form-select rounded-xl" required>
                                <option value="USD">$ USD</option>
                                <option value="SYP">ل.س (سوري)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">حالة الاشتراك *</label>
                        <select name="status" class="form-select rounded-xl" required>
                            <option value="active">نشط وساري (Active)</option>
                            <option value="trial">فترة تجريبية (Trial)</option>
                            <option value="grace_period">فترة سماح (Grace)</option>
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="create_invoice" value="1" id="createInvCheck" checked>
                        <label class="form-check-label text-xs font-bold text-slate-700" for="createInvCheck">
                            إصدار فاتورة اشتراك رسمية مسددة فوراً
                        </label>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">ملاحظات داخلية</label>
                        <textarea name="notes" class="form-control rounded-xl text-xs" rows="2" placeholder="أي تفاصيل أو بنود خاصة بالاتفاقية..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-t px-6 py-3">
                    <button type="button" class="btn btn-light btn-sm font-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm font-bold">تأكيد تفعيل الاشتراك</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Add New Lead --}}
<div class="modal fade" id="newLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl shadow-lg border-0">
            <form action="{{ route('platform.crm.leads.store') }}" method="POST">
                @csrf
                <div class="modal-header border-b px-6 py-4">
                    <h5 class="modal-title font-bold text-slate-900">إضافة عميل محتمل لمسار المبيعات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">اسم الفندق / المنشأة *</label>
                            <input type="text" name="hotel_name" class="form-control rounded-xl" required placeholder="فندق الشام بالاس">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">اسم المدير / المسؤول *</label>
                            <input type="text" name="name" class="form-control rounded-xl" required placeholder="أحمد الخطيب">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">رقم الهاتف *</label>
                            <input type="text" name="phone" class="form-control rounded-xl" placeholder="0944112233">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control rounded-xl" placeholder="manager@hotel.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">المحافظة</label>
                            <input type="text" name="province" class="form-control rounded-xl" placeholder="دمشق">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">مصدر العميل</label>
                            <select name="source" class="form-select rounded-xl">
                                <option value="website">تسجيل عبر الموقع</option>
                                <option value="phone">اتصال هاتفي مباشر</option>
                                <option value="referral">توصية من فندق آخر</option>
                                <option value="visit">زيارة ميدانية</option>
                                <option value="social_media">وسائل التواصل</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">المرحلة الحالية</label>
                            <select name="status" class="form-select rounded-xl">
                                <option value="new">عميل جديد</option>
                                <option value="contacted">تم التواصل</option>
                                <option value="demo_scheduled">جدولة Demo</option>
                                <option value="proposal_sent">إرسال عرض مالي</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">الإيراد المتوقع ($)</label>
                            <input type="number" name="expected_revenue" value="650" class="form-control rounded-xl">
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">ملاحظات واحتياجات الفندق</label>
                        <textarea name="notes" class="form-control rounded-xl text-xs" rows="2" placeholder="عدد الغرف، الأنظمة الحالية، موعد المتابعة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-t px-6 py-3">
                    <button type="button" class="btn btn-light btn-sm font-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm font-bold">إضافة لمسار المبيعات</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: New Plan --}}
<div class="modal fade" id="newPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl shadow-lg border-0">
            <form action="{{ route('platform.crm.plans.store') }}" method="POST">
                @csrf
                <div class="modal-header border-b px-6 py-4">
                    <h5 class="modal-title font-bold text-slate-900">إنشاء باقة اشتراك جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">اسم الباقة *</label>
                        <input type="text" name="name" class="form-control rounded-xl" required placeholder="الباقة الماسية">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">السعر الشهري ($ USD) *</label>
                            <input type="number" step="any" name="price_monthly_usd" value="80" class="form-control rounded-xl" required>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">السعر السنوي ($ USD) *</label>
                            <input type="number" step="any" name="price_yearly_usd" value="800" class="form-control rounded-xl" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">السعر الشهري (ل.س) *</label>
                            <input type="number" step="any" name="price_monthly_syp" value="1200000" class="form-control rounded-xl" required>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">السعر السنوي (ل.س) *</label>
                            <input type="number" step="any" name="price_yearly_syp" value="12000000" class="form-control rounded-xl" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">الحد الأقصى للغرف (-1 = غير محدود)</label>
                            <input type="number" name="max_rooms" value="70" class="form-control rounded-xl" required>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">الحد الأقصى للمستخدمين</label>
                            <input type="number" name="max_users" value="15" class="form-control rounded-xl" required>
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">قائمة المزايا (بند في كل سطر)</label>
                        <textarea name="features" class="form-control rounded-xl text-xs" rows="3" placeholder="أتمتة شاملة للحجوزات&#10;تطبيق سطح المكتب والموبايل&#10;دعم VIP"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="planActiveCheck" checked>
                        <label class="form-check-label text-xs font-bold text-slate-700" for="planActiveCheck">
                            تفعيل الباقة وإتاحتها للاشتراك فوراً
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-t px-6 py-3">
                    <button type="button" class="btn btn-light btn-sm font-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm font-bold">حفظ الباقة</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: New Campaign --}}
<div class="modal fade" id="newCampaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl shadow-lg border-0">
            <form action="{{ route('platform.crm.campaigns.store') }}" method="POST">
                @csrf
                <div class="modal-header border-b px-6 py-4">
                    <h5 class="modal-title font-bold text-slate-900">إنشاء كود خصم / حملة تسويقية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">عنوان الحملة *</label>
                        <input type="text" name="title" class="form-control rounded-xl" required placeholder="خصم الصيف للفنادق الجديدة">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">كود الخصم (Promo Code) *</label>
                            <input type="text" name="code" class="form-control rounded-xl font-mono" required placeholder="SUMMER20">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">نسبة الخصم (%)</label>
                            <input type="number" step="any" name="discount_percent" value="20" class="form-control rounded-xl">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">تاريخ البداية</label>
                            <input type="date" name="starts_at" value="{{ now()->toDateString() }}" class="form-control rounded-xl">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">تاريخ الانتهاء</label>
                            <input type="date" name="ends_at" class="form-control rounded-xl">
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">الحد الأقصى لمرات الاستخدام (-1 = غير محدود)</label>
                        <input type="number" name="max_uses" value="50" class="form-control rounded-xl">
                    </div>

                    <input type="hidden" name="currency" value="USD">
                    <input type="hidden" name="type" value="discount_coupon">
                </div>
                <div class="modal-footer border-t px-6 py-3">
                    <button type="button" class="btn btn-light btn-sm font-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm font-bold">إطلاق الحملة</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: New Subscription Invoice --}}
<div class="modal fade" id="newInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl shadow-lg border-0">
            <form action="{{ route('platform.crm.invoices.store') }}" method="POST">
                @csrf
                <div class="modal-header border-b px-6 py-4">
                    <h5 class="modal-title font-bold text-slate-900">إصدار فاتورة اشتراك جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">الفندق *</label>
                        <select name="hotel_id" class="form-select rounded-xl" required>
                            @foreach(\App\Models\Hotel::orderBy('name')->get() as $ht)
                                <option value="{{ $ht->id }}">{{ $ht->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">الباقة *</label>
                        <select name="plan_id" class="form-select rounded-xl" required>
                            @foreach($plans as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">المبلغ *</label>
                            <input type="number" step="any" name="amount" value="65" class="form-control rounded-xl" required>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">العملة *</label>
                            <select name="currency" class="form-select rounded-xl" required>
                                <option value="USD">$ USD</option>
                                <option value="SYP">ل.س (سوري)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">طريقة الدفع *</label>
                            <select name="payment_method" class="form-select rounded-xl" required>
                                <option value="bank_transfer">حوالة بنكية</option>
                                <option value="cash">نقداً (كاش)</option>
                                <option value="online">بطاقة / دفع إلكتروني</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">حالة الفاتورة *</label>
                            <select name="status" class="form-select rounded-xl" required>
                                <option value="paid">مسددة فوراً ✔</option>
                                <option value="unpaid">غير مسددة (بانتظار التحصيل)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-t px-6 py-3">
                    <button type="button" class="btn btn-light btn-sm font-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm font-bold">إصدار الفاتورة</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

