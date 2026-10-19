@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- App Header Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">💼 النظام المحاسبي ودفتر القيود</h2>
            <p class="text-muted mb-0 small">إدارة الحركات المالية، القيود اليومية، الصناديق، ومتابعة الأرباح والمصروفات.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->role !== 'hotel_owner')
                <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <span>➕</span>
                    <span>إضافة قيد محاسبي جديد</span>
                </a>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 border border-warning">
                    <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
                </span>
            @endif
            <a href="{{ route('accounts.reports') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <span>📊</span>
                <span>التقارير المالية والطباعة</span>
            </a>
            <a href="{{ route('deletion-logs.index') }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm">
                <span>🗑️</span>
                <span>سجل المحذوفات</span>
            </a>
        </div>
    </div>

    {{-- Financial KPI Cards --}}
    @php
        $balanceSYP = ($incomeSYP ?? 0) - ($expensesSYP ?? 0);
        $balanceUSD = ($incomeUSD ?? 0) - ($expensesUSD ?? 0);
        $totalTransactions = $accounts->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي الوارد (الإيرادات)</span>
                    <h4 class="fw-bold mb-0 text-success mt-1">{{ number_format($incomeSYP ?? 0) }} <small class="fs-6">ل.س</small></h4>
                    @if(($incomeUSD ?? 0) > 0)
                        <div class="text-primary small fw-semibold mt-1">+ ${{ number_format($incomeUSD, 2) }} USD</div>
                    @endif
                    <small class="text-muted">التدفقات النقدية الداخلة</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي الصادر (المصروفات)</span>
                    <h4 class="fw-bold mb-0 text-danger mt-1">{{ number_format($expensesSYP ?? 0) }} <small class="fs-6">ل.س</small></h4>
                    @if(($expensesUSD ?? 0) > 0)
                        <div class="text-danger small fw-semibold mt-1">- ${{ number_format($expensesUSD, 2) }} USD</div>
                    @endif
                    <small class="text-muted">تكاليف التشغيل والمصروفات</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 {{ $balanceSYP >= 0 ? 'border-primary' : 'border-warning' }} h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">صافي الرصيد (ل.س)</span>
                    <h4 class="fw-bold mb-0 {{ $balanceSYP >= 0 ? 'text-primary' : 'text-danger' }} mt-1">
                        {{ number_format($balanceSYP) }} <small class="fs-6">ل.س</small>
                    </h4>
                    <small class="text-muted">{{ $balanceSYP >= 0 ? 'فائض تشغيلي بالليرة' : 'عجز مؤقت بالليرة' }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">صافي الرصيد بالدولار ($)</span>
                    <h4 class="fw-bold mb-0 text-info mt-1">${{ number_format($balanceUSD, 2) }} <small class="fs-6">USD</small></h4>
                    <small class="text-muted">إجمالي السيولة المنفصلة بالدولار</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Tabs and Filter Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white p-3 border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <ul class="nav nav-pills" id="accountTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active px-4 fw-bold" id="journal-tab" data-bs-toggle="tab" data-bs-target="#journal-pane" type="button" role="tab">
                            📒 دفتر القيود واليومية ({{ $accounts->count() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-4 fw-bold" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices-pane" type="button" role="tab">
                            🧾 فواتير الإقامة والحجوزات ({{ $invoices->count() }})
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="min-width: 250px;">
                        <span class="input-group-text bg-white">🔍</span>
                        <input type="text" id="accountSearchInput" class="form-control" placeholder="بحث فوري في القيود...">
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active account-filter-btn" data-type="all">الكل</button>
                        <button type="button" class="btn btn-outline-success account-filter-btn" data-type="income">وارد</button>
                        <button type="button" class="btn btn-outline-danger account-filter-btn" data-type="expense">صادر</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="accountTabsContent">
                {{-- تبويب دفتر القيود --}}
                <div class="tab-pane fade show active" id="journal-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="journalTable">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">نوع القيد</th>
                                    <th>البيان / الوصف</th>
                                    <th>المبلغ</th>
                                    <th>تاريخ الحركة</th>
                                    <th>الملاحظات والتفاصيل</th>
                                    <th>المصدر</th>
                                    <th class="text-end pe-3">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $account)
                                    <tr class="account-row" data-type="{{ $account->type }}">
                                        <td class="ps-3">
                                            @if($account->type === 'income')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    ↓ وارد (إيراد)
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                    ↑ صادر (مصروف)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $account->title }}</td>
                                        <td class="fw-bold {{ $account->type === 'income' ? 'text-success' : 'text-danger' }}">
                                            {{ number_format((float)$account->amount, 2) }}
                                            <span class="badge {{ ($account->currency ?? 'SYP') === 'USD' ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-secondary-subtle text-secondary' }} border ms-1 small">
                                                {{ ($account->currency ?? 'SYP') === 'USD' ? '$ USD' : 'ل.س' }}
                                            </span>
                                        </td>
                                        <td class="text-muted small" dir="ltr">{{ $account->transaction_date }}</td>
                                        <td class="small text-muted">{{ $account->notes ?: '—' }}</td>
                                        <td>
                                            @if($account->invoice_id)
                                                <span class="badge bg-light text-primary border">فاتورة #{{ $account->invoice?->invoice_number ?: $account->invoice_id }}</span>
                                            @else
                                                <span class="badge bg-light text-muted border">قيد يدوي</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            @if(auth()->user()->role === 'hotel_owner')
                                                @if($account->invoice_id)
                                                    <a href="{{ route('invoices.show', $account->invoice_id) }}" class="btn btn-outline-info btn-sm">
                                                        عرض الفاتورة
                                                    </a>
                                                @else
                                                    <span class="badge bg-light text-muted border">اطلاع فقط</span>
                                                @endif
                                            @else
                                                @if(!$account->invoice_id)
                                                    <div class="d-inline-flex gap-1">
                                                        <a href="{{ route('accounts.edit', $account) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                                                        <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا القيد المحاسبي؟');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <a href="{{ route('invoices.show', $account->invoice_id) }}" class="btn btn-outline-info btn-sm">
                                                        عرض الفاتورة
                                                    </a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            لا توجد قيود محاسبية مسجلة حتى الآن.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- تبويب فواتير الإقامة --}}
                <div class="tab-pane fade" id="invoices-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">رقم الفاتورة</th>
                                    <th>النزيل</th>
                                    <th>الغرفة</th>
                                    <th>قيمة الإقامة</th>
                                    <th>الضريبة</th>
                                    <th>الإجمالي</th>
                                    <th>الحالة</th>
                                    <th>طريقة السداد</th>
                                    <th class="text-end pe-3">عرض</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                    <tr>
                                        <td class="ps-3 fw-bold">
                                            <a href="{{ route('invoices.show', $inv->id) }}" class="text-decoration-none">
                                                {{ $inv->invoice_number ?: ('#' . $inv->id) }}
                                            </a>
                                        </td>
                                        <td>{{ $inv->booking?->guest?->name ?? '—' }}</td>
                                        <td><span class="badge bg-light text-dark border">غرفة {{ $inv->room?->room_number ?? '—' }}</span></td>
                                        <td>{{ number_format((float)$inv->amount) }} ل.س</td>
                                        <td class="text-muted">{{ number_format((float)$inv->tax) }} ل.س</td>
                                        <td class="fw-bold text-primary">{{ number_format((float)$inv->total) }} ل.س</td>
                                        <td>
                                            @if($inv->status === 'paid')
                                                <span class="badge bg-success">مدفوعة</span>
                                            @else
                                                <span class="badge bg-warning text-dark">غير مدفوعة</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $inv->payments->last()?->payment_method ?? '—' }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-outline-primary btn-sm">
                                                معاينة
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            لا توجد فواتير نزلاء مسجلة.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('accountSearchInput');
    const filterButtons = document.querySelectorAll('.account-filter-btn');
    const rows = document.querySelectorAll('.account-row');

    let currentType = 'all';
    let currentSearch = '';

    function filterAccounts() {
        rows.forEach(row => {
            const rowType = row.getAttribute('data-type');
            const rowText = row.innerText.toLowerCase();

            const matchesType = (currentType === 'all') || (rowType === currentType);
            const matchesSearch = !currentSearch || rowText.includes(currentSearch);

            if (matchesType && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            currentSearch = e.target.value.trim().toLowerCase();
            filterAccounts();
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            currentType = this.getAttribute('data-type');
            filterAccounts();
        });
    });
});
</script>
@endsection