@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 800px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">📑 إنشاء فاتورة جديدة</h2>
            <p class="text-muted mb-0 small">إصدار فاتورة إقامة وخدمات مع خيار تحديد عملة الفاتورة والدفع (ليرة سورية أو دولار أمريكي).</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">العودة للفواتير</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('invoices.store') }}" method="POST">
                @csrf

                {{-- اختيار الحجز --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">اختر الحجز المرتبط بالفاتورة <span class="text-danger">*</span></label>
                    <select name="booking_id" id="booking_id" class="form-select" required>
                        <option value="">-- اختر الحجز والنزيل --</option>
                        @foreach($bookings as $booking)
                            @php
                                $sypAmount = (float) $booking->total_price;
                                $usdAmount = (float) ($booking->total_price_usd > 0 ? $booking->total_price_usd : ($booking->room?->price_usd ?? 0));
                            @endphp
                            <option value="{{ $booking->id }}"
                                    data-price-syp="{{ $sypAmount }}"
                                    data-price-usd="{{ $usdAmount }}"
                                    {{ old('booking_id') == $booking->id ? 'selected' : '' }}>
                                حجز #{{ $booking->id }} — النزيل: {{ $booking->guest?->name ?? 'نزيل' }} (غرفة {{ $booking->room?->room_number ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- تحديد العملة وخيارات الفاتورة والدفع --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-primary">عملة الفاتورة والدفع <span class="text-danger">*</span></label>
                        <select name="currency" id="currency_select" class="form-select border-primary fw-bold" required>
                            <option value="SYP" {{ old('currency') === 'USD' ? '' : 'selected' }}>🇸🇾 بالليرة السورية (ل.س)</option>
                            <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>🇺🇸 بالدولار الأمريكي ($ USD)</option>
                        </select>
                        <small class="text-muted">الأسعار منفصلة ومحددة لكل عملة بشكل مستقل.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">قيمة الإقامة الأساسية <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" name="amount" id="amount_input" value="{{ old('amount', 0) }}" class="form-control fw-bold" required>
                            <span class="input-group-text currency-symbol">ل.س</span>
                        </div>
                    </div>
                </div>

                {{-- الخدمات الإضافية --}}
                <div class="card bg-light border-0 p-3 mb-3">
                    <h6 class="fw-bold mb-2">خدمات فندقية إضافية (اختياري)</h6>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small">قيمة الخدمات</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="service_amount" id="service_amount" value="{{ old('service_amount', 0) }}" class="form-control">
                                <span class="input-group-text currency-symbol">ل.س</span>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">وصف الخدمات</label>
                            <input type="text" name="service_description" value="{{ old('service_description') }}" class="form-control form-control-sm" placeholder="مغسلة، وجبات طعام، مشروبات، مواصلات...">
                        </div>
                    </div>
                </div>

                {{-- الضرائب --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">اسم الضريبة (اختياري)</label>
                        <input type="text" name="tax_name" maxlength="100" value="{{ old('tax_name') }}" class="form-control form-control-sm" placeholder="مثال: ضريبة الإقامة">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">نسبة الضريبة (%)</label>
                        <input id="tax-rate" type="number" name="tax_rate" min="0" max="100" step="0.01" value="{{ old('tax_rate', 0) }}" class="form-control form-control-sm">
                    </div>
                </div>

                {{-- ملخص الإجمالي النهائي --}}
                <div class="alert alert-primary py-3 mb-4 d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">إجمالي الفاتورة النهائي:</span>
                    <span class="fs-4 fw-bold text-success">
                        <span id="invoice-total">0</span>
                        <span class="currency-symbol">ل.س</span>
                    </span>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm">
                    ✓ تأكيد وحفظ الفاتورة بالعملة المختارة
                </button>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bookingSelect = document.getElementById('booking_id');
    const currencySelect = document.getElementById('currency_select');
    const amountInput = document.getElementById('amount_input');
    const serviceInput = document.getElementById('service_amount');
    const taxRateInput = document.getElementById('tax-rate');
    const totalOutput = document.getElementById('invoice-total');
    const currencySymbols = document.querySelectorAll('.currency-symbol');

    function updateCurrencyAndPrices(autoFillAmount = true) {
        const selectedCurrency = currencySelect.value;
        const symbolText = (selectedCurrency === 'USD') ? '$ USD' : 'ل.س';

        currencySymbols.forEach(el => el.textContent = symbolText);

        const selectedOption = bookingSelect.selectedOptions[0];
        if (selectedOption && autoFillAmount) {
            const priceSyp = Number(selectedOption.getAttribute('data-price-syp') || 0);
            const priceUsd = Number(selectedOption.getAttribute('data-price-usd') || 0);

            if (selectedCurrency === 'USD') {
                amountInput.value = priceUsd;
            } else {
                amountInput.value = priceSyp;
            }
        }

        recalculateTotal();
    }

    function recalculateTotal() {
        const baseAmount = Math.max(0, Number(amountInput.value) || 0);
        const serviceAmount = Math.max(0, Number(serviceInput.value) || 0);
        const taxRate = Math.min(100, Math.max(0, Number(taxRateInput.value) || 0));

        const subtotal = baseAmount + serviceAmount;
        const total = subtotal + (subtotal * taxRate / 100);

        totalOutput.textContent = new Intl.NumberFormat('ar-SA', {
            minimumFractionDigits: (currencySelect.value === 'USD') ? 2 : 0,
            maximumFractionDigits: 2
        }).format(total);
    }

    bookingSelect.addEventListener('change', function () {
        updateCurrencyAndPrices(true);
    });

    currencySelect.addEventListener('change', function () {
        updateCurrencyAndPrices(true);
    });

    amountInput.addEventListener('input', recalculateTotal);
    serviceInput.addEventListener('input', recalculateTotal);
    taxRateInput.addEventListener('input', recalculateTotal);

    updateCurrencyAndPrices(false);
});
</script>
@endsection
