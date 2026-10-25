@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        
        {{-- Header Section --}}
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-600/30 bg-blue-50 px-4 py-1.5 text-xs font-bold text-blue-700 mb-4 shadow-sm">
                <span>✨ باقات متكاملة تناسب جميع أحجام الفنادق والمنتجعات</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-4">
                خطط وباقات الاشتراك في نظام إدارة الفنادق
            </h1>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                حلول سحابية متقدمة تربط بين تطبيق سطح المكتب والطباعة الحرارية، وتطبيقات الجوال، وموقع الحجز الإلكتروني بأعلى معايير الأمان والسرعة.
            </p>

            {{-- Demo Mode Quick Banner CTA --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('demo.enter') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-amber-400 via-amber-300 to-yellow-500 text-slate-950 font-black text-sm shadow-xl shadow-amber-500/20 hover:scale-105 transition transform">
                    <span>⚡ تجربة لوحة الإدارة حياً (Demo)</span>
                    <span class="text-[11px] bg-slate-950 text-amber-300 px-2 py-0.5 rounded-full font-bold">بدون تسجيل</span>
                </a>
                <a href="{{ route('home') }}#downloads" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-slate-800 font-bold text-sm border border-slate-300 shadow-sm hover:bg-slate-50 transition">
                    <span>📲 تحميل تطبيقات النظام</span>
                </a>
            </div>
        </div>

        {{-- Billing Toggle (Monthly / Yearly) --}}
        <div class="flex items-center justify-center mb-10">
            <div class="inline-flex items-center p-1.5 rounded-2xl bg-slate-200/80 border border-slate-300 shadow-inner">
                <button type="button" id="btnBillingMonthly" onclick="switchBillingCycle('monthly')" class="px-5 py-2 rounded-xl text-xs sm:text-sm font-black transition-all bg-white text-slate-900 shadow-sm">
                    الدفع الشهري
                </button>
                <button type="button" id="btnBillingYearly" onclick="switchBillingCycle('yearly')" class="px-5 py-2 rounded-xl text-xs sm:text-sm font-black transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                    <span>الدفع السنوي</span>
                    <span class="bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">توفير شهرين مجاناً 🎉</span>
                </button>
            </div>
        </div>

        {{-- Plans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16 items-stretch">
            @forelse($plans as $plan)
                @php
                    $isPopular = !empty($plan->badge);
                @endphp
                <div class="rounded-3xl border-2 flex flex-col justify-between transition-all duration-300 hover:shadow-2xl relative p-6 sm:p-8 {{ $isPopular ? 'border-blue-600 bg-white shadow-xl shadow-blue-500/10 scale-100 lg:-translate-y-2' : 'border-slate-200 bg-white shadow-sm' }}">
                    
                    @if($plan->badge)
                        <div class="absolute -top-3.5 right-6">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-[11px] font-black shadow-md">
                                <span>⭐</span>
                                <span>{{ $plan->badge }}</span>
                            </span>
                        </div>
                    @endif

                    <div>
                        {{-- Title & Desc --}}
                        <div class="mb-4">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">{{ $plan->name }}</h3>
                            <p class="text-xs text-slate-500 min-h-[36px] leading-relaxed">{{ $plan->description ?? 'باقة متكاملة توفر كافة أدوات إدارة الفندق باحترافية.' }}</p>
                        </div>

                        {{-- Pricing Card --}}
                        <div class="mb-6 p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                            {{-- Monthly Display --}}
                            <div class="pricing-display-monthly">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl sm:text-4xl font-black text-slate-900">${{ $plan->price_monthly_usd }}</span>
                                    <span class="text-xs font-bold text-slate-500">USD / شهرياً</span>
                                </div>
                                <div class="text-xs font-bold text-blue-700 mt-1">
                                    أو {{ number_format($plan->price_monthly_syp) }} ل.س سوري
                                </div>
                            </div>

                            {{-- Yearly Display --}}
                            <div class="pricing-display-yearly hidden">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl sm:text-4xl font-black text-emerald-700">${{ $plan->price_yearly_usd }}</span>
                                    <span class="text-xs font-bold text-slate-500">USD / سنوياً</span>
                                </div>
                                <div class="text-xs font-bold text-emerald-700 mt-1">
                                    أو {{ number_format($plan->price_yearly_syp) }} ل.س (شهران مجاناً)
                                </div>
                            </div>
                        </div>

                        {{-- Limits --}}
                        <div class="space-y-2.5 pb-6 border-b border-slate-100 text-xs text-slate-700">
                            <div class="flex items-center justify-between font-bold">
                                <span class="flex items-center gap-2">
                                    <span class="text-blue-600">🏨</span>
                                    <span>سعة الغرف:</span>
                                </span>
                                <span class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded-lg border border-blue-200">
                                    {{ $plan->max_rooms == -1 ? 'غير محدودة' : $plan->max_rooms . ' غرفة' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between font-bold">
                                <span class="flex items-center gap-2">
                                    <span class="text-indigo-600">👥</span>
                                    <span>حسابات الموظفين:</span>
                                </span>
                                <span class="bg-indigo-50 text-indigo-800 px-2 py-0.5 rounded-lg border border-indigo-200">
                                    {{ $plan->max_users == -1 ? 'غير محدود' : $plan->max_users . ' مستخدمين' }}
                                </span>
                            </div>
                        </div>

                        {{-- Features List --}}
                        <div class="pt-6 mb-8">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3">المزايا المشمولة في الباقة:</h4>
                            <ul class="space-y-2.5 text-xs text-slate-600">
                                @foreach($plan->features_list as $feature)
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-500 font-bold mt-0.5">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="space-y-2 pt-4 border-t border-slate-100">
                        <a href="{{ route('demo.enter') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-xs font-bold transition {{ $isPopular ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-900 hover:bg-slate-800 text-white' }}">
                            <span>⚡ تجربة هذه الباقة فوراً (Demo)</span>
                        </a>
                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                            <span>طلب اشتراك وتسجيل فندق جديد ←</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-slate-400">
                    لا توجد باقات معروضة حالياً. يرجى التواصل مع إدارة المنصة.
                </div>
            @endforelse
        </div>

        {{-- Interactive Coupon & Promo Code Checker Section --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm mb-16">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="max-w-xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold mb-2">
                        <span>🏷️ هل تمتلك كود خصم أو كوبون ترويجي؟</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">
                        تحقق من نسبة الخصم المتاحة لفندقك الآن
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-0">
                        إذا حصلت على كود ترويجي من فريق التسويق أو إحدى حملاتنا الموسمية، أدخله هنا لمعاينة الخصم المطبق على أي باقة.
                    </p>
                </div>

                <div class="w-full md:w-auto min-w-[280px] sm:min-w-[340px]">
                    <div class="flex items-center gap-2">
                        <input type="text" id="promoCodeInput" placeholder="أدخل كود الخصم (مثال: SUMMER20)" class="form-control rounded-xl py-2.5 px-3 text-sm font-mono uppercase text-slate-900 border-slate-300">
                        <button type="button" onclick="verifyPromoCode()" class="btn btn-primary font-bold text-xs py-2.5 px-4 rounded-xl whitespace-nowrap">
                            تطبيق الخصم
                        </button>
                    </div>
                    <div id="promoCodeResult" class="mt-2 text-xs font-bold hidden"></div>
                </div>
            </div>
        </div>

        {{-- FAQ Section --}}
        <div class="max-w-3xl mx-auto">
            <h3 class="text-2xl font-black text-slate-900 text-center mb-6">الأسئلة الشائعة حول الاشتراكات</h3>
            <div class="space-y-4">
                <div class="card p-4 rounded-2xl border border-slate-200 bg-white">
                    <h4 class="font-bold text-sm text-slate-900 mb-1">هل يمكنني تجربة النظام قبل الاشتراك الفعلي؟</h4>
                    <p class="text-xs text-slate-600 m-0 leading-relaxed">
                        نعم بالتأكيد! يمكنك الضغط على زر <strong>Demo</strong> في أعلى الصفحة للدخول فوراً إلى لوحة تحكم كاملة لفندق تجريبي دون الحاجة لتسجيل حساب أو إدخال بطاقة دفع، وتجربة كافة المزايا.
                    </p>
                </div>
                <div class="card p-4 rounded-2xl border border-slate-200 bg-white">
                    <h4 class="font-bold text-sm text-slate-900 mb-1">هل يمكنني السداد بالليرة السورية أو بالدولار الأمريكي؟</h4>
                    <p class="text-xs text-slate-600 m-0 leading-relaxed">
                        النظام يدعم المحاسبة المزدوجة بالكامل. يمكنك اختيار سداد قيمة الاشتراك بالدولار الأمريكي أو بما يعادله بالليرة السورية عبر التحويل البنكي أو الكاش أو وسائل الدفع الإلكتروني المعتمدة.
                    </p>
                </div>
                <div class="card p-4 rounded-2xl border border-slate-200 bg-white">
                    <h4 class="font-bold text-sm text-slate-900 mb-1">هل يتضمن الاشتراك ربط تطبيق سطح المكتب والطابعات الحرارية؟</h4>
                    <p class="text-xs text-slate-600 m-0 leading-relaxed">
                        نعم، جميع الباقات تتضمن إمكانية تحميل تطبيق سطح المكتب (Windows) المتزامن لحظياً مع السيرفر، مع دعم طباعة فواتير الإقامة الحرارية وإيصالات الدفع وباركود الفواتير المباشر.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Embedded Script for Billing Cycle Toggle and Promo Code Verification --}}
<script>
    function switchBillingCycle(cycle) {
        const btnM = document.getElementById('btnBillingMonthly');
        const btnY = document.getElementById('btnBillingYearly');
        const monthlyDisplays = document.querySelectorAll('.pricing-display-monthly');
        const yearlyDisplays = document.querySelectorAll('.pricing-display-yearly');

        if (cycle === 'yearly') {
            btnY.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
            btnY.classList.remove('text-slate-600');
            btnM.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
            btnM.classList.add('text-slate-600');

            monthlyDisplays.forEach(el => el.classList.add('hidden'));
            yearlyDisplays.forEach(el => el.classList.remove('hidden'));
        } else {
            btnM.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
            btnM.classList.remove('text-slate-600');
            btnY.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
            btnY.classList.add('text-slate-600');

            yearlyDisplays.forEach(el => el.classList.add('hidden'));
            monthlyDisplays.forEach(el => el.classList.remove('hidden'));
        }
    }

    const availableCampaigns = @json($activeCampaigns->values());

    function verifyPromoCode() {
        const input = document.getElementById('promoCodeInput');
        const result = document.getElementById('promoCodeResult');
        const code = (input.value || '').trim().toUpperCase();

        result.classList.remove('hidden', 'text-emerald-700', 'text-rose-600');

        if (!code) {
            result.classList.add('text-rose-600');
            result.innerText = 'يرجى إدخال كود الخصم أولاً.';
            return;
        }

        const match = availableCampaigns.find(c => (c.code || '').toUpperCase() === code);
        if (match) {
            result.classList.add('text-emerald-700');
            let discountText = match.discount_percent > 0 
                ? `${match.discount_percent}% خصم فوري` 
                : `$${match.discount_fixed} ${match.currency} خصم نقدي`;
            result.innerText = `🎉 كود فعال: ${match.title} (${discountText}). يمكنك استخدامه عند تأكيد طلب الاشتراك!`;
        } else {
            result.classList.add('text-rose-600');
            result.innerText = 'عذراً، هذا الكود غير صالح أو انتهت صلاحيته.';
        }
    }
</script>
@endsection
