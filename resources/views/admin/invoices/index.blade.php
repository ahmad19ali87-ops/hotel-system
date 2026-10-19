@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- App Header Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">📑 إدارة الفواتير والتحصيل للنزلاء</h2>
            <p class="text-muted mb-0 small">متابعة الفواتير المصدرة والتحصيل والمدفوعات وسجلات الفواتير الفندقية.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->role !== 'hotel_owner')
                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                    <span>➕</span>
                    <span>إنشاء فاتورة جديدة</span>
                </a>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 border border-warning">
                    <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
                </span>
            @endif
            <a href="{{ route('deletion-logs.index') }}?type=invoice" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                <span>🗑️</span>
                <span>سجل الفواتير المحذوفة</span>
            </a>
        </div>
    </div>

    {{-- Quick KPI Metrics Cards --}}
    @php
        $totalInvoicesCount = $invoices->count();
        $paidInvoicesCount = $invoices->where('status', 'paid')->count();
        $unpaidInvoicesCount = $invoices->where('status', '!==', 'paid')->count();
        $totalInvoicesAmount = $invoices->sum('total');
        $paidInvoicesAmount = $invoices->where('status', 'paid')->sum('total');
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي الفواتير</span>
                    <h4 class="fw-bold mb-0 text-primary mt-1">{{ number_format($totalInvoicesCount) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">الفواتير المسددة</span>
                    <h4 class="fw-bold mb-0 text-success mt-1">{{ number_format($paidInvoicesCount) }} <span class="fs-6 text-muted font-normal">({{ number_format($paidInvoicesAmount) }} ل.س)</span></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">فواتير تنتظر التحصيل</span>
                    <h4 class="fw-bold mb-0 text-warning mt-1">{{ number_format($unpaidInvoicesCount) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 border-start border-4 border-dark h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي المبالغ المصدرة</span>
                    <h4 class="fw-bold mb-0 text-dark mt-1">{{ number_format($totalInvoicesAmount) }} <small class="fs-6 text-muted">ل.س</small></h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Search & Filter Toolbar --}}
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">🔍</span>
                        <input type="text" id="invoiceTableSearch" class="form-control border-start-0" placeholder="بحث فوري برقم الفاتورة، اسم النزيل، أو رقم الغرفة...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active filter-status-btn" data-status="all">الكل</button>
                        <button type="button" class="btn btn-outline-success filter-status-btn" data-status="paid">مدفوعة</button>
                        <button type="button" class="btn btn-outline-warning filter-status-btn" data-status="unpaid">غير مدفوعة</button>
                    </div>
                </div>
                <div class="col-md-2 text-md-end text-muted small">
                    المعروض: <span id="visibleCount" class="fw-bold">{{ $invoices->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="invoicesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">رقم الفاتورة</th>
                            <th>الغرفة</th>
                            <th>النزيل</th>
                            <th>قيمة الإقامة</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th class="text-end pe-3" style="min-width: 250px;">خيارات وإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr class="invoice-row" data-status="{{ $invoice->status === 'paid' ? 'paid' : 'unpaid' }}">
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="text-decoration-none">
                                        {{ $invoice->invoice_number ?: ('#' . $invoice->id) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">غرفة {{ $invoice->room?->room_number ?? '—' }}</span>
                                </td>
                                <td class="fw-semibold">{{ $invoice->booking?->guest?->name ?? '—' }}</td>
                                <td>{{ number_format((float)$invoice->amount, 2) }} {{ $invoice->currency === 'USD' ? '$' : 'ل.س' }}</td>
                                <td class="text-muted">{{ number_format((float)$invoice->tax, 2) }} {{ $invoice->currency === 'USD' ? '$' : 'ل.س' }}</td>
                                <td class="fw-bold text-primary">
                                    {{ number_format((float)$invoice->total, 2) }}
                                    <span class="badge {{ $invoice->currency === 'USD' ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-secondary-subtle text-secondary' }} border small">
                                        {{ $invoice->currency === 'USD' ? '$ USD' : 'ل.س' }}
                                    </span>
                                </td>
                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="badge bg-success">✓ مدفوعة</span>
                                    @else
                                        <span class="badge bg-warning text-dark">⏳ غير مدفوعة</span>
                                    @endif
                                </td>

                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-info btn-sm" title="عرض الفاتورة">
                                            عرض
                                        </a>

                                        @if(auth()->user()->role !== 'hotel_owner')
                                            @if($invoice->status !== 'paid')
                                                <form action="{{ route('invoices.pay', $invoice) }}" method="POST" class="d-inline-flex gap-1" onsubmit="return confirm('تأكيد استلام وتسديد مبلغ هذه الفاتورة؟');">
                                                    @csrf @method('PUT')
                                                    <select name="payment_method" required class="form-select form-select-sm" style="width: auto;">
                                                        <option value="cash">نقدًا</option>
                                                        <option value="card">بطاقة</option>
                                                        <option value="transfer">تحويل</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-success btn-sm">سداد</button>
                                                </form>
                                            @endif

                                            {{-- زر حذف الفاتورة مع طلب السبب --}}
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteInvoiceModal{{ $invoice->id }}">
                                                حذف
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Modal حذف الفاتورة وطلب السبب --}}
                                    <div class="modal fade text-start" id="deleteInvoiceModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content shadow">
                                                <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title fs-6 fw-bold">⚠️ تأكيد حذف الفاتورة {{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-3">
                                                        @if($invoice->status === 'paid')
                                                            <div class="alert alert-warning small mb-3">
                                                                <strong>تنبيه مالي مهم:</strong> هذه الفاتورة مسددة مسبقاً بمبلغ <strong>{{ number_format($invoice->total) }} ل.س</strong>.
                                                                سيتم توثيق عملية الحذف بالكامل في <strong>سجل المحذوفات الرقابي</strong>.
                                                            </div>
                                                        @else
                                                            <p class="small text-muted mb-3">أنت على وشك حذف الفاتورة الخاصة بالنزيل <strong>{{ $invoice->booking?->guest?->name }}</strong> بمبلغ <strong>{{ number_format($invoice->total) }} ل.س</strong>.</p>
                                                        @endif

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">سبب الحذف <span class="text-danger">*</span></label>
                                                            <textarea name="reason" rows="3" required minlength="3" maxlength="1000" class="form-control" placeholder="يرجى كتابة سبب الحذف بالتفصيل (مثل: خطأ في الحساب، إلغاء الحجز، تسوية إدارية)..."></textarea>
                                                            <div class="form-text small">سيتم حفظ هذا السبب في سجل المحذوفات للمراجعة والتدقيق.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer py-2 bg-light">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-danger btn-sm fw-bold">تأكيد الحذف والتوثيق بالسجل</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noInvoicesRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    لا توجد فواتير مسجلة حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Interactive Realtime Search/Filter Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('invoiceTableSearch');
    const filterButtons = document.querySelectorAll('.filter-status-btn');
    const rows = document.querySelectorAll('.invoice-row');
    const visibleCountSpan = document.getElementById('visibleCount');

    let currentStatus = 'all';

    function filterTable() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.innerText.toLowerCase();
            const rowStatus = row.dataset.status;

            const matchesSearch = !query || rowText.includes(query);
            const matchesStatus = currentStatus === 'all' || rowStatus === currentStatus;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleCount;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.dataset.status;
            filterTable();
        });
    });
});
</script>
@endsection

