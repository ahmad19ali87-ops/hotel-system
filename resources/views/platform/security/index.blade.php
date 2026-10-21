@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Header Banner --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #311042 100%);">
        <div class="card-body p-4 text-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center bg-white/10 rounded-3 p-3 border border-white/20 shadow-sm" style="width: 58px; height: 58px;">
                        <span class="fs-2">🛡️</span>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1 text-white">لوحة المراقبة والأمان الذكية (Security Monitoring Dashboard)</h3>
                        <p class="text-white-50 mb-0 small">
                            مراقبة حية وشاملة لكافة الهجمات، كشف محاولات الاختراق جغرافياً، وتنبيهات فورية ضد الهجمات الحرجة
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill fw-bold border border-danger shadow-sm">
                        🚨 {{ $totalAttacks24h }} هجمة آخر 24 ساعة
                    </span>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                        ⛔ {{ $activeBlockedIpsCount }} عنوان IP محظور
                    </span>
                    <span class="badge bg-info-subtle text-info px-3 py-2 rounded-pill fw-bold border border-info-subtle">
                        💉 {{ $sqlInjections24h }} محاولة SQL Injection
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-bold border border-secondary-subtle">
                        🦠 {{ $maliciousFiles24h }} ملف ضار
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <span>✅</span>
                <strong>نجاح:</strong> {{ session('success') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Critical Attacks Alert Callout if any --}}
    @if($criticalAttacks24h > 0)
        <div class="alert alert-danger rounded-4 border-2 border-danger shadow-sm mb-4 p-3 d-flex align-items-center gap-3 bg-red-50 dark:bg-red-950/40 text-danger">
            <span class="fs-1">⚠️</span>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1 text-danger">تنبيه أمني عالي الخطورة!</h5>
                <p class="mb-0 small text-danger-emphasis">
                    تم رصد <strong>{{ $criticalAttacks24h }}</strong> محاولة هجوم مصنفة على أنها عالية الخطورة (Critical) خلال الـ 24 ساعة الماضية، وتم التعامل معها تلقائياً بالصد والحظر.
                </p>
            </div>
            <a href="#logs-section" class="btn btn-sm btn-danger rounded-3 px-3 fw-bold">معاينة السجلات</a>
        </div>
    @endif

    {{-- KPI Cards Grid (6 Key Security Indicators) --}}
    <div class="row g-3 mb-4">
        {{-- Total Attacks 24h --}}
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <span class="text-xs text-muted d-block mb-1 font-bold">هجمات آخر 24 ساعة</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="fw-black text-danger mb-0">{{ number_format($totalAttacks24h) }}</h3>
                        <span class="fs-4">🚨</span>
                    </div>
                    <span class="text-[11px] text-muted mt-2 d-block">إجمالي الأنشطة المصدودة</span>
                </div>
            </div>
        </div>

        {{-- SQL Injection --}}
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <span class="text-xs text-muted d-block mb-1 font-bold">حقن SQL Injection</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="fw-black text-primary mb-0">{{ number_format($sqlInjections24h) }}</h3>
                        <span class="fs-4">💉</span>
                    </div>
                    <span class="text-[11px] text-muted mt-2 d-block">محاولات استعلام خبيثة</span>
                </div>
            </div>
        </div>

        {{-- Malicious Files --}}
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800 border-start border-4 border-dark">
                <div class="card-body p-3">
                    <span class="text-xs text-muted d-block mb-1 font-bold">رفع ملفات ضارة</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="fw-black text-dark dark:text-light mb-0">{{ number_format($maliciousFiles24h) }}</h3>
                        <span class="fs-4">🦠</span>
                    </div>
                    <span class="text-[11px] text-muted mt-2 d-block">PHP / EXE / شل محظور</span>
                </div>
            </div>
        </div>

        {{-- Replay Attacks --}}
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <span class="text-xs text-muted d-block mb-1 font-bold">هجمات Replay</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="fw-black text-warning mb-0">{{ number_format($replayAttacks24h) }}</h3>
                        <span class="fs-4">🔁</span>
                    </div>
                    <span class="text-[11px] text-muted mt-2 d-block">تكرار طلبات أو Nonce</span>
                </div>
            </div>
        </div>

        {{-- Failed Logins & Brute Force --}}
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800 border-start border-4 border-info">
                <div class="card-body p-3">
                    <span class="text-xs text-muted d-block mb-1 font-bold">فشل دخول وتخمين</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="fw-black text-info mb-0">{{ number_format($failedLogins24h) }}</h3>
                        <span class="fs-4">🔑</span>
                    </div>
                    <span class="text-[11px] text-muted mt-2 d-block">Brute Force مجهول</span>
                </div>
            </div>
        </div>

        {{-- Fake Google Logins --}}
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800 border-start border-4 border-secondary">
                <div class="card-body p-3">
                    <span class="text-xs text-muted d-block mb-1 font-bold">Google مزيفة</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="fw-black text-secondary mb-0">{{ number_format($fakeGoogleLogins24h) }}</h3>
                        <span class="fs-4">🕵️</span>
                    </div>
                    <span class="text-[11px] text-muted mt-2 d-block">انتحال رموز Google</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Visual Analytics Grid (3 Panels: By Country, By IP, By Attack Type) --}}
    <div class="row g-4 mb-4">
        {{-- 1. Attacks by Country --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                        <span>🌍</span> الهجمات حسب الدولة
                    </h5>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill text-xs">آخر 24 ساعة</span>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    @if($attacksByCountry->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <span class="fs-1 d-block mb-2">🕊️</span>
                            لا توجد هجمات مسجلة خلال آخر 24 ساعة.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-xs text-muted border-bottom">
                                        <th>الدولة</th>
                                        <th class="text-center">الهجمات</th>
                                        <th class="text-end">النسبة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $countrySum = $attacksByCountry->sum('total') ?: 1; @endphp
                                    @foreach($attacksByCountry as $country)
                                        @php $percent = round(($country->total / $countrySum) * 100, 1); @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-5">{{ $country->flag }}</span>
                                                    <div>
                                                        <span class="fw-bold text-xs text-slate-800 dark:text-slate-200 d-block">{{ $country->country_name }}</span>
                                                        <span class="text-[10px] text-muted uppercase font-monospace">{{ $country->country_code }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-1 rounded-pill">
                                                    {{ $country->total }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px; min-width: 50px;">
                                                        <div class="progress-bar bg-danger" style="width: {{ $percent }}%"></div>
                                                    </div>
                                                    <span class="text-xs text-muted font-bold">{{ $percent }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Top Offending IPs --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                        <span>🎯</span> الهجمات حسب الـ IP
                    </h5>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill text-xs">أخطر العناوين</span>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    @if($attacksByIp->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <span class="fs-1 d-block mb-2">🛡️</span>
                            لا توجد عناوين IP مشبوهة حالياً.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-xs text-muted border-bottom">
                                        <th>عنوان الـ IP</th>
                                        <th class="text-center">المحاولات</th>
                                        <th class="text-end">الإجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attacksByIp as $ipRow)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span>{{ $ipRow->flag }}</span>
                                                    <div>
                                                        <span class="font-monospace fw-bold text-xs text-slate-800 dark:text-slate-200 d-block">{{ $ipRow->ip_address }}</span>
                                                        <span class="text-[10px] text-muted">{{ $ipRow->country_name }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger text-white font-monospace rounded-pill px-2">
                                                    {{ $ipRow->total }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                @if($ipRow->is_blocked)
                                                    <form action="{{ route('platform.security.unblock') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="ip_address" value="{{ $ipRow->ip_address }}">
                                                        <button type="submit" class="btn btn-outline-success btn-xs rounded-pill px-2 py-1 text-[11px] fw-bold" title="إلغاء الحظر">
                                                            فك الحظر
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('platform.security.block') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="ip_address" value="{{ $ipRow->ip_address }}">
                                                        <input type="hidden" name="duration" value="24">
                                                        <input type="hidden" name="reason" value="تكرار هجمات تم رصدها في لوحة المراقبة">
                                                        <button type="submit" class="btn btn-outline-danger btn-xs rounded-pill px-2 py-1 text-[11px] fw-bold" title="حظر 24 ساعة">
                                                            حظر 🚫
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Attacks by Attack Type --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white dark:bg-slate-800">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                        <span>📊</span> الهجمات حسب النوع
                    </h5>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill text-xs">تصنيف الهجمات</span>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    @if($attacksByType->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <span class="fs-1 d-block mb-2">✨</span>
                            لا توجد تصنيفات هجمات مسجلة.
                        </div>
                    @else
                        @php $typeSum = $attacksByType->sum('total') ?: 1; @endphp
                        <div class="d-flex flex-column gap-3">
                            @foreach($attacksByType as $typeRow)
                                @php $pct = round(($typeRow->total / $typeSum) * 100, 1); @endphp
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-xs fw-bold text-slate-700 dark:text-slate-300">{{ $typeRow->label }}</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-monospace text-xs">{{ $typeRow->total }}</span>
                                            <span class="text-[11px] text-muted">{{ $pct }}%</span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-gradient-to-r from-blue-600 to-indigo-600" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Settings & Manual Actions Row --}}
    <div class="row g-4 mb-4">
        {{-- Instant Alert Settings Card --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white dark:bg-slate-800 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-1 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                        <span>⚡</span> قواعد التنبيهات الفورية (Instant Alert Rules)
                    </h5>
                    <p class="text-xs text-muted mb-0">إرسال إشعار فوري لمدير المنصة عند رصد هجوم حرج أو من دولة معينة أو تجاوز العتبة</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <form action="{{ route('platform.security.settings') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-600 dark:text-slate-300">
                                🌐 قائمة رموز الدول المراقبة للتنبيه الفوري (مفصولة بفواصل)
                            </label>
                            <input type="text" name="security_alert_countries" class="form-control rounded-3 text-sm font-monospace" value="{{ old('security_alert_countries', $alertCountries) }}" placeholder="مثال: IL,IR,RU,CN,US">
                            <div class="form-text text-[11px] text-muted">
                                أي محاولة دخول أو هجوم من هذه الدول ستطلق إشعاراً فورياً للمسؤول.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-600 dark:text-slate-300">
                                📈 عتبة عدد المحاولات بالساعة للتنبيه التلقائي (Threshold)
                            </label>
                            <div class="input-group">
                                <input type="number" name="security_alert_threshold" class="form-control rounded-start-3 text-sm font-bold" min="1" max="100" value="{{ old('security_alert_threshold', $alertThreshold) }}">
                                <span class="input-group-text text-xs rounded-end-3">محاولات / ساعة</span>
                            </div>
                            <div class="form-text text-[11px] text-muted">
                                إطلاق إنذار فوري بمجرد تجاوز نفس الـ IP هذا العدد من المحاولات خلال ساعة.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-xs text-success fw-bold">✓ التنبيه عند الهجوم الخطير (Critical) مفعل دائماً</span>
                            <button type="submit" class="btn btn-primary rounded-3 btn-sm px-4 fw-bold shadow-sm">
                                حفظ قواعد التنبيه 💾
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Manual IP Block & Quick Tools --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white dark:bg-slate-800 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-1 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                        <span>⛔</span> حظر عنوان IP يدوياً (Manual IP Blacklist)
                    </h5>
                    <p class="text-xs text-muted mb-0">حظر عنوان IP فورياً مع تحديد السبب وفترة الحظر</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <form action="{{ route('platform.security.block') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label text-xs fw-bold text-slate-600 dark:text-slate-300">عنوان الـ IP *</label>
                                <input type="text" name="ip_address" class="form-control rounded-3 text-sm font-monospace" required placeholder="مثال: 198.51.100.4">
                            </div>
                            <div class="col-5">
                                <label class="form-label text-xs fw-bold text-slate-600 dark:text-slate-300">مدة الحظر *</label>
                                <select name="duration" class="form-select rounded-3 text-sm">
                                    <option value="1">ساعة واحدة</option>
                                    <option value="24" selected>24 ساعة (يوم)</option>
                                    <option value="168">7 أيام (أسبوع)</option>
                                    <option value="720">30 يوماً (شهر)</option>
                                    <option value="permanent">حظر دائم</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-600 dark:text-slate-300">سبب الحظر (اختياري)</label>
                            <input type="text" name="reason" class="form-control rounded-3 text-sm" placeholder="مثال: نشاط غير مصرح به أو فحص ثغرات">
                        </div>

                        <div class="d-flex justify-content-end pt-2 border-top">
                            <button type="submit" class="btn btn-danger rounded-3 btn-sm px-4 fw-bold shadow-sm">
                                تطبيق الحظر الفوري 🚫
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Currently Blocked IPs Table --}}
    @if($blockedIps->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white dark:bg-slate-800">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                    <span>🚫</span> قائمة العناوين المحظورة حالياً ({{ $blockedIps->count() }})
                </h5>
                <span class="badge bg-danger rounded-pill text-xs">حظر نشط</span>
            </div>
            <div class="card-body p-4 pt-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-xs text-muted">
                            <tr>
                                <th>عنوان الـ IP</th>
                                <th>سبب الحظر</th>
                                <th>عدد الانتهاكات</th>
                                <th>تاريخ الحظر</th>
                                <th>انتهاء الحظر</th>
                                <th class="text-end">فك الحظر</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach($blockedIps as $bIp)
                                <tr>
                                    <td>
                                        <span class="font-monospace fw-bold text-danger">{{ $bIp->ip_address }}</span>
                                    </td>
                                    <td>{{ $bIp->reason ?: 'حظر أمني تلقائي' }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $bIp->violations_count }}</span>
                                    </td>
                                    <td class="text-xs text-muted">{{ $bIp->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-xs">
                                        @if($bIp->blocked_until)
                                            <span class="text-warning fw-bold">{{ $bIp->blocked_until->diffForHumans() }}</span>
                                            <span class="text-muted d-block font-monospace">({{ $bIp->blocked_until->format('Y-m-d H:i') }})</span>
                                        @else
                                            <span class="badge bg-danger">دائم</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('platform.security.unblock') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="ip_address" value="{{ $bIp->ip_address }}">
                                            <button type="submit" class="btn btn-outline-success btn-xs rounded-pill px-3 py-1 text-xs fw-bold">
                                                إلغاء الحظر ✓
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Live Security Incident Logs Table --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white dark:bg-slate-800" id="logs-section">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1 text-slate-800 dark:text-slate-100 d-flex align-items-center gap-2">
                    <span>📜</span> سجل الهجمات والأنشطة المشبوهة (Live Security Audit Logs)
                </h5>
                <p class="text-xs text-muted mb-0">أحدث الأنشطة المسجلة مع تفاصيل الطلب والمسار ومستوى الخطورة</p>
            </div>
            <form action="{{ route('platform.security.clear-logs') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في أرشفة وتنظيف السجلات الأقدم من 30 يوماً؟');">
                @csrf
                <input type="hidden" name="days" value="30">
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-3 text-xs fw-bold">
                    🧹 تنظيف السجلات القديمة (> 30 يوم)
                </button>
            </form>
        </div>

        {{-- Filter toolbar --}}
        <div class="card-body px-4 pt-2 pb-0">
            <form action="{{ route('platform.security.index') }}" method="GET" class="row g-2 align-items-center mb-3">
                <div class="col-12 col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm rounded-3" placeholder="بحث بالـ IP أو الدولة أو المسار...">
                </div>
                <div class="col-6 col-md-3">
                    <select name="type" class="form-select form-select-sm rounded-3">
                        <option value="">-- كل أنواع الهجمات --</option>
                        <option value="sql_injection_attempt" {{ request('type') == 'sql_injection_attempt' ? 'selected' : '' }}>حقن SQL</option>
                        <option value="malicious_file_upload" {{ request('type') == 'malicious_file_upload' ? 'selected' : '' }}>رفع ملفات ضارة</option>
                        <option value="replay_attack_duplicate_payload" {{ request('type') == 'replay_attack_duplicate_payload' ? 'selected' : '' }}>Replay Attack</option>
                        <option value="fake_google_token" {{ request('type') == 'fake_google_token' ? 'selected' : '' }}>Google Login مزيف</option>
                        <option value="failed_login_attempt" {{ request('type') == 'failed_login_attempt' ? 'selected' : '' }}>فشل تسجيل دخول</option>
                        <option value="failed_verification_code" {{ request('type') == 'failed_verification_code' ? 'selected' : '' }}>تخمين كود تفعيل</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="severity" class="form-select form-select-sm rounded-3">
                        <option value="">-- مستوى الخطورة --</option>
                        <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>حرج (Critical)</option>
                        <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>تحذير (Warning)</option>
                        <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>معلومات (Info)</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 flex-grow-1 fw-bold">تصفية</button>
                    @if(request()->hasAny(['search', 'type', 'severity']))
                        <a href="{{ route('platform.security.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">إلغاء</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-4 pt-1">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <span class="fs-1 d-block mb-2">🛡️</span>
                    لا توجد سجلات هجمات مطابقة للفلاتر المحددة.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-xs text-muted">
                            <tr>
                                <th>التوقيت</th>
                                <th>الـ IP والدولة</th>
                                <th>نوع الهجوم</th>
                                <th>الخطورة</th>
                                <th>المسار المطلوب</th>
                                <th>الرسالة / التفاصيل</th>
                                <th class="text-end">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach($logs as $log)
                                <tr>
                                    <td class="text-xs text-muted" style="white-space: nowrap;">
                                        {{ $log->created_at->format('Y-m-d') }}
                                        <span class="d-block font-monospace text-slate-700 dark:text-slate-300">{{ $log->created_at->format('H:i:s') }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{ \App\Services\GeoIpService::countryCodeToFlag($log->country_code ?? 'UNKNOWN') }}</span>
                                            <div>
                                                <span class="font-monospace fw-bold text-xs text-slate-800 dark:text-slate-200 d-block">{{ $log->ip_address }}</span>
                                                <span class="text-[10px] text-muted">{{ $log->country_name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-monospace text-[11px] px-2 py-1">
                                            {{ $log->event_type }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->severity === 'critical')
                                            <span class="badge bg-danger rounded-pill px-2.5 py-1 text-xs fw-bold">🚨 حرج (Critical)</span>
                                        @elseif($log->severity === 'warning')
                                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 text-xs fw-bold">⚠️ تحذير (Warning)</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1 text-xs">معلومات</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-xs font-monospace text-muted d-inline-block text-truncate" style="max-width: 180px;" title="{{ $log->url }}">
                                            <span class="badge bg-light text-dark me-1">{{ $log->method }}</span>
                                            {{ $log->url }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-xs text-slate-700 dark:text-slate-300 d-block" style="max-width: 250px;">
                                            {{ $log->message }}
                                        </span>
                                        @if(!empty($log->payload))
                                            <details class="text-[10px] text-muted mt-1">
                                                <summary class="cursor-pointer text-primary">عرض الحمولة (Payload)</summary>
                                                <pre class="bg-slate-100 dark:bg-slate-900 p-2 rounded mt-1 font-monospace text-[10px]" style="max-height: 80px; overflow-y: auto;">{{ $log->payload }}</pre>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(\App\Models\BlockedIp::isBlocked($log->ip_address))
                                            <span class="badge bg-danger-subtle text-danger text-[11px] px-2 py-1">محظور</span>
                                        @else
                                            <form action="{{ route('platform.security.block') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="ip_address" value="{{ $log->ip_address }}">
                                                <input type="hidden" name="duration" value="24">
                                                <input type="hidden" name="reason" value="تم رصد هجوم: {{ $log->event_type }}">
                                                <button type="submit" class="btn btn-outline-danger btn-xs rounded-pill px-2 py-1 text-[11px] fw-bold" title="حظر هذا العنوان 24 ساعة">
                                                    حظر
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
