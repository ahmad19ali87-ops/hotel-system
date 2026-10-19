@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-3">الغرف المتاحة في فندق {{ $hotel->name }}</h2>

    <p class="text-muted mb-4">
        المحافظة: {{ $hotel->province }}  
        — التصنيف:
        @for($i = 0; $i < $hotel->stars; $i++)
            ⭐
        @endfor
    </p>

    <div class="row">

        @forelse($rooms as $room)
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">

                    {{-- صورة الغرفة --}}
                    @php
                        $images = $room->images ? json_decode($room->images, true) : [];
                    @endphp

                    @if(count($images))
                        <img src="{{ asset('uploads/rooms/' . $images[0]) }}" class="card-img-top" style="height: 220px; object-fit: cover;">
                    @else
                        <img src="{{ asset('default-room.jpg') }}" class="card-img-top" style="height: 220px; object-fit: cover;">
                    @endif

                    <div class="card-body">

                        {{-- نوع الغرفة --}}
                        <h5 class="card-title">
                            {{ $room->type ?? 'غرفة' }}
                        </h5>

                        {{-- السعر --}}
                        <p class="card-text">
                            السعر: <strong>{{ number_format($room->price) }} ل.س</strong>
                        </p>

                        {{-- عدد الأسرة --}}
                        <p class="card-text">
                            عدد الأسرة: {{ $room->beds }}
                        </p>

                        {{-- وصف الغرفة --}}
                        <p class="card-text text-muted">
                            {{ $room->description }}
                        </p>

                        {{-- زر الحجز --}}
                        <a href="/booking/{{ $room->id }}" class="btn btn-success w-100">
                            احجز الآن
                        </a>

                    </div>
                </div>
            </div>
        @empty
            <p class="text-center">لا توجد غرف متاحة في هذا الفندق.</p>
        @endforelse

    </div>

</div>
@endsection
