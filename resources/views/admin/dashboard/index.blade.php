@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- App Header Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">🏢 لوحة قيادة إدارة الفندق</h2>
            <p class="text-muted mb-0 small">متابعة فورية للحجوزات، الإشغال، الإيرادات المالية، وسجلات الرقابة والمحذوفات.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->role !== 'hotel_owner')
                <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <span>➕</span>
                    <span>حجز جديد</span>
                </a>
                <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <span>📑</span>
                    <span>فاتورة جديدة</span>
                </a>
                <a href="{{ route('accounts.create') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <span>💵</span>
                    <span>قيد محاسبي</span>
                </a>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 border border-warning">
                    <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
                </span>
            @endif
            <a href="{{ route('deletion-logs.index') }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm">
                <span>🗑️</span>
                <span>سجل المحذوفات ({{ $deletionCount }})</span>
            </a>
        </div>
    </div>

    {{-- Quick KPI Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small fw-bold">الغرف المتاحة حالياً</span>
                            <h3 class="fw-bold mb-0 text-success mt-1">{{ $availableRooms }}</h3>
                            <small class="text-muted">من إجمالي {{ $totalRooms }} غرفة</small>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle fs-4">🚪</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small fw-bold">الغرف المحجوزة / المشغولة</span>
                            <h3 class="fw-bold mb-0 text-primary mt-1">{{ $reservedRooms }}</h3>
                            <small class="text-muted">نسبة الإشغال: <strong class="text-primary">{{ $occupancyRate }}%</strong></small>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle fs-4">🛏️</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small fw-bold">حجوزات اليوم الجديدة</span>
                            <h3 class="fw-bold mb-0 text-warning mt-1">{{ $todayBookings }}</h3>
                            <small class="text-muted">حركة الحجز الفندقي</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-circle fs-4">📅</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="w-100">
                            <span class="text-muted small fw-bold d-block">إيرادات الفندق اليوم</span>
                            <div class="mt-1">
                                <div class="fw-bold text-success fs-6">
                                    🇸🇾 {{ number_format($todayRevenueSYP, 2) }} <small class="text-muted">ل.س</small>
                                </div>
                                <div class="fw-bold text-primary fs-6">
                                    🇺🇸 ${{ number_format($todayRevenueUSD, 2) }} <small class="text-muted">USD</small>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">المبالغ المحصلة فعلياً</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-circle fs-4">💰</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- قسم سجل المحذوفات البارز (فواتير وحجوزات محذوفة مع الأسباب) --}}
    <div class="card border-0 shadow-sm mb-4 border-top border-3 border-danger">
        <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-3 fs-5">🗑️</div>
                <div>
                    <h5 class="fw-bold mb-0 text-danger">سجل المحذوفات والرقابة الإدارية</h5>
                    <small class="text-muted">توثيق مباشر لجميع الفواتير والحجوزات التي تم حذفها مع أسباب الحذف والمسؤولين عنها.</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
                    المحذوفات: <strong>{{ $deletionCount }}</strong> حركة
                </span>
                <span class="badge bg-secondary-subtle text-dark border px-3 py-2 fs-6">
                    🇸🇾 {{ number_format($deletedTotalSYP, 2) }} ل.س &nbsp;|&nbsp; 🇺🇸 ${{ number_format($deletedTotalUSD, 2) }} USD
                </span>
                <a href="{{ route('deletion-logs.index') }}" class="btn btn-outline-danger btn-sm fw-bold">
                    عرض كامل السجل ⬅️
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">نوع الحركة</th>
                            <th>رقم المرجع</th>
                            <th>النزيل / الغرفة</th>
                            <th>القيمة المحذوفة</th>
                            <th style="min-width: 250px;">سبب الحذف الموثق</th>
                            <th>الموظف المسؤول</th>
                            <th>تاريخ وتوقيت الحذف</th>
                            <th class="text-end pe-3">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDeletionLogs as $log)
                            <tr>
                                <td class="ps-3">
                                    @if($log->record_type === 'invoice')
                                        <span class="badge bg-danger">فاتورة</span>
                                    @elseif($log->record_type === 'booking')
                                        <span class="badge bg-warning text-dark">حجز نزيل</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $log->record_type }}</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $log->reference_number ?: ('#' . $log->record_id) }}</td>
                                <td>
                                    <div><strong>{{ $log->guest_name ?: '—' }}</strong></div>
                                    @if($log->room_number)
                                        <small class="text-muted">غرفة {{ $log->room_number }}</small>
                                    @endif
                                </td>
                                <td class="fw-bold text-danger">
                                    @if($log->currency === 'USD')
                                        ${{ number_format((float)$log->amount, 2) }} <span class="badge bg-primary-subtle text-primary small">USD</span>
                                    @else
                                        {{ number_format((float)$log->amount, 2) }} <small class="text-muted">ل.س</small>
                                    @endif
                                    @if($log->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success small d-block" style="font-size: 0.7rem;">كانت مسددة</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="p-2 bg-light rounded text-dark small border-start border-3 border-danger">
                                        {{ $log->reason }}
                                    </div>
                                </td>
                                <td class="small">
                                    <span class="fw-semibold">{{ $log->user?->name ?? 'مدير الفندق' }}</span>
                                </td>
                                <td class="small text-muted" dir="ltr">
                                    {{ $log->deleted_at ? $log->deleted_at->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="text-end pe-3">
                                    @if(!empty($log->snapshot))
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#dashboardLogModal{{ $log->id }}">
                                            معاينة
                                        </button>

                                        {{-- Modal Snapshot --}}
                                        <div class="modal fade text-start" id="dashboardLogModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content shadow">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h6 class="modal-title fw-bold">نسخة البيانات المحذوفة ({{ $log->reference_number ?: $log->record_id }})</h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-3">
                                                        <div class="alert alert-danger py-2 mb-3">
                                                            <strong>سبب الحذف الموثق:</strong> {{ $log->reason }}
                                                        </div>
                                                        <div class="row g-2 mb-3 small">
                                                            <div class="col-md-4"><strong>النوع:</strong> {{ $log->record_type }}</div>
                                                            <div class="col-md-4"><strong>المبلغ:</strong> {{ number_format((float)$log->amount, 2) }} {{ $log->currency === 'USD' ? 'USD' : 'ل.س' }}</div>
                                                            <div class="col-md-4"><strong>المستخدم:</strong> {{ $log->user?->name }}</div>
                                                        </div>
                                                        <pre class="bg-light p-3 rounded border text-start small mb-0" style="max-height: 250px; overflow-y: auto; direction: ltr;">{{ json_encode($log->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                    <div class="modal-footer py-2">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted small">
                                    لا توجد عمليات حذف مسجلة حتى الآن. جميع الحركات المحذوفة ستظهر هنا فور تنفيذها مع أسبابها.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- الرسوم البيانية التفاعلية --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0">📊 الإيرادات الشهرية</h5>
                </div>
                <div class="card-body p-3">
                    <canvas id="revenueChart" style="max-height: 260px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0">📈 الحجوزات الشهرية</h5>
                </div>
                <div class="card-body p-3">
                    <canvas id="bookingChart" style="max-height: 260px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0">🥧 نسبة الإشغال</h5>
                </div>
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                    <div style="width: 180px; height: 180px;">
                        <canvas id="occupancyChart"></canvas>
                    </div>
                    <div class="mt-3 text-center small">
                        <span class="badge bg-primary me-1">مشغول: {{ $reservedRooms }}</span>
                        <span class="badge bg-success">متاح: {{ $availableRooms }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- المحاسبة السريعة والشكاوى --}}
    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <h5 class="fw-bold mb-0">💼 المركز المالي والمحاسبي (مفصول بالعملتين)</h5>
                        <small class="text-muted">متابعة دقيقة لحركة الوارد والمصروف وصافي الأرباح لكل عملة بشكل مستقل</small>
                    </div>
                    <a href="{{ route('accounts.index') }}" class="btn btn-outline-primary btn-sm">دفتر الأستاذ ⬅️</a>
                </div>
                <div class="card-body p-3">
                    {{-- Syrian Pound Box --}}
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-6">🇸🇾 محفظة الليرة السورية (SYP)</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">ل.س</span>
                        </div>
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 bg-success bg-opacity-10 rounded">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">الوارد (دخل)</small>
                                    <strong class="text-success" style="font-size: 0.85rem;">+{{ number_format($incomeSYP, 2) }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-danger bg-opacity-10 rounded">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">الصادر (مصروف)</small>
                                    <strong class="text-danger" style="font-size: 0.85rem;">-{{ number_format($expensesSYP, 2) }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-primary bg-opacity-10 rounded">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">صافي الربح</small>
                                    <strong class="text-primary" style="font-size: 0.85rem;">{{ number_format($profitSYP, 2) }} ل.س</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- US Dollar Box --}}
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-6">🇺🇸 محفظة الدولار الأمريكي (USD)</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono">$ USD</span>
                        </div>
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 bg-success bg-opacity-10 rounded">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">الوارد (دخل)</small>
                                    <strong class="text-success font-mono" style="font-size: 0.85rem;">+${{ number_format($incomeUSD, 2) }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-danger bg-opacity-10 rounded">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">الصادر (مصروف)</small>
                                    <strong class="text-danger font-mono" style="font-size: 0.85rem;">-${{ number_format($expensesUSD, 2) }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-primary bg-opacity-10 rounded">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">صافي الربح</small>
                                    <strong class="text-primary font-mono" style="font-size: 0.85rem;">${{ number_format($profitUSD, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">📢 شكاوى النزلاء الجديدة ({{ $newComplaints->count() }})</h5>
                    <a href="{{ route('complaints.index') }}" class="btn btn-outline-secondary btn-sm">إدارة الشكاوى ⬅️</a>
                </div>
                <div class="card-body p-3">
                    @forelse($newComplaints as $complaint)
                        <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded">
                            <div>
                                <span class="badge bg-danger me-1">جديدة</span>
                                <strong>{{ $complaint->title }}</strong>
                            </div>
                            <span class="small text-muted">{{ $complaint->created_at->format('Y-m-d') }}</span>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            لا توجد شكاوى جديدة معلقة بحمد الله.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- آخر الحجوزات ومهام التنظيف --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">🛎️ أحدث الحجوزات والموظف المسؤول</h5>
                    <a href="{{ route('bookings.index') }}" class="btn btn-outline-primary btn-sm">عرض كل الحجوزات</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">الغرفة</th>
                                    <th>النزيل</th>
                                    <th>موظف الاستقبال</th>
                                    <th>الحالة</th>
                                    <th>وقت الحجز</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentBookings as $booking)
                                    <tr>
                                        <td class="ps-3 fw-bold">غرفة {{ $booking->room?->room_number ?? '—' }}</td>
                                        <td>{{ $booking->guest?->name ?? '—' }}</td>
                                        <td class="small">{{ $booking->creator?->name ?? 'الحجز العام' }}</td>
                                        <td>
                                            @if($booking->status === 'confirmed')
                                                <span class="badge bg-primary">مؤكد</span>
                                            @elseif($booking->status === 'checked_in')
                                                <span class="badge bg-success">مقيم</span>
                                            @elseif($booking->status === 'checked_out')
                                                <span class="badge bg-secondary">غادر</span>
                                            @else
                                                <span class="badge bg-info text-dark">{{ $booking->status }}</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted" dir="ltr">{{ $booking->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-3 text-muted small">لا توجد حجوزات حديثة.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">🧹 آخر مهام تنظيف الغرف</h5>
                    <a href="{{ route('cleaning.index') }}" class="btn btn-outline-secondary btn-sm">جدول التنظيف</a>
                </div>
                <div class="card-body p-3">
                    @forelse($recentCleaningLogs as $log)
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div>
                                <strong>غرفة {{ $log->room?->room_number ?? '—' }}</strong>
                                <small class="text-muted d-block">عامل التنظيف: {{ $log->cleaner?->name ?? '—' }}</small>
                            </div>
                            <span class="badge bg-light text-dark border">{{ $log->cleaned_at }}</span>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">لا توجد مهام تنظيف مسجلة مؤخراً.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // الإيرادات الشهرية بالعملتين المنفصلتين
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
            datasets: [
                {
                    label: 'إيراد الليرة (ل.س)',
                    data: @json($monthlyRevenueSYP),
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'إيراد الدولار ($ USD)',
                    data: @json($monthlyRevenueUSD),
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'ل.س' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'USD $' },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            }
        }
    });

    // الحجوزات الشهرية
    new Chart(document.getElementById('bookingChart'), {
        type: 'bar',
        data: {
            labels: ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
            datasets: [{
                label: 'عدد الحجوزات',
                data: @json($monthlyBookings),
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // نسبة الإشغال
    new Chart(document.getElementById('occupancyChart'), {
        type: 'doughnut',
        data: {
            labels: ['مشغول', 'متاح'],
            datasets: [{
                data: [{{ $reservedRooms }}, {{ $availableRooms }}],
                backgroundColor: ['#2563eb', '#10b981'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });
</script>
@endsection
