@extends('layouts.app')

@section('content')

<div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">

    <!-- عنوان الفاتورة -->
    <h2 class="text-3xl font-bold mb-6 text-gray-800 text-center">
        فاتورة رقم {{ $invoice->id }}
    </h2>

    <!-- بيانات العميل -->
    <div class="mb-6">
        <h3 class="text-xl font-semibold text-gray-700 mb-3">بيانات العميل</h3>

        <div class="space-y-2 text-gray-700">
            <p><strong>الاسم:</strong> {{ $invoice->booking->guest->name }}</p>
            <p><strong>الهاتف:</strong> {{ $invoice->booking->guest->phone }}</p>
            <p><strong>البريد الإلكتروني:</strong> {{ $invoice->booking->guest->email }}</p>
            <p><strong>رقم الهوية / الإقامة:</strong> {{ $invoice->booking->guest->national_id }}</p>
        </div>
    </div>

    <hr class="my-6">

    <!-- بيانات الحجز -->
    <div class="mb-6">
        <h3 class="text-xl font-semibold text-gray-700 mb-3">تفاصيل الحجز</h3>

        <div class="space-y-2 text-gray-700">
            <p><strong>الغرفة:</strong> رقم {{ $invoice->room->room_number }}</p>
            <p><strong>تاريخ الدخول:</strong> {{ $invoice->booking->check_in }}</p>
            <p><strong>تاريخ الخروج:</strong> {{ $invoice->booking->check_out }}</p>
        </div>
    </div>

    <hr class="my-6">

    <!-- تفاصيل الفاتورة -->
    <div class="mb-6">
        <h3 class="text-xl font-semibold text-gray-700 mb-3">تفاصيل الفاتورة</h3>

        <div class="space-y-2 text-gray-700">
            <p><strong>المبلغ قبل الضريبة:</strong> {{ $invoice->amount }} ريال</p>
            <p><strong>الضريبة (15%):</strong> {{ $invoice->tax }} ريال</p>

            <p class="text-2xl font-bold text-blue-600 mt-4">
                الإجمالي: {{ $invoice->total }} ريال
            </p>
        </div>
    </div>

    <hr class="my-6">

    <!-- أزرار التحكم -->
    <div class="flex justify-between mt-6">

        <a href="{{ route('invoices.index') }}"
           class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg shadow">
            رجوع
        </a>

        <button onclick="window.print()"
                class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg shadow">
            طباعة الفاتورة
            <a href="{{ route('invoices.download', $invoice->id) }}"
   class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg shadow">
    تحميل PDF
</a>

        </button>

    </div>

</div>

@endsection
