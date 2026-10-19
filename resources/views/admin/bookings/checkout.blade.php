@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 800px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    @if($booking->hotel?->logo_url)
                        <img src="{{ $booking->hotel->logo_url }}" alt="شعار الفندق" style="width: 55px; height: 55px; object-fit: contain;">
                    @endif
                    <div>
                        <h4 class="mb-1 text-primary fw-bold">{{ $booking->hotel?->name }}</h4>
                        <p class="mb-0 text-muted small">إجراءات تسجيل المغادرة للحجز رقم #{{ $booking->id }}</p>
                    </div>
                </div>
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                    العودة للحجوزات
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            @if(session('status'))
                <div class="alert alert-success mb-4">{{ session('status') }}</div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
            @endif

            {{-- ملخص الحجز والنزيل --}}
            @php
                $checkoutCurrency = $booking->currency ?? ($invoice?->currency ?? 'SYP');
                $currSymbol = ($checkoutCurrency === 'USD') ? '$ USD' : 'ل.س';
            @endphp
            <div class="bg-light rounded p-3 mb-4 border">
                <div class="row g-2">
                    <div class="col-sm-6">النزيل: <strong>{{ $booking->guest?->name ?? '—' }}</strong></div>
                    <div class="col-sm-6">الغرفة: <strong>غرفة {{ $booking->room?->room_number ?? '—' }}</strong></div>
                    <div class="col-sm-6">تاريخ الدخول: <strong>{{ $booking->check_in }}</strong></div>
                    <div class="col-sm-6">تاريخ الخروج: <strong>{{ $booking->check_out }}</strong></div>
                    <div class="col-12 mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span>قيمة الإقامة الأصلية:</span>
                        <strong id="room-amount" data-amount="{{ $booking->total_price }}" class="text-primary fs-5">
                            {{ number_format((float) $booking->total_price) }} {{ $currSymbol }}
                        </strong>
                    </div>
                </div>
            </div>

            @if(isset($invoice) && $invoice && $invoice->status === 'paid')
                {{-- في حال كانت الفاتورة مسددة مسبقًا --}}
                <div class="alert alert-success border-success bg-success bg-opacity-10 p-4 mb-4 rounded-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-success text-white rounded-circle p-2 fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            ✓
                        </div>
                        <div>
                            <h5 class="alert-heading fw-bold mb-1 text-success">الفاتورة مسددة مسبقاً</h5>
                            <p class="mb-0 text-muted small">تم سداد وتحصيل كامل مستحقات هذا الحجز، ولا تترتب أي مبالغ إضافية على النزيل.</p>
                        </div>
                    </div>

                    <div class="card border-0 bg-white p-3 mb-3 shadow-sm rounded-3">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">📄 بيانات الفاتورة المسددة:</h6>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">رقم الفاتورة:</span>
                                <strong class="fs-6 text-primary">{{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">المبلغ الإجمالي المسدد:</span>
                                <strong class="fs-5 text-success">{{ number_format((float) $invoice->total) }} {{ ($invoice->currency === 'USD') ? '$ USD' : 'ل.س' }}</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">طريقة الدفع:</span>
                                <span class="badge bg-light text-dark border">
                                    @php $lastPay = $invoice->payments->last(); @endphp
                                    @if($lastPay?->payment_method === 'card') 💳 بطاقة مصرفية
                                    @elseif($lastPay?->payment_method === 'transfer') 🏦 تحويل بنكي
                                    @else 💵 نقدًا
                                    @endif
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">تاريخ ووقت السداد:</span>
                                <span class="text-muted small" dir="ltr">{{ $lastPay?->paid_at ? \Carbon\Carbon::parse($lastPay->paid_at)->format('Y-m-d H:i') : $invoice->updated_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-top text-end">
                            <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                👁️ معاينة وطباعة الفاتورة المسددة
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('bookings.checkout', $booking) }}">
                        @csrf
                        <div class="text-center pt-2">
                            <button type="submit" class="btn btn-success btn-lg px-5 py-3 fw-bold shadow">
                                ✓ موافق - تسجيل خروج العميل وتفريغ الغرفة
                            </button>
                        </div>
                    </form>
                </div>

            @else
                {{-- في حال لم تكن مسددة بعد، يتم عرض نموذج التسوية والدفع --}}
                <form method="POST" action="{{ route('bookings.checkout', $booking) }}" onsubmit="return confirm('سيتم تأكيد استلام المبلغ وإنهاء الحجز. متابعة؟')">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">قيمة الخدمات الإضافية</label>
                        <div class="input-group">
                            <input id="service-amount" name="service_amount" type="number" min="0" step="0.01" value="{{ old('service_amount', 0) }}" class="form-control">
                            <span class="input-group-text">{{ $currSymbol }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">تفاصيل الخدمات</label>
                        <textarea name="service_description" maxlength="1000" rows="2" class="form-control" placeholder="طعام، مشروبات، غسيل، إلخ...">{{ old('service_description') }}</textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold">اسم الضريبة (إن وجدت)</label>
                            <input name="tax_name" value="{{ old('tax_name') }}" maxlength="100" class="form-control" placeholder="مثال: ضريبة القيمة المضافة">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-bold">نسبة الضريبة (%)</label>
                            <input id="tax-rate" name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate', 0) }}" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">طريقة الدفع</label>
                        <select name="payment_method" required class="form-select">
                            <option value="cash">💵 نقدًا</option>
                            <option value="card">💳 بطاقة مصرفية</option>
                            <option value="transfer">🏦 تحويل بنكي</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">رقم العملية / المرجع (اختياري)</label>
                        <input name="transaction_id" value="{{ old('transaction_id') }}" class="form-control" placeholder="رقم الإيصال أو الحوالة">
                    </div>

                    <div class="alert alert-primary py-3 mb-4 d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold">الإجمالي المستحق للدفع:</span>
                        <span class="fs-4 fw-bold text-success"><span id="invoice-total">{{ number_format((float) $booking->total_price) }}</span> {{ $currSymbol }}</span>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                        ✓ تأكيد استلام الدفع وتسجيل الخروج
                    </button>
                </form>

                <script>
                    const roomAmount = Number(document.getElementById('room-amount').dataset.amount);
                    const serviceInput = document.getElementById('service-amount');
                    const taxRateInput = document.getElementById('tax-rate');
                    const invoiceTotal = document.getElementById('invoice-total');
                    const isUSD = '{{ $checkoutCurrency }}' === 'USD';
                    const updateTotal = () => {
                        const services = Math.max(0, Number(serviceInput.value) || 0);
                        const taxRate = Math.min(100, Math.max(0, Number(taxRateInput.value) || 0));
                        const base = roomAmount + services;
                        const totalCalc = base + (base * taxRate / 100);
                        invoiceTotal.textContent = totalCalc.toLocaleString('en-US', {
                            minimumFractionDigits: isUSD ? 2 : 0,
                            maximumFractionDigits: 2
                        });
                    };
                    serviceInput.addEventListener('input', updateTotal);
                    taxRateInput.addEventListener('input', updateTotal);
                </script>
            @endif
        </div>
    </div>
</div>
@endsection