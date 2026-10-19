@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">تعديل الحجز</h2>

    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('bookings.update', $booking->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">الغرفة</label>
            <select name="room_id" class="form-select" required>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ old('room_id', $booking->room_id) == $room->id ? 'selected' : '' }}>
                        غرفة رقم {{ $room->room_number }} ({{ $room->type?->name ?? 'عادية' }} - {{ number_format($room->price) }} ل.س)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">اسم النزيل</label>
            <input type="text" name="guest_name" class="form-control" value="{{ old('guest_name', $booking->guest?->name ?? $booking->guest_name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">هاتف النزيل</label>
            <input type="text" name="guest_phone" class="form-control" value="{{ old('guest_phone', $booking->guest?->phone ?? $booking->guest_phone) }}">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">تاريخ الدخول</label>
                <input type="date" name="check_in" class="form-control" value="{{ old('check_in', $booking->check_in) }}" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">تاريخ الخروج</label>
                <input type="date" name="check_out" class="form-control" value="{{ old('check_out', $booking->check_out) }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">العملة المعتمدة للحساب والدفع</label>
            <select name="currency" class="form-select" required>
                <option value="SYP" @selected(old('currency', $booking->currency) === 'SYP')>🇸🇾 الليرة السورية (ل.س)</option>
                <option value="USD" @selected(old('currency', $booking->currency) === 'USD')>🇺🇸 الدولار الأمريكي ($ USD)</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">الحالة</label>
            <select name="status" class="form-select" required>
                <option value="pending" {{ old('status', $booking->status) === 'pending' ? 'selected' : '' }}>⏳ حجز جديد (بانتظار التأكيد)</option>
                <option value="confirmed" {{ old('status', $booking->status) === 'confirmed' ? 'selected' : '' }}>✓ مؤكد</option>
                <option value="checked_in" {{ old('status', $booking->status) === 'checked_in' ? 'selected' : '' }}>تم تسجيل الدخول (مقيم)</option>
                <option value="checked_out" {{ old('status', $booking->status) === 'checked_out' ? 'selected' : '' }}>تم تسجيل الخروج (مغادر)</option>
                <option value="cancelled" {{ old('status', $booking->status) === 'cancelled' ? 'selected' : '' }}>ملغي</option>
            </select>
        </div>

        <button class="btn btn-success w-100">تحديث بيانات الحجز</button>
    </form>

</div>
@endsection
