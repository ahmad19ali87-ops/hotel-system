@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">سجل حجوزات العميل: {{ $guest->name }}</h2>

    <div class="card p-3 mb-4 shadow">
        <h5>بيانات العميل</h5>
        <p><strong>الهاتف:</strong> {{ $guest->phone }}</p>
        <p><strong>البريد:</strong> {{ $guest->email }}</p>
        <p><strong>رقم الهوية:</strong> {{ $guest->national_id }}</p>
        <p><strong>ملاحظات:</strong> {{ $guest->notes }}</p>
    </div>

    <h4 class="mb-3">جميع الحجوزات</h4>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>الغرفة</th>
                <th>الدخول</th>
                <th>الخروج</th>
                <th>السعر</th>
                <th>الحالة</th>
                <th>خيارات</th>
            </tr>
        </thead>

        <tbody>
            @foreach($bookings as $booking)
                <tr>
                    <td>غرفة رقم {{ $booking->room->room_number }}</td>
                    <td>{{ $booking->check_in }}</td>
                    <td>{{ $booking->check_out }}</td>
                    <td>{{ number_format($booking->total_price) }} ل.س</td>

                    <td>
                        @if($booking->status == 'confirmed')
                            <span class="text-primary">مؤكد</span>
                        @elseif($booking->status == 'checked_in')
                            <span class="text-success">تم تسجيل الدخول</span>
                        @elseif($booking->status == 'checked_out')
                            <span class="text-secondary">تم تسجيل الخروج</span>
                        @else
                            <span class="text-danger">ملغي</span>
                        @endif
                    </td>

                    <td>
                        <a href="{{ route('bookings.edit', $booking->id) }}" class="btn btn-warning btn-sm">عرض الحجز</a>

                        @php
                            $invoice = \App\Models\Invoice::where('booking_id', $booking->id)->first();
                        @endphp

                        @if($invoice)
                            <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-info btn-sm">عرض الفاتورة</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>

</div>
@endsection
