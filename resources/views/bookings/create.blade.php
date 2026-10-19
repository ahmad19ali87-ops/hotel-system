@extends('layouts.app')

@section('content')

<div class="bg-white p-6 rounded-lg shadow max-w-xl mx-auto">

    <h2 class="text-2xl font-bold mb-6 text-gray-700">إنشاء حجز جديد</h2>

    <form action="{{ route('bookings.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="font-semibold">اختر الغرفة:</label>
            <select name="room_id" class="w-full border rounded-lg p-2">
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">
                        غرفة رقم {{ $room->room_number }} — {{ $room->type->name }}
                    </option>
                @endforeach
            </select>
        </div>
@if(session('error'))
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
        {{ session('error') }}
    </div>
@endif

        <div>
            <label class="font-semibold">تاريخ الدخول:</label>
            <input type="date" name="check_in" class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="font-semibold">تاريخ الخروج:</label>
            <input type="date" name="check_out" class="w-full border rounded-lg p-2">
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg shadow">
            حفظ الحجز
        </button>
<div>
    <label class="font-semibold">اختر العميل:</label>
    <select name="guest_id" class="w-full border rounded-lg p-2">
        @foreach($guests as $guest)
            <option value="{{ $guest->id }}">
                {{ $guest->name }} — {{ $guest->phone }}
            </option>
        @endforeach
    </select>
</div>


    </form>

</div>

@endsection
