@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">تسجيل شكوى جديدة</h2>

    <form action="{{ route('complaints.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>العميل (اختياري)</label>
            <select name="guest_id" class="form-select">
                <option value="">بدون</option>
                @foreach($guests as $guest)
                    <option value="{{ $guest->id }}">{{ $guest->name }} - {{ $guest->phone }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>الحجز (اختياري)</label>
            <select name="booking_id" class="form-select">
                <option value="">بدون</option>
                @foreach($bookings as $booking)
                    <option value="{{ $booking->id }}">حجز #{{ $booking->id }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>الغرفة (اختياري)</label>
            <select name="room_id" class="form-select">
                <option value="">بدون</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">غرفة {{ $room->room_number }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>عنوان الشكوى</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>نص الشكوى</label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
        </div>

        <button class="btn btn-success w-100">حفظ الشكوى</button>

    </form>

</div>
@endsection
