@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 820px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    @if($booking->hotel?->logo_url)
                        <img src="{{ $booking->hotel->logo_url }}" alt="شعار الفندق" style="width: 55px; height: 55px; object-fit: contain;">
                    @endif
                    <div>
                        <h4 class="mb-1 text-primary fw-bold">{{ $booking->hotel?->name }}</h4>
                        <p class="mb-0 text-muted small">إجراءات تسجيل المغادرة واحتساب الإقامة للحجز رقم #{{ $booking->id }}</p>
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

            @php
                $checkoutCurrency = $booking->currency ?? ($invoice?->currency ?? 'SYP');
                $currSymbol = ($checkoutCurrency === 'USD') ? '$ USD' : 'ل.س';
                $effActualCheckIn = $actualCheckIn ?? ($booking->actual_check_in_at ?: \Carbon\Carbon::parse($booking->check_in . ' 12:00:00'));
                $effActualCheckOut = $actualCheckOut ?? \Carbon\Carbon::now();
                $effDays = $calculatedDays ?? \App\Services\HotelStayCalculatorService::calculateHotelNights($effActualCheckIn, $effActualCheckOut);
                $effNightlyRate = $nightlyRate ?? \App\Services\HotelStayCalculatorService::getNightlyRate($booking);
                $effRoomAmount = $calculatedRoomAmount ?? round($effDays * $effNightlyRate, 2);
            @endphp

            {{-- بطاقة تفاصيل الإقامة والتوقيت الفعلي --}}
            <div class="bg-light rounded p-3 mb-4 border">
                <div class="row g-2 small">
                    <div class="col-sm-6">النزيل: <strong class="text-dark">{{ $booking->guest?->name ?? '—' }}</strong></div>
                    <div class="col-sm-6">الغرفة: <strong class="text-primary">غرفة {{ $booking->room?->room_number ?? '—' }} ({{ $booking->room?->roomType?->name ?? ($booking->room?->type ?? 'غرفة فندقية') }})</strong></div>
                    <div class="col-sm-6 text-muted">تاريخ الحجز المسبق: <strong>{{ $booking->check_in }} ⬅️ {{ $booking->check_out }}</strong></div>
                    <div class="col-sm-6 text-success">توقيت الدخول الفعلي: <strong>{{ \Carbon\Carbon::parse($effActualCheckIn)->format('Y-m-d h:i A') }}</strong></div>
                    <div class="col-sm-6 text-danger">توقيت المغادرة الحالي: <strong>{{ \Carbon\Carbon::parse($effActualCheckOut)->format('Y-m-d h:i A') }}</strong></div>
                    <div class="col-sm-6">العملة المعتمدة: <strong>{{ $checkoutCurrency === 'USD' ? 'دولار أمريكي (USD)' : 'ليرة سورية (SYP)' }}</strong></div>
                </div>
            </div>

            {{-- تنبيه احتساب اليوم الفندقي 12:00 ظهراً --}}
            <div class="alert alert-info border-info bg-info bg-opacity-10 d-flex align-items-center gap-2 mb-4 p-3 rounded-3">
                <div class="fs-4 text-info">ℹ️</div>
                <div class="small">
                    <strong>نظام اليوم الفندقي العملي (12:00 ظهراً - 12:00 ظهراً):</strong>
                    تم احتساب مدة الإقامة تلقائياً <strong>({{ $effDays }} {{ $effDays > 1 ? 'أيام' : 'يوم' }})</strong> بناءً على ساعة تسجيل الوصول وتوقيت المغادرة الفعلي.
                    <span class="text-muted d-block mt-1">تستطيع تعديل عدد الأيام أو سعر الليلة يدوياً في الخانات أدناه متى ما تطلبت سياسة الاستقبال أو الإدارة ذلك.</span>
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
                                <span class="text-muted d-block small">الأيام وسعر الليلة:</span>
                                <strong>{{ $invoice->nights ?? $effDays }} ليالٍ × {{ number_format((float) ($invoice->nightly_rate ?? $effNightlyRate)) }} {{ $currSymbol }}</strong>
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
                {{-- نموذج التسوية والدفع التلقائي مع خيارات التعديل اليدوي --}}
                <form method="POST" action="{{ route('bookings.checkout', $booking) }}" onsubmit="return confirm('سيتم تأكيد احتساب الأيام واستلام المبلغ وإنهاء الحجز. متابعة؟')">
                    @csrf

                    {{-- قسم احتساب أيام الإقامة وسعر الليلة --}}
                    <div class="card border border-primary border-opacity-25 mb-4 shadow-none">
                        <div class="card-header bg-primary bg-opacity-10 py-2">
                            <h6 class="mb-0 fw-bold text-primary">🏨 احتساب الإقامة الفندقية الفعلي</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">عدد الأيام / الليالي المحتسبة *</label>
                                    <div class="input-group">
                                        <input id="days-input" name="days" type="number" min="1" step="1" value="{{ old('days', $effDays) }}" required class="form-control fw-bold text-center">
                                        <span class="input-group-text small">أيام</span>
                                    </div>
                                    <div class="form-text small text-muted">تلقائي وفق دورة 12 ظهراً</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">سعر الليلة الواحدة *</label>
                                    <div class="input-group">
                                        <input id="nightly-rate-input" name="nightly_rate" type="number" min="0" step="0.01" value="{{ old('nightly_rate', $effNightlyRate) }}" required class="form-control fw-bold">
                                        <span class="input-group-text small">{{ $currSymbol }}</span>
                                    </div>
                                    <div class="form-text small text-muted">سعر حجز الغرفة الأساسي</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">أجرة الإقامة الإجمالية</label>
                                    <div class="p-2 border rounded bg-white text-center">
                                        <strong id="display-room-amount" class="text-primary fs-5">{{ number_format($effRoomAmount, 2) }}</strong>
                                        <span class="small text-muted">{{ $currSymbol }}</span>
                                    </div>
                                    <input type="hidden" id="room-amount-hidden" name="room_amount" value="{{ $effRoomAmount }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- قسم الخدمات الإضافية والضريبة --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold small">قيمة الخدمات الإضافية أثناء الإقامة (ميني بار، مغسلة، مطعم...)</label>
                        <div class="input-group">
                            <input id="service-amount" name="service_amount" type="number" min="0" step="0.01" value="{{ old('service_amount', 0) }}" class="form-control">
                            <span class="input-group-text">{{ $currSymbol }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">تفاصيل الخدمات الإضافية</label>
                        <textarea name="service_description" maxlength="1000" rows="2" class="form-control" placeholder="طعام، مشروبات، غسيل، إلخ...">{{ old('service_description') }}</textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold small">اسم الضريبة / الرسوم (إن وجدت)</label>
                            <input name="tax_name" value="{{ old('tax_name') }}" maxlength="100" class="form-control" placeholder="مثال: ضريبة القيمة المضافة">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-bold small">نسبة الضريبة (%)</label>
                            <input id="tax-rate" name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate', 0) }}" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold small">طريقة الدفع والتحصيل *</label>
                            <select name="payment_method" required class="form-select">
                                <option value="cash">💵 نقدًا (كاش)</option>
                                <option value="card">💳 بطاقة مصرفية (POS)</option>
                                <option value="transfer">🏦 تحويل بنكي</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-bold small">رقم العملية / المرجع (اختياري)</label>
                            <input name="transaction_id" value="{{ old('transaction_id') }}" class="form-control" placeholder="رقم الإيصال أو الحوالة">
                        </div>
                    </div>

                    {{-- شريط الإجمالي النهائي --}}
                    <div class="alert alert-primary py-3 mb-4 d-flex justify-content-between align-items-center rounded-3 shadow-sm">
                        <div>
                            <span class="fs-5 fw-bold d-block">المبلغ الإجمالي المستحق للسداد:</span>
                            <span class="small text-muted">(يشمل أجرة الإقامة الفعلية + الخدمات الإضافية + الضرائب)</span>
                        </div>
                        <div class="text-end">
                            <span class="fs-3 fw-bold text-success"><span id="invoice-total">{{ number_format($effRoomAmount, 2) }}</span> {{ $currSymbol }}</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow">
                        ✓ تأكيد الحساب واستلام الدفع وتسجيل الخروج
                    </button>
                </form>

                <script>
                    const daysInput = document.getElementById('days-input');
                    const nightlyRateInput = document.getElementById('nightly-rate-input');
                    const displayRoomAmount = document.getElementById('display-room-amount');
                    const roomAmountHidden = document.getElementById('room-amount-hidden');
                    const serviceInput = document.getElementById('service-amount');
                    const taxRateInput = document.getElementById('tax-rate');
                    const invoiceTotal = document.getElementById('invoice-total');
                    const isUSD = '{{ $checkoutCurrency }}' === 'USD';

                    const updateTotal = () => {
                        const days = Math.max(1, parseInt(daysInput.value) || 1);
                        const nightlyRate = Math.max(0, parseFloat(nightlyRateInput.value) || 0);
                        const roomAmount = Math.round((days * nightlyRate) * 100) / 100;

                        displayRoomAmount.textContent = roomAmount.toLocaleString('en-US', {
                            minimumFractionDigits: isUSD ? 2 : 0,
                            maximumFractionDigits: 2
                        });
                        roomAmountHidden.value = roomAmount;

                        const services = Math.max(0, parseFloat(serviceInput.value) || 0);
                        const taxRate = Math.min(100, Math.max(0, parseFloat(taxRateInput.value) || 0));
                        const base = roomAmount + services;
                        const totalCalc = base + (base * taxRate / 100);

                        invoiceTotal.textContent = totalCalc.toLocaleString('en-US', {
                            minimumFractionDigits: isUSD ? 2 : 0,
                            maximumFractionDigits: 2
                        });
                    };

                    daysInput.addEventListener('input', updateTotal);
                    nightlyRateInput.addEventListener('input', updateTotal);
                    serviceInput.addEventListener('input', updateTotal);
                    taxRateInput.addEventListener('input', updateTotal);
                    updateTotal();
                </script>
            @endif
        </div>
    </div>
</div>
@endsection
