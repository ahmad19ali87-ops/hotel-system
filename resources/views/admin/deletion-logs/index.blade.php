@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- رأس الصفحة --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1 text-danger">🗑️ سجل المحذوفات والرقابة المالية</h2>
            <p class="text-muted mb-0 small">توثيق شامل لكافة الفواتير والحجوزات المحذوفة مع توضيح سبب الحذف والتفاصيل المالية.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                ← العودة للوحة التحكم
            </a>
        </div>
    </div>

    {{-- كروت الإحصائيات السريعة --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي المحذوفات</span>
                    <h3 class="fw-bold mb-0 text-danger mt-1">{{ number_format($totalDeletedCount) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">فواتير محذوفة</span>
                    <h3 class="fw-bold mb-0 text-warning mt-1">{{ number_format($deletedInvoicesCount) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">حجوزات محذوفة</span>
                    <h3 class="fw-bold mb-0 text-info mt-1">{{ number_format($deletedBookingsCount) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-dark h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي مبالغ المحذوفات</span>
                    <div class="mt-1">
                        <div class="fw-bold text-dark fs-6">🇸🇾 {{ number_format($totalDeletedAmountSYP, 2) }} <small class="text-muted">ل.س</small></div>
                        <div class="fw-bold text-primary fs-6">🇺🇸 ${{ number_format($totalDeletedAmountUSD, 2) }} <small class="text-muted">USD</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- شريط الفلاتر والبحث --}}
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('deletion-logs.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="🔍 ابحث برقم المرجع، النزيل، الغرفة، أو السبب...">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">جميع الأنواع (فواتير وحجوزات)</option>
                        <option value="invoice" @selected(request('type') === 'invoice')>فواتير محذوفة فقط</option>
                        <option value="booking" @selected(request('type') === 'booking')>حجوزات محذوفة فقط</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">تطبيق الفلتر</button>
                    @if(request()->hasAny(['search', 'type']))
                        <a href="{{ route('deletion-logs.index') }}" class="btn btn-outline-secondary btn-sm">إلغاء الفلتر</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- جدول المحذوفات --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">النوع</th>
                            <th>المرجع</th>
                            <th>النزيل</th>
                            <th>الغرفة</th>
                            <th>المبلغ المحذوف</th>
                            <th>حالة الدفع</th>
                            <th style="min-width: 220px;">سبب الحذف</th>
                            <th>المسؤول عن الحذف</th>
                            <th>تاريخ ووقت الحذف</th>
                            <th class="text-end pe-3">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-3">
                                    @if($log->record_type === 'invoice')
                                        <span class="badge bg-danger-subtle text-danger border border-danger">فاتورة</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border border-info">حجز</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $log->reference_number ?? ('#' . $log->record_id) }}</td>
                                <td>{{ $log->guest_name ?? '—' }}</td>
                                <td>{{ $log->room_number ? 'غرفة ' . $log->room_number : '—' }}</td>
                                <td class="fw-bold text-danger">
                                    @if($log->currency === 'USD')
                                        ${{ number_format((float)$log->amount, 2) }} <span class="badge bg-primary-subtle text-primary small">USD</span>
                                    @else
                                        {{ number_format((float)$log->amount, 2) }} <small class="text-muted">ل.س</small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->payment_status === 'paid')
                                        <span class="badge bg-success">مدفوع</span>
                                    @elseif($log->payment_status === 'checked_out')
                                        <span class="badge bg-secondary">مغادر</span>
                                    @else
                                        <span class="badge bg-warning text-dark">غير مدفوع</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="p-2 bg-light rounded small border-start border-3 border-danger text-break">
                                        {{ $log->reason }}
                                    </div>
                                </td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td class="small text-muted">{{ $log->deleted_at ? $log->deleted_at->format('Y-m-d H:i') : '—' }}</td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                        📄 عرض
                                    </button>

                                    {{-- Modal تفاصيل السجل المحذوف --}}
                                    <div class="modal fade text-start" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content shadow">
                                                <div class="modal-header bg-light">
                                                    <h5 class="modal-title fs-6 fw-bold">تفاصيل السجل المحذوف ({{ $log->reference_number }})</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="mb-3 p-2 bg-danger-subtle rounded border border-danger">
                                                        <strong class="d-block text-danger small mb-1">سبب الحذف المدون:</strong>
                                                        <span>{{ $log->reason }}</span>
                                                    </div>

                                                    <ul class="list-group list-group-flush small">
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">النوع:</span>
                                                            <strong>{{ $log->record_type === 'invoice' ? 'فاتورة مالية' : 'حجز غرفة' }}</strong>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">النزيل:</span>
                                                            <strong>{{ $log->guest_name }}</strong>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">الغرفة:</span>
                                                            <strong>{{ $log->room_number }}</strong>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">القيمة:</span>
                                                            <strong class="text-primary">{{ number_format($log->amount) }} ل.س</strong>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">حالة الدفع السابقة:</span>
                                                            <strong>{{ $log->payment_status }}</strong>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">المسؤول عن الحذف:</span>
                                                            <strong>{{ $log->user?->name }} ({{ $log->user?->role }})</strong>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between">
                                                            <span class="text-muted">تاريخ الحذف:</span>
                                                            <strong>{{ $log->deleted_at }}</strong>
                                                        </li>
                                                    </ul>

                                                    @if($log->snapshot)
                                                        <div class="mt-3">
                                                            <button class="btn btn-sm btn-link text-decoration-none p-0 text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#rawJson{{ $log->id }}">
                                                                ▼ عرض البيانات الأصلية الخام
                                                            </button>
                                                            <div class="collapse mt-2" id="rawJson{{ $log->id }}">
                                                                <pre class="bg-dark text-light p-2 rounded small" style="max-height: 150px; overflow: auto; direction: ltr; text-align: left;">{{ json_encode($log->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer py-2 bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <span class="fs-1 d-block mb-2">✨</span>
                                    سجل المحذوفات نظيف، لا توجد أي عمليات حذف مسجلة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-3 border-top">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

