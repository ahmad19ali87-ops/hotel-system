@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 1100px;">
    <a href="{{ route('hotel.front', $hotel->slug) }}" class="btn btn-outline-secondary btn-sm mb-3">← العودة إلى صفحة فندق {{ $hotel->name }}</a>
    <h2 class="mb-3">🏨 الغرف المتاحة في {{ $hotel->name }}</h2>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('hotel.rooms', $hotel->slug) }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold">تاريخ القدوم (Check-in)</label>
                    <input type="date" name="check_in" value="{{ old('check_in', $checkIn) }}" min="{{ now()->toDateString() }}" required class="form-control">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">تاريخ المغادرة (Check-out)</label>
                    <input type="date" name="check_out" value="{{ old('check_out', $checkOut) }}" min="{{ $checkIn ?: now()->addDay()->toDateString() }}" required class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 ابحث عن الغرف</button>
                </div>
            </form>
        </div>
    </div>

    @if($checkIn && $checkOut)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 text-success">✓ تم العثور على {{ $rooms->count() }} غرفة متاحة</h4>
            <span class="badge bg-light text-dark border">الفترة: {{ $checkIn }} إلى {{ $checkOut }}</span>
        </div>

        <div class="row g-4">
            @forelse($rooms as $room)
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 overflow-hidden">
                        @if($room->image_url)
                            <img src="{{ $room->image_url }}" alt="غرفة {{ $room->room_number }}" class="card-img-top" style="height: 200px; object-fit: cover;">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold">غرفة رقم {{ $room->room_number }}</h5>
                            <p class="text-muted mb-2">{{ $room->type?->name ?? 'غرفة قياسية' }}</p>
                            @if($room->type?->description)
                                <p class="small text-secondary mb-3">{{ Str::limit($room->type->description, 80) }}</p>
                            @endif
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-5 fw-bold text-primary">{{ number_format((float) $room->price) }} ل.س <small class="text-muted fs-6">/ ليلة</small></div>
                                    @if((float)$room->price_usd > 0)
                                        <div class="small fw-bold text-success">${{ number_format((float)$room->price_usd, 2) }} USD <small class="text-muted">/ ليلة</small></div>
                                    @endif
                                </div>
                                <a href="{{ route('hotel.room.show', ['hotelSlug' => $hotel->slug, 'id' => $room->id, 'check_in' => $checkIn, 'check_out' => $checkOut]) }}" class="btn btn-outline-primary btn-sm fw-bold">
                                    تفاصيل وحجز
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning py-4 text-center">
                        للأسف، لا توجد غرف متاحة في هذه الفترة المحددة. يرجى اختيار مواعيد أخرى.
                    </div>
                </div>
            @endforelse
        </div>
    @else
        <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <span class="fs-1">📅</span>
                <h5 class="mt-3 text-muted">يرجى تحديد تاريخي القدوم والمغادرة لعرض الغرف المتوفرة والأسعار.</h5>
            </div>
        </div>
    @endif
</div>
@endsection