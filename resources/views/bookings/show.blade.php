@extends('layouts.app')

@section('content')
<h3 class="text-xl font-bold mt-6 mb-2">Payment History</h3>

@if($invoice->payments->count() == 0)
    <p class="text-gray-500">No payments recorded.</p>
@else
    <table class="w-full border-collapse">
        <tr class="bg-gray-100">
            <th class="border p-2">Transaction ID</th>
            <th class="border p-2">Method</th>
            <th class="border p-2">Amount</th>
            <th class="border p-2">Status</th>
            <th class="border p-2">Paid At</th>
        </tr>

        @foreach($invoice->payments as $payment)
        <tr>
            <td class="border p-2">{{ $payment->transaction_id }}</td>
            <td class="border p-2">{{ $payment->payment_method }}</td>
            <td class="border p-2">{{ number_format($payment->amount, 2) }} SAR</td>
            <td class="border p-2">{{ ucfirst($payment->status) }}</td>
            <td class="border p-2">{{ $payment->paid_at }}</td>
        </tr>
        @endforeach
    </table>
@endif

<div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">

    <h2 class="text-2xl font-bold mb-4 text-gray-700">تفاصيل الحجز رقم {{ $booking->id }}</h2>

    <div class="space-y-3 text-gray-700">

        <p><strong>الغرفة:</strong> رقم {{ $booking->room->room_number }}</p>

        <p><strong>العميل:</strong> {{ $booking->guest->name }}</p>

        <p><strong>الهاتف:</strong> {{ $booking->guest->phone }}</p>

        <p><strong>البريد:</strong> {{ $booking->guest->email }}</p>

        <p><strong>الهوية:</strong> {{ $booking->guest->national_id }}</p>

        <p><strong>تاريخ الدخول:</strong> {{ $booking->check_in }}</p>

        <p><strong>تاريخ الخروج:</strong> {{ $booking->check_out }}</p>

        <p class="text-xl font-bold text-blue-600">
            السعر: {{ $booking->total_price }} ريال
        </p>

    </div>

    <div class="mt-6">
        <a href="{{ route('bookings.index') }}"
           class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg shadow">
            رجوع
        </a>
    </div>

</div>

@endsection
