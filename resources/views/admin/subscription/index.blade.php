@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl py-6 px-4">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <span>إدارة الفندق</span>
                <span>/</span>
                <span class="text-slate-500">خطة واشتراك الفندق</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-2">
                <span>💳 باقة واشتراك الفندق في النظام</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                استعراض تفاصيل باقتك الحالية، مدة الصلاحية، حدود الغرف، فواتير الاشتراك السابقة، وطلب الترقية
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.support.create') }}" class="btn btn-outline-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5">
                <span>🎧</span>
                <span>طلب مساعدة أو دعم فني</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Urgent Expiration Warning Banner --}}
    @if($subscription)
        @if($subscription->is_expired)
            <div class="alert alert-danger rounded-2xl shadow-sm border-2 border-rose-300 p-4 mb-6">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h4 class="font-black text-rose-950 text-base mb-1">لقد انتهت صلاحية اشتراك فندقكم!</h4>
                        <p class="text-xs text-rose-900 m-0">
                            انتهى الاشتراك بتاريخ <strong>{{ $subscription->expires_at?->format('Y-m-d') }}</strong>. يرجى تجديد الاشتراك فوراً لضمان عدم توقف الخدمات واستمرار الربط مع تطبيق الكومبيوتر والجوال.
                        </p>
                    </div>
                </div>
            </div>
        @elseif($subscription->is_expiring_soon)
            <div class="alert alert-warning rounded-2xl shadow-sm border-2 border-amber-300 p-4 mb-6">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⏰</span>
                    <div>
                        <h4 class="font-black text-amber-950 text-base mb-1">تنبيه: اقتراب موعد انتهاء الاشتراك!</h4>
                        <p class="text-xs text-amber-900 m-0">
                            ينتهي اشتراككم خلال <strong>{{ $subscription->days_remaining }} أيام</strong> (بتاريخ <strong>{{ $subscription->expires_at?->format('Y-m-d') }}</strong>). نوصي بتجديد الاشتراك مبكراً لضمان سلاسة التشغيل.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Current Subscription Overview Card --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Main Status Card --}}
        <div class="md:col-span-2 card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <span class="text-xs font-bold text-slate-400 block uppercase">الباقة الفندقية الحالية</span>
                        <h2 class="text-2xl font-black text-slate-900 mt-1">{{ $subscription?->plan?->name ?? 'الباقة القياسية' }}</h2>
                    </div>
                    @if($subscription)
                        <span class="badge {{ $subscription->status_badge_class }} px-3 py-1.5 font-bold text-xs rounded-full">
                            {{ $subscription->status_label }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 mb-4 text-xs">
                    <div>
                        <span class="text-slate-400 block">تاريخ بدء الاشتراك:</span>
                        <strong class="text-slate-800 text-sm">{{ $subscription?->starts_at?->format('Y-m-d') ?? '—' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">تاريخ الانتهاء:</span>
                        <strong class="text-slate-800 text-sm">{{ $subscription?->expires_at?->format('Y-m-d') ?? '—' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">الأيام المتبقية:</span>
                        @if($subscription)
                            @if($subscription->is_expired)
                                <strong class="text-rose-600 text-sm font-bold">منتهي الصلاحية</strong>
                            @else
                                <strong class="text-emerald-700 text-sm font-bold">{{ $subscription->days_remaining }} يوم</strong>
                            @endif
                        @else
                            <strong class="text-slate-400">—</strong>
                        @endif
                    </div>
                </div>

                {{-- Rooms Quota Meter --}}
                <div>
                    <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                        <span>إشغال سعة الغرف بالنظام</span>
                        <span>
                            {{ $roomsCount }} / {{ $maxRooms == -1 ? 'غير محدود' : $maxRooms }} غرفة
                        </span>
                    </div>
                    @php
                        $percent = ($maxRooms > 0) ? min(100, round(($roomsCount / $maxRooms) * 100)) : 20;
                    @endphp
                    <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs text-slate-500">لتغيير أو ترقية الباقة، اختر من الباقات أدناه واضغط طلب ترقية.</span>
                <button type="button" class="btn btn-primary btn-sm font-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#requestPlanModal">
                    🔄 طلب تجديد أو ترقية الباقة
                </button>
            </div>
        </div>

        {{-- Support Contact Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-gradient-to-br from-indigo-50 to-white rounded-2xl flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold text-indigo-700 block uppercase">دعم العملاء والاشتراكات</span>
                <h3 class="text-lg font-black text-slate-900 mt-1">هل تحتاج مساعدة في باقتك؟</h3>
                <p class="text-xs text-slate-600 mt-2">
                    فريق دعم المنصة جاهز للرد على كافة استفساراتكم المالية، وتفعيل الباقات المخصصة للفنادق والمنتجعات الكبرى.
                </p>
                <div class="mt-4 space-y-2 text-xs text-slate-700">
                    <div>✉️ البريد: <strong>support@hotel.ahmadinsights.online</strong></div>
                    <div>📞 الهاتف: <strong>+963 944 000 000</strong></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-indigo-100">
                <a href="{{ route('admin.support.index') }}" class="btn btn-outline-primary btn-sm font-bold w-full">
                    تذاكر الدعم الفني الفندقي 🎧
                </a>
            </div>
        </div>
    </div>

    {{-- Available Subscription Plans Comparison --}}
    <div class="mb-8">
        <h3 class="text-xl font-black text-slate-900 mb-4">باقات وخطط الاشتراك المتاحة</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans as $plan)
                @php
                    $isCurrent = ($subscription?->plan_id === $plan->id);
                @endphp
                <div class="card p-6 shadow-sm rounded-2xl border-2 flex flex-col justify-between {{ $isCurrent ? 'border-blue-500 bg-blue-50/20' : 'border-slate-200 bg-white' }}">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-lg font-black text-slate-900 m-0">{{ $plan->name }}</h4>
                            @if($isCurrent)
                                <span class="badge bg-blue-600 text-white font-bold text-xs">باقتك الحالية</span>
                            @elseif($plan->badge)
                                <span class="badge bg-indigo-100 text-indigo-800 font-bold text-xs">{{ $plan->badge }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 min-h-[36px]">{{ $plan->description }}</p>

                        <div class="my-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="flex items-baseline justify-between mb-1">
                                <span class="text-xs text-slate-500 font-bold">شهري:</span>
                                <span class="font-black text-slate-900">${{ $plan->price_monthly_usd }} USD <span class="text-xs text-slate-400">({{ number_format($plan->price_monthly_syp) }} ل.س)</span></span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-slate-500 font-bold">سنوي (توفير شهرين):</span>
                                <span class="font-black text-emerald-700">${{ $plan->price_yearly_usd }} USD</span>
                            </div>
                        </div>

                        <ul class="text-xs text-slate-700 space-y-2 mb-4">
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>سعة الغرف: <strong>{{ $plan->max_rooms == -1 ? 'غير محدودة' : $plan->max_rooms . ' غرفة' }}</strong></span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>المستخدمين: <strong>{{ $plan->max_users == -1 ? 'غير محدود' : $plan->max_users . ' مستخدم' }}</strong></span>
                            </li>
                            @foreach($plan->features_list as $f)
                                <li class="flex items-center gap-1.5">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span>{{ $f }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="pt-4 border-t">
                        @if($isCurrent)
                            <button class="btn btn-outline-primary btn-sm font-bold w-full" data-bs-toggle="modal" data-bs-target="#requestPlanModal" onclick="document.getElementById('req_plan_id').value = '{{ $plan->id }}';">
                                🔄 تجديد هذه الباقة
                            </button>
                        @else
                            <button class="btn btn-primary btn-sm font-bold w-full" data-bs-toggle="modal" data-bs-target="#requestPlanModal" onclick="document.getElementById('req_plan_id').value = '{{ $plan->id }}';">
                                🚀 اختيار هذه الباقة
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Invoices History Table --}}
    <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900 m-0">سجل فواتير الاشتراكات الصادرة لفندقكم</h3>
            <span class="text-xs text-slate-500">فواتير رسمية معتمدة من المنصة</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
                <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                    <tr>
                        <th class="ps-4">رقم الفاتورة</th>
                        <th>الباقة</th>
                        <th>المبلغ والعملة</th>
                        <th>طريقة الدفع</th>
                        <th>حالة السداد</th>
                        <th>تاريخ السداد</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr>
                            <td class="ps-4 font-mono font-bold text-slate-900">{{ $inv->invoice_number }}</td>
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
                                    <span class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold">بانتظار السداد</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ $inv->paid_at ? $inv->paid_at->format('Y-m-d') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">لا توجد فواتير اشتراك مسجلة حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL: Request Plan / Upgrade --}}
<div class="modal fade" id="requestPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl shadow-lg border-0">
            <form action="{{ route('admin.subscription.request') }}" method="POST">
                @csrf
                <div class="modal-header border-b px-6 py-4">
                    <h5 class="modal-title font-bold text-slate-900">طلب تجديد أو ترقية باقة الاشتراك</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">اختر الباقة المطلوبة *</label>
                        <select name="plan_id" id="req_plan_id" class="form-select rounded-xl" required>
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}" {{ $subscription?->plan_id == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} (${{ $p->price_monthly_usd }} / شهر)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">دورة السداد *</label>
                            <select name="billing_cycle" class="form-select rounded-xl" required>
                                <option value="monthly">شهري</option>
                                <option value="yearly">سنوي (توفير شهرين مجاناً)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">العملة المفضلة *</label>
                            <select name="currency" class="form-select rounded-xl" required>
                                <option value="USD">$ USD</option>
                                <option value="SYP">ل.س (سوري)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">ملاحظات إضافية أو استفسارات</label>
                        <textarea name="notes" class="form-control rounded-xl text-xs" rows="3" placeholder="اكتب أي ملاحظة أو طلب بخصوص الدفع وتفاصيل الحساب..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-t px-6 py-3">
                    <button type="button" class="btn btn-light btn-sm font-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm font-bold">إرسال طلب الترقية والتجديد</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

