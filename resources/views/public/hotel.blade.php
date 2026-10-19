@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- اسم الفندق --}}
    <h2 class="mb-3">{{ $hotel->name }}</h2>

    {{-- المحافظة والتصنيف --}}
    <p class="text-muted">
        المحافظة: {{ $hotel->province }} <br>
        التصنيف:
        @for($i = 0; $i < $hotel->stars; $i++)
            ⭐
        @endfor
    </p>

    {{-- سلايدر الصور --}}
    @if(count($images))
        <div id="hotelSlider" class="carousel slide mb-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                @foreach($images as $index => $img)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ asset('uploads/hotels/' . $img) }}" class="d-block w-100" style="height: 350px; object-fit: cover;">
                    </div>
                @endforeach
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#hotelSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>

            <button class="carousel-control-next" type="button" data-bs-target="#hotelSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    @endif

    {{-- وصف الفندق --}}
    <div class="mb-4">
        <h4>وصف الفندق</h4>
        <p>{{ $hotel->description }}</p>
    </div>

    {{-- زر الغرف المتاحة --}}
    <a href="/hotel/{{ $hotel->slug }}/rooms" class="btn btn-primary btn-lg w-100">
        شاهد الغرف المتاحة
    </a>

</div>
@endsection
