@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- App Header Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">📅 إدارة سجل وحجوزات النزلاء</h2>
            <p class="text-muted mb-0 small">متابعة الحجوزات، تسكين النزلاء، تسجيل الخروج، وسجلات الإلغاء والحذف.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->role !== 'hotel_owner')
                <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <span>➕</span>
                    <span>إنشاء حجز جديد</span>
                </a>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 border border-warning">
                    <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
                </span>
            @endif
            <a href="{{ route('deletion-logs.index') }}?type=booking" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                <span>🗑️</span>
                <span>سجل الحجوزات المحذوفة</span>
            </a>
        </div>
    </div>

    {{-- KPI Metric Cards --}}
    @php
        $totalBookingsCount = $bookings->count();
        $pendingCount = $bookings->where('status', 'pending')->count();
        $confirmedCount = $bookings->where('status', 'confirmed')->count();
        $checkedInCount = $bookings->where('status', 'checked_in')->count();
        $checkedOutCount = $bookings->where('status', 'checked_out')->count();
        $totalRevenue = $bookings->sum('total_price');
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي الحجوزات</span>
                    <h4 class="fw-bold mb-0 text-primary mt-1">{{ number_format($totalBookingsCount) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">حجوزات جديدة معلقة (بحاجة تأكيد)</span>
                    <h4 class="fw-bold mb-0 text-warning mt-1">{{ number_format($pendingCount) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">نزلاء مقيمين حالياً</span>
                    <h4 class="fw-bold mb-0 text-success mt-1">{{ number_format($checkedInCount) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">حجوزات مؤكدة (قادمة)</span>
                    <h4 class="fw-bold mb-0 text-info mt-1">{{ number_format($confirmedCount) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Live Filter Toolbar --}}
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">🔍</span>
                        <input type="text" id="bookingTableSearch" class="form-control border-start-0" placeholder="بحث فوري باسم النزيل، رقم الغرفة، الهاتف، أو الموظف...">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active filter-status-btn" data-status="all">الكل</button>
                        <button type="button" class="btn btn-outline-warning filter-status-btn" data-status="pending">معلق ({{ $pendingCount }})</button>
                        <button type="button" class="btn btn-outline-primary filter-status-btn" data-status="confirmed">مؤكد</button>
                        <button type="button" class="btn btn-outline-success filter-status-btn" data-status="checked_in">مقيم</button>
                        <button type="button" class="btn btn-outline-secondary filter-status-btn" data-status="checked_out">غادر</button>
                        <button type="button" class="btn btn-outline-danger filter-status-btn" data-status="cancelled">ملغي</button>
                    </div>
                </div>
                <div class="col-md-2 text-md-end text-muted small">
                    المعروض: <span id="visibleBookingCount" class="fw-bold">{{ $bookings->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="bookingsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">الغرفة</th>
                            <th>النزيل</th>
                            <th>الهاتف</th>
                            <th>الدخول</th>
                            <th>الخروج</th>
                            <th>السعر الإجمالي</th>
                            <th>الحالة</th>
                            <th>الموظف</th>
                            <th class="text-end pe-3" style="min-width: 240px;">خيارات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($bookings as $booking)
                            @php
                                $nights = max(1, (int) \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));
                                $calcSYP = (float)($booking->room?->price ?? 0) * $nights;
                                $calcUSD = (float)($booking->room?->price_usd ?? 0) * $nights;
                            @endphp
                            <tr class="booking-row" data-status="{{ $booking->status }}">
                                <td class="ps-3 fw-bold">
                                    <span class="badge bg-light text-dark border">غرفة {{ $booking->room?->room_number ?? '—' }}</span>
                                </td>
                                <td class="fw-semibold">{{ $booking->guest?->name ?? '—' }}</td>
                                <td class="text-muted small" dir="ltr">{{ $booking->guest?->phone ?? '—' }}</td>
                                <td><span class="badge bg-light text-secondary">{{ $booking->check_in }}</span></td>
                                <td><span class="badge bg-light text-secondary">{{ $booking->check_out }}</span></td>
                                <td class="fw-bold text-primary">
                                    {{ number_format((float)$booking->total_price) }}
                                    <span class="badge {{ ($booking->currency ?? 'SYP') === 'USD' ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-secondary-subtle text-secondary' }} border ms-1 small">
                                        {{ ($booking->currency ?? 'SYP') === 'USD' ? '$ USD' : 'ل.س' }}
                                    </span>
                                </td>
                                <td>
                                    @if($booking->status === 'pending')
                                        <span class="badge bg-warning text-dark border border-warning">⏳ حجز جديد (معلق)</span>
                                    @elseif($booking->status === 'confirmed')
                                        <span class="badge bg-primary">✓ مؤكد</span>
                                    @elseif($booking->status === 'checked_in')
                                        <span class="badge bg-success">تم الدخول (مقيم)</span>
                                    @elseif($booking->status === 'checked_out')
                                        <span class="badge bg-secondary">تم المغادرة</span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="badge bg-danger">ملغي</span>
                                    @else
                                        <span class="badge bg-info text-dark">{{ $booking->status }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $booking->creator?->name ?? 'حجز إلكتروني' }}</td>

                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if(auth()->user()->role === 'hotel_owner')
                                            <span class="badge bg-light text-muted border">اطلاع فقط</span>
                                        @else
                                            @if($booking->status === 'pending')
                                                {{-- زر تأكيد الحجز وتحديد العملة --}}
                                                <button type="button" class="btn btn-warning btn-sm fw-bold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmBookingModal{{ $booking->id }}">
                                                    ✓ تأكيد وتحديد العملة
                                                </button>
                                            @elseif($booking->status === 'confirmed')
                                                <form action="{{ route('bookings.checkin', $booking) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">تسجيل دخول</button>
                                                </form>
                                            @elseif($booking->status === 'checked_in')
                                                <a href="{{ route('bookings.checkout.form', $booking) }}" class="btn btn-warning btn-sm">تسجيل خروج وتسديد</a>
                                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#requestCleaningModal{{ $booking->id }}" title="طلب تنظيف للغرفة والنزيل مقيم">
                                                    🧹 طلب تنظيف
                                                </button>
                                            @endif

                                            <a href="{{ route('bookings.edit', $booking->id) }}" class="btn btn-outline-primary btn-sm">تعديل</a>

                                            {{-- زر حذف الحجز مع طلب السبب --}}
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteBookingModal{{ $booking->id }}">
                                                حذف
                                            </button>
                                        @endif
                                    </div>

                                    @if($booking->status === 'pending')
                                        {{-- Modal تأكيد الحجز وتحديد العملة --}}
                                        <div class="modal fade text-start" id="confirmBookingModal{{ $booking->id }}" tabindex="-1" aria-labelledby="confirmBookingLabel{{ $booking->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content shadow border-0">
                                                    <form action="{{ route('bookings.confirm', $booking->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title fs-6 fw-bold" id="confirmBookingLabel{{ $booking->id }}">
                                                                🛎️ تأكيد الحجز وتحديد عملة الدفع والفاتورة
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-3">
                                                                هذا الحجز تم تسجيله من قبل الجمهور كحجز معلق. لتأكيده، يرجى اختيار العملة المعتمدة لحساب وإصدار فاتورة هذا النزيل:
                                                            </p>

                                                            <div class="bg-light p-3 rounded mb-3 small border">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-muted">النزيل:</span>
                                                                    <span class="fw-bold">{{ $booking->guest?->name ?? '—' }} ({{ $booking->guest?->phone ?? 'بدون هاتف' }})</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-muted">الغرفة:</span>
                                                                    <span class="fw-bold">غرفة رقم {{ $booking->room?->room_number }} ({{ $booking->room?->type?->name ?? 'قياسية' }})</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-muted">الفترة:</span>
                                                                    <span>{{ $booking->check_in }} إلى {{ $booking->check_out }} ({{ $nights }} ليلة)</span>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">اختر عملة الحساب المعتمدة:</label>
                                                                <div class="d-grid gap-2">
                                                                    <label class="p-3 border rounded-3 cursor-pointer d-flex align-items-center justify-content-between bg-white hover-bg-light">
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <input type="radio" name="currency" value="SYP" checked class="form-check-input">
                                                                            <div>
                                                                                <strong class="d-block text-dark">🇸🇾 بالليرة السورية (ل.س)</strong>
                                                                                <span class="small text-muted">سعر الليلة: {{ number_format($booking->room?->price) }} ل.س</span>
                                                                            </div>
                                                                        </div>
                                                                        <strong class="text-primary fs-5">{{ number_format($calcSYP) }} ل.س</strong>
                                                                    </label>

                                                                    <label class="p-3 border rounded-3 cursor-pointer d-flex align-items-center justify-content-between bg-white hover-bg-light">
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <input type="radio" name="currency" value="USD" class="form-check-input">
                                                                            <div>
                                                                                <strong class="d-block text-dark">🇺🇸 بالدولار الأمريكي ($ USD)</strong>
                                                                                <span class="small text-muted">سعر الليلة: ${{ number_format($booking->room?->price_usd ?? 0, 2) }}</span>
                                                                            </div>
                                                                        </div>
                                                                        <strong class="text-success fs-5">${{ number_format($calcUSD, 2) }} USD</strong>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="alert alert-info py-2 small mb-0">
                                                                ✓ بمجرد التأكيد، ستتحول حالة الحجز إلى "مؤكد" وتُعتمد هذه العملة تلقائياً في فواتير الإقامة وتسجيل الخروج.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                            <button type="submit" class="btn btn-success btn-sm fw-bold px-4">✓ تأكيد الحجز واعتماد العملة</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($booking->status === 'checked_in' && $booking->room)
                                        {{-- Modal طلب تنظيف للغرفة والنزيل مقيم --}}
                                        <div class="modal fade text-start" id="requestCleaningModal{{ $booking->id }}" tabindex="-1" aria-labelledby="cleaningModalLabel{{ $booking->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content shadow border-0">
                                                    <form action="{{ route('cleaning.request') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="room_id" value="{{ $booking->room_id }}">
                                                        <div class="modal-header bg-info text-white">
                                                            <h5 class="modal-title fs-6 fw-bold" id="cleaningModalLabel{{ $booking->id }}">
                                                                🧹 إرسال طلب تنظيف فوري للغرفة (النزيل مقيم)
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="alert alert-info py-2 px-3 small mb-3">
                                                                سيتم إرسال إشعار فوري لجميع عمال وموظفي التدبير الفندقي والتنظيف لتجهيز هذه الغرفة أثناء إقامة النزيل.
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-muted">الغرفة والنزيل:</label>
                                                                <div class="p-2 bg-light rounded border small">
                                                                    <strong>غرفة رقم {{ $booking->room->room_number }}</strong> — النزيل: {{ $booking->guest?->name ?? '—' }}
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small">ملاحظات أو تعليمات خاصة لعمال التنظيف:</label>
                                                                <textarea name="notes" class="form-control" rows="3" placeholder="مثال: تنظيف وتغيير المناشف والفرش بعد استئذان النزيل..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                            <button type="submit" class="btn btn-info btn-sm text-white fw-bold">🚀 إرسال طلب التنظيف فوراً</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Modal حذف الحجز وطلب السبب --}}
                                    <div class="modal fade text-start" id="deleteBookingModal{{ $booking->id }}" tabindex="-1" aria-labelledby="deleteBookingLabel{{ $booking->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content shadow">
                                                <form action="{{ route('bookings.destroy', $booking->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title fs-6 fw-bold" id="deleteBookingLabel{{ $booking->id }}">
                                                            ⚠️ تأكيد حذف الحجز وتوثيق السبب
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning py-2 mb-3 small">
                                                            <strong>تنبيه:</strong> سيتم حذف الحجز نهائياً، وسيتم تفريغ الغرفة تلقائياً وتوثيق كافة بيانات الحجز في <strong>سجل المحذوفات</strong> مع اسم المستخدم وتاريخ الإجراء.
                                                        </div>

                                                        <div class="bg-light p-2 rounded mb-3 small">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted">النزيل:</span>
                                                                <span class="fw-bold">{{ $booking->guest?->name ?? '—' }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted">الغرفة:</span>
                                                                <span class="fw-bold">غرفة {{ $booking->room?->room_number ?? '—' }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted">الفترة:</span>
                                                                <span>من {{ $booking->check_in }} إلى {{ $booking->check_out }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span class="text-muted">قيمة الحجز:</span>
                                                                <span class="fw-bold text-danger">{{ number_format($booking->total_price) }} ل.س</span>
                                                            </div>
                                                        </div>

                                                        <div class="mb-2">
                                                            <label class="form-label fw-bold small text-danger">
                                                                سبب الحذف <span class="text-danger">*</span> (إلزامي للتوثيق والرقابة):
                                                            </label>
                                                            <textarea name="reason" rows="3" class="form-control" placeholder="يرجى كتابة سبب الحذف بالتفصيل (مثل: إلغاء بطلب النزيل واسترجاع العربون، إدخال مكرر بالخطأ...)" required minlength="3" maxlength="500"></textarea>
                                                            <div class="form-text small">سيتم حفظ هذا السبب في سجل المحذوفات بالداشبورد للمراجعة المحاسبية والإدارية.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light py-2">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold">تأكيد الحذف وحفظ في السجل</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- نهاية الـ Modal --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="fs-1 mb-2">📅</div>
                                    لا توجد حجوزات مسجلة حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('bookingTableSearch');
    const filterButtons = document.querySelectorAll('.filter-status-btn');
    const rows = document.querySelectorAll('.booking-row');
    const visibleCountSpan = document.getElementById('visibleBookingCount');

    let currentStatus = 'all';
    let currentSearch = '';

    function filterTable() {
        let count = 0;
        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const rowText = row.innerText.toLowerCase();

            const matchesStatus = (currentStatus === 'all') || (rowStatus === currentStatus);
            const matchesSearch = !currentSearch || rowText.includes(currentSearch);

            if (matchesStatus && matchesSearch) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        if (visibleCountSpan) {
            visibleCountSpan.textContent = count;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            currentSearch = e.target.value.trim().toLowerCase();
            filterTable();
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => {
                b.classList.remove('active');
                if (b.classList.contains('btn-primary') && b.getAttribute('data-status') === 'all') {
                    b.classList.replace('btn-primary', 'btn-outline-primary');
                }
            });
            this.classList.add('active');

            currentStatus = this.getAttribute('data-status');
            filterTable();
        });
    });
});
</script>
@endsection
