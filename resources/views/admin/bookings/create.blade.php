@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">إنشاء حجز جديد</h2>

    <form action="{{ route('bookings.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-bold">الغرفة</label>
            <select name="room_id" class="form-select" required>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">
                        غرفة رقم {{ $room->room_number }} ({{ $room->type?->name ?? 'عادية' }} - {{ number_format($room->price) }} ل.س / ${{ number_format($room->price_usd ?? 0, 2) }} USD)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">العملة المعتمدة للحساب والدفع</label>
            <select name="currency" class="form-select" required>
                <option value="SYP" @selected(old('currency') === 'SYP')>🇸🇾 الليرة السورية (ل.س)</option>
                <option value="USD" @selected(old('currency') === 'USD')>🇺🇸 الدولار الأمريكي ($ USD)</option>
            </select>
        </div>

        <div class="mb-3">
            <label>اسم العميل</label>
            <input type="text" name="guest_name" class="form-control">
        </div>

        <div class="mb-3">
            <label>هاتف العميل</label>
            <input type="text" name="guest_phone" class="form-control">
        </div>

        <div class="mb-3">
            <label>تاريخ الدخول</label>
            <input type="date" name="check_in" class="form-control">
        </div>

        <div class="mb-3">
            <label>تاريخ الخروج</label>
            <input type="date" name="check_out" class="form-control">
        </div>

        <button class="btn btn-success w-100">حفظ الحجز</button>

    </form>

</div>
@endsection
