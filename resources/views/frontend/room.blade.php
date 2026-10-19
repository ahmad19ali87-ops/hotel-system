@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 800px;">

    <a href="{{ route('hotel.rooms', $hotel->slug) }}" class="btn btn-outline-secondary btn-sm mb-3">← العودة إلى قائمة غرف {{ $hotel->name }}</a>

    <div class="card shadow-sm overflow-hidden mb-4">
        @if($room->image_url)
            <img src="{{ $room->image_url }}" alt="غرفة {{ $room->room_number }}" class="w-100" style="height: 320px; object-fit: cover;">
        @endif

        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="fw-bold mb-1">غرفة رقم {{ $room->room_number }}</h2>
                    <p class="text-muted mb-0">{{ $room->type?->name ?? 'غرفة قياسية' }}</p>
                </div>
                <div class="text-end">
                    <span class="fs-4 fw-bold text-primary">{{ number_format((float) $room->price) }} ل.س</span>
                    @if((float)$room->price_usd > 0)
                        <div class="fs-5 fw-bold text-success">${{ number_format((float) $room->price_usd, 2) }} USD</div>
                    @endif
                    <div class="small text-muted">للّيلة الواحدة</div>
                </div>
            </div>

            @if($room->type?->description)
                <div class="alert alert-light border my-3">
                    <p class="mb-0 text-secondary">{{ $room->type->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 text-primary">📝 تأكيد حجز الغرفة</h4>
        </div>
        <div class="card-body p-4">
            @if($errors->any())
                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('hotel.book', $hotel->slug) }}">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">

                <div class="mb-3">
                    <label class="form-label fw-bold">اسم النزيل / العميل</label>
                    <input type="text" name="guest_name" value="{{ old('guest_name') }}" class="form-control" placeholder="الاسم الثلاثي" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الهاتف للتواصل</label>
                    <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" class="form-control" placeholder="رقم الجوال أو الواتساب" required>
                    <div class="form-text small text-muted">
                        💡 السعر معروض بالليرة السورية والدولار الأمريكي؛ سيتم تأكيد حجزك واعتماد العملة المفضلة لديك عبر قسم الاستقبال بالفندق.
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">تاريخ الوصول (Check-in)</label>
                        <input type="date" name="check_in" value="{{ old('check_in', $checkIn) }}" min="{{ now()->toDateString() }}" required class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">تاريخ المغادرة (Check-out)</label>
                        <input type="date" name="check_out" value="{{ old('check_out', $checkOut) }}" min="{{ $checkIn ?: now()->addDay()->toDateString() }}" required class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                    ✓ إتمام وتأكيد الحجز الآن
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
