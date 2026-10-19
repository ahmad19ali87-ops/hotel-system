@extends('layouts.app')

@section('content')

<div class="bg-white p-6 rounded-lg shadow">

    <h2 class="text-2xl font-bold mb-6 text-gray-700">جميع الحجوزات</h2>

    <table class="min-w-full border">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 border">الغرفة</th>
                <th class="p-3 border">الدخول</th>
                <th class="p-3 border">الخروج</th>
                <th class="p-3 border">السعر</th>
                <th class="p-3 border">الحالة</th>
                <th class="p-3 border">العميل</th>
                <th class="p-3 border">التحكم</th>
            </tr>
        </thead>

        <tbody>
            @foreach($bookings as $booking)
            <tr class="text-center border-b">
               <td class="p-3 border">

    @if($booking->status == 'confirmed')
        <span class="text-blue-600 font-semibold">مؤكد</span>
        <a href="{{ route('bookings.checkin', $booking->id) }}"
           class="ml-3 bg-green-600 text-white py-1 px-3 rounded">
            Check‑in
        </a>

    @elseif($booking->status == 'checked_in')
        <span class="text-green-600 font-semibold">تم تسجيل الدخول</span>
        <a href="{{ route('bookings.checkout', $booking->id) }}"
           class="ml-3 bg-red-600 text-white py-1 px-3 rounded">
            Check‑out
        </a>

    @elseif($booking->status == 'checked_out')
        <span class="text-gray-600 font-semibold">تم تسجيل الخروج</span>

    @else
        <span class="text-red-600 font-semibold">ملغي</span>
    @endif

</td>
<td class="p-3 border">
    {{ $booking->guest->name ?? '—' }}  
    <div class="text-gray-500 text-sm">
        {{ $booking->guest->phone ?? '' }}
    </div>
</td>
                <td class="p-3 border">{{ $booking->room->room_number }}</td>
                <td class="p-3 border">{{ $booking->check_in }}</td>
                <td class="p-3 border">{{ $booking->check_out }}</td>
                <td class="p-3 border text-blue-600 font-bold">
                    {{ number_format($booking->total_price, 2) }} ريال
                </td>
                <td class="p-3 border">
                    @if($booking->status == 'confirmed')
                        <span class="text-blue-600 font-semibold">مؤكد</span>
                    @elseif($booking->status == 'checked_in')
                        <span class="text-green-600 font-semibold">تم تسجيل الدخول</span>
                    @elseif($booking->status == 'checked_out')
                        <span class="text-gray-600 font-semibold">تم تسجيل الخروج</span>
                    @else
                        <span class="text-red-600 font-semibold">ملغي</span>
                    @endif
                </td>

                <td class="p-3 border flex justify-center gap-3">
                    <a href="{{ route('bookings.edit', $booking->id) }}"
                       class="text-blue-600 font-semibold">تعديل</a>

                    <form action="{{ route('bookings.destroy', $booking->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 font-semibold"
                                onclick="return confirm('هل أنت متأكد؟');">
                            حذف
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection
